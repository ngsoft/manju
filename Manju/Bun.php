<?php

namespace Manju;
use RedBeanPHP\SimpleModel;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;
use Psr\Log\LoggerInterface;



/**
 * Constants used
 */
@define('MANJU_CREATED_COLUMN', 'created_at');
@define('MANJU_UPDATED_COLUMN', 'updated_at');


abstract class Bun extends SimpleModel{
    
    //===============       Configurable Properties        ===============//
    
    /**
     * Type of the bean
     * @var string 
     */
    protected $beantype;
    
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
     *          - created : MANJU_CREATED_COLUMN
     *          - updated : MANJU_UPDATED_COLUMN
     * @var bool 
     */
    protected $savetimestamps = false;
    
    
    //===============       Bun Properties        ===============//
    
    const VERSION = '1.0.1';
    
    /**
     * Regex to check some values
     */
    const VALID_BEAN_TYPE = '/^[^0-9][a-z0-9]+$/';
    const TO_MANY_LIST = '/^(shared|own|xown)([A-Z][a-z0-9]+)List$/';
    const VALID_PROP = '/^[^0-9_][a-z0-9_]+$/';
    
    /**
     * List of beans declared to BunHelper
     * 
     * @var array 
     */
    public static $beanlist = [];    
    
    /**
     * Logger Aware
     * Set to static for better reusability
     * @var Psr\Log\LoggerInterface
     */
    protected static $logger;
    
    /**
     * Bean
     * @var OODBBean
     */
    protected $bean;
       
    /**
     * Store the plugins
     * @var \stdClass
     */
    private static $plugins;
    
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
     * Store default values
     * @var array 
     */
    private static $defaults = [];
    
    /**
     * Store Special columns
     * @var array 
     */
    private static $columns = [];    
 
    /**
     * Valid types to be converted
     * @var array 
     */
    private static $valid_types = [ "integer", "double", "boolean", "array", "object", "datetime"];
    
    /**
     * Aliases that can be set as types
     * @var array 
     */
    private static $types_alias = [
        "int"           =>  "integer",
        "float"         =>  "double",
        "bool"          =>  "boolean",
        "date"          =>  "datetime"
    ];
    
    /**
     * Scallar typed properties converted from bean
     * @var $array
     */
    private $properties = [];
    
    /**
     * Does array or obj gets accessed?
     * @var bool
     */
    private $tainted = false;
    

    
    //=============== FUSE Methods that can be extended into models ===============//
    public function dispense() {}
    public function open(){}
    public function update() {}
    public function after_update() {}
    public function delete() {}
    public function after_delete() {}
    
    
    //===============       Model Initialization        ===============//
    
    /**
     * Your Configure Method
     * Get run once at the first load of the model
     */
    abstract protected function configure();
    
    
    /**
     * Creates or load a new bean
     * @param int $bean id of the bean
     * @param array $bean Array to import into the bean
     * @param null $bean Creates a fresh bean
     * @return $this
     */
    public function __invoke($bean = null){
        $this->initialize($bean);
        return $this;
    }
    
    /**
     * Creates or load a new bean
     * @param int $bean id of the bean
     * @param array $bean Array to import into the bean
     * @param null|bool $bean Do nothing (prevent loops)
     */
    final public function __construct($bean = null) {
        $bean = is_null($bean)?false:$bean;
        $this->initialize(false);
    }


    /**
     * Defines if it't time to run $this->configure
     */
    private function _configure(){
        $class = get_class($this);
        if(!array_key_exists($class, self::$columns)){
            self::$beanlist[$this->beantype()] = $class;
            self::$columns[$class] = [];
            self::$alias[$class] = [];
            self::$defaults[$class] = [];
            self::$required[$class] = [];
            $this->configure();
        }
    }
    
