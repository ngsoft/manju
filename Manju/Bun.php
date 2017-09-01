<?php

namespace Manju;
use RedBeanPHP\SimpleModel;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;

if (version_compare(PHP_VERSION, '7.0', '<')) {
    throw new Exception('This program needs php version > 7.0 to run correctly.');
    exit;
}

abstract class Bun extends SimpleModel implements \IteratorAggregate, \Countable, \ArrayAccess, \JsonSerializable{
    
    //===============       Configurable Properties        ===============//
    
    /**
     * Type of the bean
     * 
     * @var string $bean
     */
    protected $beantype;
    
    /**
     * Enables Scalar type conversion
     * if set to false Manju will passtru the data after using the formatter//validator
     * 
     * @var bool
     */
    protected $scalar_type_conversion = true;
    
    
    
    /**
     * Flag that prevents Manju to execute
     * the store() method. Can be used by your validator method
     * to prevent adding data you don't want
     * 
     * @var bool
     */
    protected $cansave = true;
    
    
    /**
     * Flag that enables datetime columns to be created
     *          - created : created_at
     *          - updated : updated_at
     * @var bool 
     */
    protected $savetimestamps = false;
    

    //===============       Bun Properties        ===============//

    const MANJU_VERSION = '0.1.2';
    const ACCEPTED_BEAN_NAMES = '/^[^0-9][a-z0-9]+$/';
    const ACCEPTED_PROP_NAMES = '/^[^0-9_][a-z0-9_]+$/';
    
    protected static $connected = false;

    /**
     * List of beans declared to BeanHelper
     * 
     * @var array 
     */
    public static $beanlist = [];

    /**
     * @var OODBBean
     */
    protected $bean;
    
    
    /**
     * Scallar typed properties converted from bean
     * @var $array
     */
    private $properties = [];
    

    /**
     * Valid types to be converted
     * @var array 
     */
    private static $valid_scalar_types = [ "integer", "double", "boolean", "array", "object", "datetime"];
    
    /**
     * Passtru data to the bean
     */
    private static $ignore_scalar_types = [ "string", "NULL"];
    
    /**
     * Store Special columns
     * @var array 
     */
    private static $columns = [];
    
    /**
     * Store default values
     * @var array 
     */
    private static $defaults = [];

    /**
     * Store aliases
     * @var array
     */
    private static $alias = [];
    
    /**
     * Store required values
     * @var array
     */
    private static $required = [];


    /**
     * Does array or obj gets accessed?
     * @var bool
     */
    private $tainted = false;
    
    /**
     * Store the plugins
     * @var \stdClass
     */
    protected static $plugins;






    /**
     * Model Construct method
     * Gets called when first inputing or retrieving data
     */
    protected function configure(){}

    /**
     * Defines if it't time to run $this->configure
     */
    private function _configure(){
        $key = get_called_class();
        if(!array_key_exists($key, self::$columns)){
            self::$columns[$key] = [];
            self::$defaults[$key] = [];
            self::$alias[$key] = [];
            self::$required[$key] = [];
            $this->configure();
        }
    }
    
    /**
     * Initialize the bean with defaults values
     * gets called on load() and create()
     */
    private function initialize_bean(){
        if($this->savetimestamps){
            foreach (['created_at', 'updated_at'] as $prop){
                $this->setColumnType($prop, 'datetime');
            }
        }
        
        //set defaults values to bean using the filter
        foreach (self::$defaults[get_called_class()] as $prop => $val){
            if(is_null($this->bean->$prop)){
                $this->$prop = $val;
            }
        }
        
    }



    public function __invoke($bean = null) {
        $this->initialize($bean);
        return $this;
    }
    
    public function __construct() {
        $this->initialize(false);
    }

    