    /**
     * Initialize the bean with defaults values
     */
    private function initialize_bean(){
        $this->initialize(false);
        
        if($this->savetimestamps){
            foreach ([MANJU_CREATED_COLUMN, MANJU_UPDATED_COLUMN] as $prop){
                $this->setColumnType($prop, 'datetime');
            }
        }
        
        //set defaults values to bean using the filter
        foreach (self::$defaults[get_class($this)] as $prop => $val){
            if(is_null($this->bean->$prop)){
                if(is_callable($val)){
                    $val = $val();
                }
                $this->$prop = $val;
            }
        }
        
        //initialize required values to null into the bean
        foreach (self::$required as $prop){
            if(is_null($this->bean->$prop)){
                $this->bean->$prop = null;
            }
        }
        
    }
    
    /**
     * Class constructor
     * @param mixed $bean
     */
    private function initialize($bean = null){
        if(BunHelper::connected()){
            //reset properties
            $this->tainted = false;
            $this->properties = [];
            $this->cansave = true;
            self::$beanlist or $this->setBeanlist();
            $this->_configure();
        }
        if(is_bool($bean)) return;
        
        switch (gettype($bean)){
            case "integer":
                $this->load($bean);
                break;
            case "NULL":
                $this->create();
                break;
            case "array":
                $this->import($bean);
                break;
        }        
    }
    
    //===============       Plugins Support        ===============//
 
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
     * @param string $friendly_name name for the plugin to be accessed $this->plugins()->friendly_name If not set will use lowercase class basename
     * @return $this
     */
    public function addPlugin($instance, string $friendly_name = null){
        if(!is_object($instance)){
            return $this;
        }
        if(!$friendly_name){
            $friendly_name = (new \ReflectionClass($instance))->getShortName();
            $friendly_name = strtolower($friendly_name);
        }
        $this->plugins()->$friendly_name = $instance;
        return $this;
    }
    
    //===============       Bun       ===============//
    
    /**
     * Set type for column
     * @param string $prop Column name
     * @param string $type valid php scallar type
     * @return bool
     */
    protected function setColumnType(string $prop, string $type): bool{
        //type is alias?
        if(isset(self::$types_alias[$type])) $type = self::$types_alias[$type];
        //type exists?
        if(!in_array($type, self::$valid_types)){
            $this->debug("Trying to set invalid type $type for column $prop in " . get_class($this));
            return false;
        }
        self::$columns[get_class($this)][$prop] = $type;
        return true;
    }
    
    /**
     * Get the declared type for a column
     * @param string $prop Column name
     * @return string|null
     */
    protected function getColumnType(string $prop){
        return isset(self::$columns[get_class($this)][$prop])?self::$columns[get_class($this)][$prop]:null;
    }
    
    /**
     * Set default value for a column
     * @param string $prop Column name
     * @param type $defaults default value (can be a callable)
     * @return bool
     */
    protected function setColumnDefaults(string $prop, $defaults): bool{
        if(is_null($defaults)) return false;
        self::$defaults[get_class($this)][$prop] = $defaults;
        return true;
    }
    
    /**
     * Get the declared default value for a column
     * @param string $prop
     * @return mixed
     */
    protected function getColumnDefaults(string $prop){
        return array_key_exists($prop, self::$defaults[get_class($this)])?self::$defaults[get_class($this)][$prop]:null;
    }
    
    /**
     * Add alias to Bun
     * @param string $alias Alias to use
     * @param string $target Column or list to point to
     * @return $this
     */
    protected function addAlias(string $alias, string $target){
        if($alias == $target){
            $this->debug("Trying to set alias $alias to $target in ".get_class($this));
        }
        else self::$alias[get_class ($this)][$alias] = $target;
        return $this;
    }
    
    /**
     * Get the target from an alias
     * @param string $alias
     * @return string
     */
    protected function getAliasTarget(string $alias): string{
        return isset(self::$alias[get_class($this)][$alias])?self::$alias[get_class($this)][$alias]:$alias;
    }
    
    
    /**
     * Add a managed column
     * @param string $prop Column name
     * @param string $type Column type
     * @param type $defaults Column Default
     * @return $this
     */
    protected function addCol(string $prop, string $type = null, $defaults = null){
        if(!is_null($type)) $this->setColumnType ($prop, $type);
        if(!is_null($defaults)) $this->setColumnDefaults ($prop, $defaults);
        if(is_null($type) and is_null($defaults)){
            $this->debug("Trying to set a managed column with no type and no default value (one parameter must be set) in ".get_class($this));
        }
        return $this;
    }
    
    /**
     * Add a required column, if value is null on store, store will be cancelled
     * @param string $prop Column name
     * @return $this
     */
    protected function addRequired(string $prop){
        self::$required[get_class($this)][] = $prop;
        return $this;
    }
    
    /**
     * Get list of required columns
     * @return array
     */
    protected function getRequiredCols():array{
        return self::$required[get_class($this)];
    }
    
    /**
     * Check if all required columns are set
     * @return bool
     */
    protected function checkRequired():bool{
        foreach ($this->getRequiredCols() as $prop){
            if(is_null($this->bean->$prop)){
                $this->debug("Required column $prop set to null value in ".get_class($this));
                return false;
            }
        }
        return true;
    }
    