    private function initialize($bean = null){
        if(!self::$connected) self::$connected = R::testConnection();
        if(!self::$connected){
            $message = "Cannot connect to the database please run R::setup() before calling a model.";
            throw new Exception($message);
            exit;
        }
        
        $this->cansave = true;
        $this->properties = [];
        $this->tainted = false;
        
        BunHelper::register();
        self::$beanlist or $this->beanlist();
        isset(self::$beanlist[$this->beantype()])?:self::$beanlist[$this->beantype()] = get_called_class();
        $this->_configure();
        
        if(is_bool($bean)) return;
        switch (gettype($bean)){
            case "integer":
                $this->load($bean);
                break;
            case "NULL":
                $this->create();
                break;
            case "object":
                if($bean instanceof OODBBean){
                    $this->takeown($bean);
                    $this->open();
                }
                break;
            case "array":
                $this->import($bean);
                break;
        }
        
    }
    
    /**
     * Scan the folder containing the model for other models
     */
    private function beanlist(){
        $r = new \ReflectionClass($this);
        self::$beanlist[$this->beantype()] = get_called_class();
        if($filename = $r->getFileName()){
            //scan the dir for class files
            $dir = dirname($filename);
            foreach (scandir($dir) as $file){
                if(preg_match('/.php$/i', $file) and strlen($file) > 4){
                    include_once $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
        //scan class list for Manju\Bun
        $list = array_reverse(get_declared_classes());
        foreach ($list as $class){
            if(in_array(__CLASS__, class_parents($class))){
                $obj = new $class;
            }
        }
    }
    
    /**
     * Defines the bean type using the class basename
     * 
     * @throws Exception
     * @return string
     */
    public function beantype(){
        if($this->beantype) return $this->beantype;
        if($class = get_called_class()){
            $classpath = explode("\\", $class);
            $basename = array_pop($classpath);
            $basename = strtolower($basename);
            $basename = str_replace(['model','own','shared','_'], '', $basename);
        }
        if(!preg_match(self::ACCEPTED_BEAN_NAMES, $basename)){
            $message = sprintf('%s : invalid Bean Type please set "protected $beantype".', get_called_class());
            throw new Exception($message);
            exit;
        }
        return $this->beantype = $basename;
    }
    
    /**
     * Access the plugins
     * @return \stdClass
     */
    public function plugins(){
        if(!is_object(self::$plugins)){
            self::$plugins = new \stdClass();
        }
        return self::$plugins;
    }
    
    /**
     * Add a plugin accessible to all the models
     * @param type $instance Plugin Object
     * @param string $friendly_name name for the plugin to be accessed $this->plugins()->friendly_name
     * @return \Manju\Bun
     */
    public function addPlugin($instance, string $friendly_name = null): Bun{
        if(!is_object($instance)){
            return $this;
        }
        if(!$friendly_name){
            $class = get_class($instance);
            $classpath = explode('\\', $class);
            $basename = array_pop($classpath);
            $friendly_name = strtolower($basename);
        }
        $this->plugins()->$friendly_name = $instance;
        return $this;
    }
    
    
    //=============== FUSE Methods + RedBeanPHP\SimpleModel extended methods ===============//
    
    public function dispense() {}
    public function open(){}
    public function update() {}
    public function after_update() {}
    public function delete() {}
    public function after_delete() {}
    
    
    
    final public function &__get($prop) {
        $this->bean or $this->initialize();
        //Alias?
        $prop = $this->getAliasTarget($prop);
        $val = $this->bean->$prop;
        if($prop == 'id'){
            return $val;
        }
        
        //association?
        if(is_array($val)){
            //rw mode
            $val = &$this->bean->$prop;
            return $val;
        }
        //many to one?
        if($val instanceof OODBBean){
            if($bun = $val->getMeta('model')){
                return $bun;
            }
            return $val;
        }
        //convert enabled?
        if($this->scalar_type_conversion){
            if(array_key_exists($prop, $this->properties)){
                $val = $this->properties[$prop];
            }
            elseif($this->getColumnType($prop)){
                $val = $this->convertForGet($prop, $val);
                if(is_object($val) or is_array($val)){
                    $this->tainted = true;
                }
            }
        }
        //call formater
        $method = "get_$prop";
        if(method_exists($this, $method)){
            $val = $this->$method($val);
        }
        $this->properties[$prop] = $val;
        return $val;
    }

    final public function __isset($prop) {
        $this->bean or $this->initialize();
        return isset( $this->bean->$prop );
    }

    final public function __set($prop, $val){
        $this->bean or $this->initialize();
        //Alias?
        $prop = $this->getAliasTarget($prop);
        if($prop == 'id') return;
        //validator/Formater
        $method = "set_$prop";
        if(method_exists($this, $method)){
            $val = $this->$method($val);
        }
        
        //one to many
        //get error if inputing SimpleModel instead of OODBBean
        if($val instanceof Bun){
            $val = $val->unbox();
        }
        //many to one
        //prevent accidental set without offset
        //as that can break associations
        foreach ([
            '/^own([A-Z][a-z0-9]+)List$/',
            '/^xown([A-Z][a-z0-9]+)List$/',
            '/^shared([A-Z][a-z0-9]+)List$/',
        ] as $regex){
            if(preg_match($regex, $prop)){
                //prevent accident
                if(is_object($val)){
                    if($val instanceof OODBBean or $val instanceof SimpleModel){
                        $this->bean->{$prop}[]=$val;
                        return;
                    }
                }
                //can only set array of bean/Bun or empty array to delete
                if(!is_array($val)) return;
            }
        }
        
        //convert ?
        if($this->scalar_type_conversion){
            if($this->getColumnType($prop)){
                $this->properties[$prop] = $val;
                $val = $this->convertForSet($prop, $val);
            }
        }
        $this->bean->$prop = $val;
    }
    
    public function __unset($prop) {
        if(!is_null($this->bean->$prop)){
            
            //to many associations are arrays
            if(is_array($this->bean->$prop)){
                $this->bean->$prop = [];
                return;
            }
            
            $this->bean->$prop = null;
            if($def = $this->getColumnDefaults($prop)){
                //use converter
                $this->$prop = $def;
            }
        }
    }

    public function box() {
        return $this;
    }

    public function unbox() {
        $this->bean or $this->initialize();
        return $this->bean;
    }
    
    
    //===============       RedBean CRUD        ===============//
    
    /**
     * Couples the bun to the bean
     * @param OODBBean $bean
     */
    private function takeown(OODBBean $bean){
        $bean->setMeta('model', $this);
        $this->bean = $bean;
    }
    
    
    /**
     * Method extended from RedBeanPHP\SimpleModel
     * Loads the bean using BeanHelper
     * 
     * @param OODBBean $bean
     * @return $this
     */
    final public function loadBean(OODBBean $bean): Bun{
        $this->initialize(false);
        $this->bean = $bean;
        $this->initialize_bean();
        return $this;
    }

    /**
     * Create a new empty bean
     * @return $this
     */
    final public function create(): Bun{
        BunHelper::dispense($this);
        return $this;
    }
    
    /**
     * Loads a bean with the data corresponding to the id column
     * @param int $id id field of the bean
     * @return $this
     */
    final public function load(int $id = 0): Bun{
        BunHelper::load($this, $id);
        return $this;
    }
    
    /**
     * Stores the bean into the database
     * @param bool $fresh Refresh the bean with saved data
     * @return $this
     */
    final public function store(bool $fresh = false): Bun{
        if(!$this->bean or !$this->cansave) return $this;
        $this->updateTainted();
        //as timestamps are objects too, this goes after
        $this->add_timestamps();
        if($this->checkRequired()){
            R::store($this->bean);
            if($fresh) return $this->fresh ();
            else $this->initialize(false);
        }
        return $this;
    }
    
    /**
     * Reloads data for the current bean from the database
     * @return $this
     */
    final public function fresh(): Bun{
        count(self::$columns) or $this->initialize(false);
        return $this->load($this->id);
    }
    
    /**
     * Removes a bean from the database
     * @return $this;
     */
    final public function trash(): Bun{
        if(!$this->bean) return $this;
        R::trash($this->bean);
        $this->bean = null;
        $this->initialize();
        return $this;
    }
    
    //===============       RedBean SQL(shortcuts)        ===============//
    
    
    /**
     * Finds a bun using a type and a where clause (SQL).
     * As with most Query tools in RedBean you can provide values to
     * be inserted in the SQL statement by populating the value
     * array parameter; you can either use the question mark notation
     * or the slot-notation (:keyname).
     *
     * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
     * @param array  $bindings values array of values to be bound to parameters in query
     *
     */
    public function find(string $sql = null, array $bindings = []){
        count(self::$columns) or $this->initialize(false);
        $r = [];
        if($rb = R::find($this->beantype(),$sql,$bindings)){
            $r = $this->createPlate($rb);
        }
        return $r;
    }
    
    /**
     * @see Facade::find
     * This variation returns the first bun only.
     *
     * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
     * @param array  $bindings values array of values to be bound to parameters in query
     */
    public function findOne($sql = null, array $bindings = []){
        count(self::$columns) or $this->initialize(false);
        $r = null;
        if($rb = R::findOne($this->beantype(),$sql,$bindings)){
            $r = $rb->getMeta('model');
        }
        return $r?:$rb;
    }
    
    /**
     * @see Facade::find
     *      The findAll() method differs from the find() method in that it does
     *      not assume a WHERE-clause, so this is valid:
     *
     * R::findAll('person',' ORDER BY name DESC ');
     *
     * Your SQL does not have to start with a valid WHERE-clause condition.
     *
     * @param string $sql      sql    SQL query to find the desired bun, starting right after WHERE clause
     * @param array  $bindings values array of values to be bound to parameters in query
     */
    public function findAll($sql = null, array $bindings = []){
        count(self::$columns) or $this->initialize(false);
        $r = [];
        if($rb = R::findAll($this->beantype(), $sql, $bindings)){
            $r = $this->createPlate($rb);
        }
        return $r;
    }
    
    /**
     * Convenience function to execute Queries directly.
     * Executes SQL.
     *
     * @param string $sql       sql    SQL query to execute
     * @param array  $bindings  values a list of values to be bound to query parameters
     *
     * @return integer
     */
    public function exec($sql = null, array $bindings = []):int{
        count(self::$columns) or $this->initialize(false);
        return R::exec($sql, $bindings);
    }
    
    /**
     * Convenience function to execute Queries directly.
     * Executes SQL.
     *
     * @param string $sql       sql    SQL query to execute
     * @param array  $bindings  values a list of values to be bound to query parameters
     *
     * @return array
     */
    public function getAll($sql = null, array $bindings = []):array{
        count(self::$columns) or $this->initialize(false);
        return R::getAll($sql, $bindings);
    }
    
    
    /**
     * Gets the last insert id
     * @return int
     */
    public function getInsertID(): int{
        count(self::$columns) or $this->initialize(false);
        return R::getInsertID();
    }
    


    
    
    
    
    
    
    //===============       Bun       ===============//
    
    /**
     * Gets an array of Bun corresponding to a One to Many or Many to Many relationship
     * @param string $prop
     * @return array
     */
    public function getPlate(string $prop): array{
        count(self::$columns) or $this->initialize(false);
        $prop = $this->getAliasTarget($prop);
        $return = [];
        if($val = $this->bean->$prop){
            if(is_array($val)){
                $return = $this->createPlate($val);
            }
        }
        return $return;
    }
    
    /**
     * Convert an array of bean to an array of bun
     * @param array $data
     * @return array
     */
    private function createPlate(array $data): array{
        $r = [];
        foreach ($data as $id => &$bean){
            if(!($bean instanceof OODBBean)) continue;
            if($bun = $bean->getMeta('model') and $bun instanceof Bun){
                $r[$id] = $bun;
            }
            else $r[$id] = $bean;
        }
        return $r;
    }

    /**
     * Adds created_at and updated_at columns and their values
     */
    private function add_timestamps(){
        if(!$this->bean) return;
        if(!$this->savetimestamps) return;
        $date = date(DateTime::DB);
        //save as valid sql datetime
        if(!$this->bean->created_at) $this->bean->created_at = $date;
        $this->bean->updated_at = $date;
    }
    
    /**
     * Set scalar type for column
     * @param string $prop Property name
     * @param string $type valid php scalar type or datetime (will creates a Manju\DateTime object)
     */
    protected function setColumnType(string $col, string $type){
        if(!in_array($type, self::$valid_scalar_types)) return false;
        $cols = &self::$columns[get_called_class()];
        $cols[$col] = $type;
        return true;
    }
    
    /**
     * Get the declared scalar type for the column
     * @param string $prop name of the column
     * @return string|null
     */
    protected function getColumnType(string $col){
        if(!isset(self::$columns[get_called_class()][$col])) return null;
        return self::$columns[get_called_class()][$col];
    }
    
    /**
     * Set the default value for a column
     * @param string $col Column
     * @param type $defaults Default value to set
     */
    protected function setColumnDefaults(string $col, $defaults = null){
        if(is_null($defaults)) return;
        self::$defaults[get_called_class()][$col] = $defaults;
    }
    
    /**
     * Get the default value for a column
     * @param string $col Column name
     * @return type
     */
    protected function getColumnDefaults(string $col){
        if(!array_key_exists($col, self::$defaults[get_called_class()])) return null;
        return self::$defaults[get_called_class()][$col];
    }
    
    /**
     * Add a column alias
     * @param string $alias Alias to use
     * @param string $target Column the alias points to
     * @return \Manju\Bun
     */
    protected function addAlias(string $alias, string $target): Bun{
        self::$alias[get_called_class()][$alias] = $target;
        return $this;
    }
    
    /**
     * Get the $alias target or the alias itself if no target
     * @param string $alias
     * @return string
     */
    protected function getAliasTarget(string $alias): string{
        return isset(self::$alias[get_called_class()][$alias])?self::$alias[get_called_class()][$alias]:$alias;
    }
    
    /**
     * Adds a managed column
     * @param string $col Column name
     * @param string $type Column Scalar type
     * @param type $defaults Column default value
     * @return \Manju\Bun
     */
    protected function addCol(string $col, string $type = null, $defaults = null):Bun{
        $this->setColumnType($col, $type);
        if(!is_null($defaults))$this->setColumnDefaults($col, $defaults);
        return $this;
    }
    
    /**
     * Set a required col
     * Equivalent to sql : notnull
     * @param string $col
     * @return \Manju\Bun
     */
    protected function addRequired(string $col): Bun{
        self::$required[get_called_class()][]=$col;
        return $this;
    }
    
    /**
     * Get required column list
     * @return array
     */
    protected function getRequiredCols():array{
        return self::$required[get_called_class()];
    }
    
    /**
     * Check if all required cols are set
     * Cancel the store() method if false
     * @return boolean
     */
    private function checkRequired(){
        foreach ($this->getRequiredCols() as $prop){
            if(is_null($this->bean->$prop)){
                return false;
            }
        }
        return true;
    }

    
    /**
     * Converts data from the bean to the user
     * @param string $prop
     * @param type $val
     * @return mixed converted data
     */
    protected function convertForGet(string $prop, $val){
        if($type = $this->getColumnType($prop)){
            switch ($type){
                case"integer":
                    $val = intval($val);
                    break;
                case "double":
                    $val = floatval($val);
                    break;
                case "boolean":
                    $val = boolval((int)$val);
                    break;
                case "array":
                case "object":
                    $val = $this->b64unserialize($val);
                    break;
                case "datetime":
                    $val = new DateTime($val);
            }
        }
        return $val;
    }
    
    /**
     * Convert data from the user to the bean
     * @param string $prop
     * @param type $val
     * @return mixed converted data
     */
    protected function convertForSet(string $prop, $val){
        //detects DateTime objects
        if($val instanceof \DateTime){
            $this->setColumnType($prop, "datetime");
            $val = $val->getTimestamp();//int
        }
        //datetime (int) timestamp conversion
        //then passtru the string
        if($declared_type = $this->getColumnType($prop)){
            if($declared_type == "datetime"){
                if(is_int($val)){
                    $val = date(DateTime::DB,$val);
                }
            }
        }
        if(!$this->getColumnType($prop)){
            return $val;
        }
        
        $type = gettype($val);
        
        if(in_array($type, self::$ignore_scalar_types)){
            return $val;
        }
        if($type != $this->getColumnType($prop)){
            $val = null;
        }
        if(in_array($type, self::$valid_scalar_types)){
            switch (gettype($val)){
                case"integer":
                    break;
                case "double":
                    $val = "$val";
                    break;
                case "boolean":
                    $val = $val?1:0;
                    break;
                case "array":
                case "object":
                    $val = $this->b64serialize($val);
                    break;
            }
        }
        return $val;
    }
    
    /**
     * Update objects or array if they gets accessed
     * before using store()
     */
    protected function updateTainted(){
        if(!$this->tainted) return;
        if(!$this->scalar_type_conversion) return;
        foreach ($this->properties as $prop => $val){
            if(is_array($val) or is_object($val)){
                if($val instanceof \DateTime){
                    $val = $this->convertForSet($prop, $val);
                    $this->bean->$prop = $val;
                }
                elseif(gettype($val) == $this->getColumnType($prop)){
                    $val = $this->convertForSet($prop, $val);
                    $this->bean->$prop = $val;
                }
            }
        }
    }



    /**
    * Serialize and encode string to base 64 
    * \Serializable Objects and arrays will be saved into that format into the database
    * @param type $value
    * @return string
    */
    public function b64serialize($value): string{
        if(is_object($value)){
            if(!($value instanceof \Serializable)){
                $value = '';
                return $value;
            }
        }
        $value = serialize($value);
        $value = base64_encode($value);
        return $value;
    }
    
    /**
     * Unserialize a base 64 serialized string
     * will return \Serializable Objects or array
     * @param type $str
     * @return array or object
     */
    public function b64unserialize(string $str = null){
        $obj = null;
        if(!empty($str)){
            $str = base64_decode($str);
            $obj = unserialize($str);   
        }
        return $obj;
    }
    
    //===============       Import/Export        ===============//
    
    
    /**
     * Import $data into Bean
     * @param array $data
     */
    public function import(array $data){
        if(!count($data)) return;
        
        if(isset($data['$id'])) $this->load($data['id']);
        else $this->create ();
        
        foreach ($data as $prop => $val){
            if(is_int($prop) or is_null($prop)) continue;
            //convert data
            $this->$prop = $val;
        }
    }
    
    /**
     * Export data from bean
     * @param bool $convert convert data using schema
     * @return array
     */
    public function export(bool $convert = true): array{
        $export = [];
        $this->bean or $this->initialize();
        $properties = array_merge($this->bean->getMeta('sys.orig'), $this->bean->getProperties());
        
        foreach ($properties as $prop => $val){
            //owned/shared lists are array, they won't be exported
            if(is_array($val)) continue;
            //to one are beans
            if($val instanceof OODBBean) continue;
            //we use the converter like this
            if($convert) $export[$prop] = $this->$prop;
            else $export[$prop] = $val;
        }
        return $export;
    }
    
    //===============       Interfaces Specific        ===============//
    
    public function getIterator(){
        return new \ArrayIterator($this->export());
    }
    
    public function count(){
        return count($this->export());
    }
    
    public function offsetExists($prop){
        return $this->__isset($prop);
    }

    public function &offsetGet($prop){
        $val = $this->__get($prop);
        return $val;
    }

    public function offsetSet($prop, $val){
        $this->__set($prop,$val);
    }

    public function offsetUnset($propt){
        $this->__unset($propt);
    }
    
    public function jsonSerialize() {
        $data = $this->export();
        $return = [];
        
        foreach($data as $prop => $val){
            if(is_object($val)){
                if($val instanceof \JsonSerializable) $val = $val->jsonSerialize ();
                else{
                    //try to get most values
                    $val = json_decode(json_encode($val),true);
                }
            }
            $return[$prop] = $val;
        }
        return $return;
    }
    
    
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }


}