    /**
     * Convert data from the bean to the user
     * @param string $prop
     * @param type $val
     * @return mixed
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
     * @return type
     */
    protected function convertForSet(string $prop, $val){
        
        //datetime detection
        if($val instanceof \DateTime){
            $val = $val->format(DateTime::DB);
            return $val;
        }
        if($this->getColumnType($prop) == 'datetime'){
            if(is_int($val)){
                $val = date(DateTime::DB, $val);
                return $val;
            }
            if(is_string($val)){
                $dt = new DateTime($val);
                if($value = $dt->format()){
                    return $value;
                }
                else{
                    $this->debug("Value $val for column $prop seems to be an incorrect datetime value in " . get_class($this));
                    return null;
                }
            }
        }
        //as it's not declared we cannot retrieve the formated value except for the use of formatters
        if(!$this->getColumnType($prop)){
            return $val;
        }
        
        $type = gettype($val);
        if($type != $this->getColumnType($prop)){
            $this->debug(sprintf("value type declared as %s for column $prop seems to be incorrect ( $type ) in %s", $this->getColumnType($prop), get_class($this)));
            return null;
        }
        switch ($type){
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
        return $val;
    }
    
    /**
     * Update objects or array if they gets accessed
     * before using store()
     */
    protected function updateTainted(){
        if(!$this->tainted) return;
        foreach ($this->properties as $prop => $val){
            if(!$this->getColumnType($prop)) continue;
            if($val instanceof \DateTime){
                $val = $this->convertForSet($prop, $val);
                $this->bean->$prop = $val;
            }
            elseif($this->getColumnType($prop) != gettype($val)){
                $val = $this->convertForSet($prop, $val);
                $this->bean->$prop = $val;
            }
        }
    }

    /**
     * Add MANJU_CREATED_COLUMN and MANJU_UPDATED_COLUMN
     * @return type
     */
    private function addTimestamps(){
        if(!$this->savetimestamps) return;
        $date = date(DateTime::DB);
        $created = MANJU_CREATED_COLUMN;
        $updated = MANJU_UPDATED_COLUMN;
        $this->bean->$created = $this->bean->$created?:$date;
        $this->bean->$updated = $date;
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
        $this->bean or $this->create();
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
    //===============       RedBeanPHP CRUD        ===============//
    
    /**
     * Create a new empty bean
     * trigger $this->dispense()
     * 
     * @return $this
     */
    final public function create(){
        $this->bean = null;
        BunHelper::dispense($this);
        return $this;
    }
    
    /**
     * Loads a bean with the data corresponding to the id column
     * trigger $this->dispense() on bean creation
     * then $this->open after data is loaded
     * 
     * @param int $id id field of the bean
     * @return $this
     */
    final public function load(int $id = 0){
        $this->bean = null;
        if($id == 0) $this->create ();
        else BunHelper::load($this, $id);
        return $this;
    }
    
    /**
     * Removes a bean from the database
     * trigger $this->delete() before deleting
     * trigger $this->after_delete() after
     * 
     * @return $this;
     */
    final public function trash(){
        if(!$this->bean) return $this;
        R::trash($this->bean);
        return $this;
    }
    
    /**
     * Reloads data for the current bean from the database
     * @return $this
     */
    final public function fresh(){
        return $this->load($this->id);
    }
    
    /**
     * Stores the bean into the database
     * @param bool $fresh Refresh the bean with saved data
     * @return $this
     */
    final public function store(bool $fresh = false){
        if(!$this->bean or !$this->cansave) return $this;
        $this->updateTainted();
        $this->addTimestamps();
        
        if($this->checkRequired()){
            R::store($this->bean);
            if($fresh) return $this->fresh ();
            else $this->initialize(false);
        }
        return $this;
    }    

    

    //===============       RedBeanPHP\SimpleModel Overrides        ===============//



    public function __get($prop) {
    }

    public function __isset($key) {
    }

    public function __set($prop, $value){
    }
    
    public function __unset($prop) {
        
    }

    /**
     * Used by bean FUSE to return the model
     * @return $this
     */
    public function box() {
        return $this;
    }

    /**
     * Used by BeanHelper/BunHelper to import a bean into SimpleModel just before $this->dispense()
     * @param OODBBean $bean
     * @return $this
     */
    public function loadBean(OODBBean $bean){
        $this->bean = $bean;
        $this->initialize_bean();
        return $this;
    }
    

    /**
     * Get the bean directly
     * @return RedBeanPHP\OODBBean
     */
    public function unbox(){
        return $this->bean;
    }



    
    
    //===============       Bun Utils        ===============//
    /**
     * Defines the bean type using the class basename
     * 
     * @return string
     */
    public function beantype(){
        if($this->beantype) return $this->beantype;
        if($class = (new \ReflectionClass($this))->getShortName()){
            $type = strtolower($class);
            $cut = explode('_', $type);
            $beantype = array_pop($cut);
            if(preg_match(self::VALID_BEAN_TYPE, $beantype)){
                return $this->beantype = $beantype;
            }
        }
        $this->error('Cannot detect bean type using class basename please set protected $beantype',[
            'classname'     =>      get_class($this),
            'class'         =>      $type
        ]);
        return '';
    }
    
    /**
     * Scan the folder containing the model for other models
     */
    private function setBeanlist(){
        self::$beanlist[$this->beantype()] = get_class($this);
        
        if($filename = (new \ReflectionClass($this))->getFileName()){
            //scan the dir for class files
            $dir = dirname($filename);
            foreach (scandir($dir) as $file){
                if(preg_match('/.php$/i', $file) and strlen($file) > 4){
                    include_once $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
        //scan class list for Manju\Bun
        //and initialize them
        $list = array_reverse(get_declared_classes());
        foreach ($list as $class){
            if($class == get_class($this)) continue;
            if(in_array(__CLASS__, class_parents($class))){
                new $class;
            }
        }
    }    



    //===============       Logger        ===============//
    
    /**
     * Sets a PSR-3 logger.
     * Can make static call
     * @param Psr\Log\LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger){
        self::$logger = $logger;
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function error($message, array $context = []){
        $this->log('error', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function debug($message, array $context = []){
        $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function log($level, $message, array $context = []){
        if(!self::$logger and class_exists("Manju\\Logger")){
            $this->setLogger(new Logger);
        }
        if(self::$logger) self::$logger->$level($message,$context);
    }
    
    
    

}
