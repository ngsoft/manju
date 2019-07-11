<?php

namespace Manju;

use ArrayAccess,
    ArrayIterator,
    Countable,
    DateTime,
    InvalidArgumentException,
    IteratorAggregate,
    JsonSerializable,
    Psr\Log\LoggerInterface,
    RedBeanPHP\Facade,
    RedBeanPHP\Facade as R,
    RedBeanPHP\Logger,
    RedBeanPHP\OODBBean,
    RedBeanPHP\SimpleModel,
    ReflectionClass,
    Serializable,
    stdClass;
use const MANJU_CREATED_COLUMN,
          MANJU_UPDATED_COLUMN;

/**
 * Constants used
 */
@define('MANJU_CREATED_COLUMN', 'created_at');
@define('MANJU_UPDATED_COLUMN', 'updated_at');

/**
 * Bun
 * Extension to RedbeanPHP\SimpleModel using FUSE
 *
 *
 *
 * For use with IDE
 * @property int $id Bean ID
 *
 */
abstract class Bun extends SimpleModel implements IteratorAggregate, Countable, ArrayAccess, JsonSerializable {
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

    const VERSION = '1.5';

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
     * @var LoggerInterface
     */
    protected static $logger;

    /**
     * Bean
     * @var OODBBean
     */
    protected $bean;

    /**
     * Store the plugins
     * @var stdClass
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
    private static $valid_types = ["integer", "double", "boolean", "array", "object", "datetime"];

    /**
     * Aliases that can be set as types
     * @var array
     */
    private static $types_alias = [
        "int" => "integer",
        "float" => "double",
        "bool" => "boolean",
        "date" => "datetime"
    ];

    /**
     * Scallar typed properties converted from bean
     * @var $array
     */
    private $properties = [];

    /**
     * Does obj gets accessed?
     * @var bool
     */
    private $tainted = false;

    //=============== FUSE Methods that can be extended into models ===============//
    public function dispense() {

    }

    public function open() {

    }

    public function update() {

    }

    public function after_update() {

    }

    public function delete() {

    }

    public function after_delete() {

    }

    //=============== RedBeanPHP\OODBBean Method Access (FUSE) ===============//

    public function __call($method, $args) {
        $this->bean or $this->create();
        //basic setters getters
        if (\preg_match('/^(?P<act>get|set)(?P<prop>[A-Z][a-zA-Z0-9]+)$/', $method, $matches)) {
            //use camel to snake converter
            $prop = $this->bean->beau($matches['prop']);
            //remove first underscore
            $prop = substr($prop, 1);
            $prop = $this->getAliasTarget($prop);
            switch ($matches['act']) {
                case 'get':
                    if (count($args)) {
                        $this->debug("trying to overload getter method " . get_class($this) . "->$method() with an argument.");
                        throw new InvalidArgumentException("$method method don't accept arguments.", 0);
                    }
                    return $this->$prop;
                //break;
                case 'set':
                    if (count($args) != 1) {
                        $this->debug("trying to overload setter method " . get_class($this) . "->$method() with an invalid argument count.");
                        throw new InvalidArgumentException("$method method require one argument.");
                    } else $this->$prop = $args[0];
            }
            return $this;
        } elseif (!method_exists($this->bean, $method)) {
            $this->debug(sprintf("Trying to access unknown method %s->%s().", get_class($this), $method));
            //let OODBBean handle the error
        }
        return call_user_func_array([$this->bean, $method], $args);
    }

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
    public function __invoke($bean = null): bun {
        $this->initialize($bean);
        return $this;
    }

    /**
     * Creates or load a new bean
     * @param int $bean id of the bean
     * @param array $bean Array to import into the bean
     * @param null|bool $bean Do nothing (prevent loops)
     */
    public function __construct($bean = null) {
        $bean = is_null($bean) ? false : $bean;
        $this->initialize($bean);
    }

    /**
     * Defines if it't time to run $this->configure
     */
    private function _configure() {
        $class = get_class($this);
        if (!array_key_exists($class, self::$columns)) {
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
    private function initialize_bean() {
        $this->initialize(false);

        if ($this->savetimestamps) {
            foreach ([MANJU_CREATED_COLUMN, MANJU_UPDATED_COLUMN] as $prop) {
                $this->setColumnType($prop, 'datetime');
            }
        }

        //set defaults values to bean using the filter
        foreach (\array_keys(self::$defaults[\get_class($this)]) as $prop) {
            if (is_null($this->bean->$prop)) {
                $this->$prop = null;
            }
        }

        //initialize required values to null into the bean
        foreach ($this->getRequiredCols() as $prop) {
            if (is_null($this->bean->$prop)) {
                $this->bean->$prop = null;
            }
        }
    }

    /**
     * Class constructor
     * @param mixed $bean
     */
    private function initialize($bean = null) {
        if (BunHelper::connected()) {
            //reset properties
            $this->tainted = false;
            $this->properties = [];
            $this->cansave = true;
            self::$beanlist or $this->setBeanlist();
            $this->_configure();
        }
        if (is_bool($bean)) return;

        switch (gettype($bean)) {
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
     * @return stdClass
     */
    public function plugins() {
        if (!is_object(self::$plugins)) {
            self::$plugins = new stdClass();
        }
        return self::$plugins;
    }

    /**
     * Add a plugin accessible to all the models
     * @param type $instance Plugin Object
     * @param string $name name for the plugin to be accessed $this->plugins()->friendly_name If not set will use lowercase class basename
     * @return $this
     */
    public function addPlugin($instance, string $name = null) {
        if (!is_object($instance)) {
            return $this;
        }
        if (!$name) {
            $name = (new ReflectionClass($instance))->getShortName();
            $name = strtolower($name);
        }
        $this->plugins()->$name = $instance;
        return $this;
    }

    //===============       Bun       ===============//

    /**
     * Set type for column
     * @param string $prop Column name
     * @param string $type valid php scallar type
     * @return bool
     */
    protected function setColumnType(string $prop, string $type): bool {
        //type is alias?
        if (isset(self::$types_alias[$type])) $type = self::$types_alias[$type];
        //type exists?
        if (!in_array($type, self::$valid_types)) {
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
    protected function getColumnType(string $prop) {
        return isset(self::$columns[get_class($this)][$prop]) ? self::$columns[get_class($this)][$prop] : null;
    }

    /**
     * Set default value for a column
     * @param string $prop Column name
     * @param any $defaults default value (can be a callable)
     * @return bool
     */
    protected function setColumnDefaults(string $prop, $defaults): bool {
        if (is_null($defaults)) return false;
        self::$defaults[get_class($this)][$prop] = $defaults;
        return true;
    }

    /**
     * Get the declared default value for a column
     * @param string $prop
     * @return mixed
     */
    protected function getColumnDefaults(string $prop) {
        return (\array_key_exists($prop, self::$defaults[\get_class($this)]) ? self::$defaults[\get_class($this)][$prop] : null);
    }

    /**
     * Add alias to Bun
     * @param string $alias Alias to use
     * @param string $target Column or list to point to
     * @return $this
     */
    protected function addAlias(string $alias, string $target) {
        if ($alias == $target) {
            $this->debug("Trying to set alias $alias to $target in " . get_class($this));
        } else self::$alias[get_class($this)][$alias] = $target;
        return $this;
    }

    /**
     * Get the target from an alias
     * @param string $alias
     * @return string
     */
    protected function getAliasTarget(string $alias): string {

        $a = &self::$alias[get_class($this)];
        //try finding alias of alias
        while (isset($a[$alias])) {
            $alias = $a[$alias];
        }
        return $alias;
    }

    /**
     * Add a managed column
     * @param string $prop Column name
     * @param string $type Column type
     * @param type $defaults Column Default
     * @return $this
     */
    protected function addCol(string $prop, string $type = null, $defaults = null) {
        if (!is_null($type)) $this->setColumnType($prop, $type);
        if (!is_null($defaults)) $this->setColumnDefaults($prop, $defaults);
        if (is_null($type) and is_null($defaults)) {
            $this->debug("Trying to set a managed column with no type and no default value (one parameter must be set) in " . get_class($this));
        }
        return $this;
    }

    /**
     * Add a required column, if value is null on store(), store() will be cancelled
     * @param string $prop Column name
     * @return $this
     */
    protected function addRequired(string $prop) {
        self::$required[get_class($this)][] = $prop;
        return $this;
    }

    /**
     * Get list of required columns
     * @return array
     */
    protected function getRequiredCols(): array {
        return self::$required[get_class($this)];
    }

    /**
     * Check if all required columns are set
     * @return bool
     */
    protected function checkRequired(): bool {
        foreach ($this->getRequiredCols() as $prop) {
            if (is_null($this->bean->$prop)) {
                $this->debug("Required column $prop set to null value in " . get_class($this));
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
    protected function convertForGet(string $prop, $val) {

        if ($type = $this->getColumnType($prop)) {
            switch ($type) {
                case"integer":
                    $val = intval($val);
                    break;
                case "double":
                    $val = floatval($val);
                    break;
                case "boolean":
                    $val = boolval((int) $val);
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
    protected function convertForSet(string $prop, $val) {

        //datetime detection
        if ($val instanceof DateTime) {
            $val = $val->format(DateTime::DB);
            return $val;
        }
        if ($this->getColumnType($prop) == 'datetime') {
            if (is_int($val)) {
                $val = date(DateTime::DB, $val);
                return $val;
            }
            if (is_string($val)) {
                $dt = new DateTime($val);
                if ($value = $dt->format()) {
                    return $value;
                } else {
                    $this->debug("Value $val for column $prop seems to be an incorrect datetime value in " . get_class($this));
                    return null;
                }
            }
        }
        //as it's not declared we cannot retrieve the formated value except for the use of formatters
        if (!$this->getColumnType($prop)) {
            return $val;
        }

        $type = gettype($val);
        if ($type != $this->getColumnType($prop)) {
            $this->debug(sprintf("value type declared as %s for column $prop seems to be incorrect ( $type ) in %s", $this->getColumnType($prop), get_class($this)));
            return null;
        }
        switch ($type) {
            case"integer":
                break;
            case "double":
                $val = "$val";
                break;
            case "boolean":
                $val = $val ? 1 : 0;
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
    protected function updateTainted() {
        if (!$this->tainted) return;
        foreach ($this->properties as $prop => $val) {
            if (!$this->getColumnType($prop)) continue;
            if ($val instanceof DateTime) {
                $val = $this->convertForSet($prop, $val);
                $this->bean->$prop = $val;
                $this->debug("updating datetime value for $prop in " . get_class($this));
            } elseif ($this->getColumnType($prop) == gettype($val) and is_object($val)) {
                $this->debug("updating serializable object " . get_class($val) . " for prop $prop in " . get_class($this));
                $val = $this->convertForSet($prop, $val);
                $this->bean->$prop = $val;
            }
        }
    }

    /**
     * Add MANJU_CREATED_COLUMN and MANJU_UPDATED_COLUMN
     * @return type
     */
    private function addTimestamps() {
        if (!$this->savetimestamps) return;
        $date = date(DateTime::DB);
        $created = MANJU_CREATED_COLUMN;
        $updated = MANJU_UPDATED_COLUMN;
        $this->bean->$created = $this->bean->$created ?: $date;
        $this->bean->$updated = $date;
    }

    /**
     * Serialize and encode string to base 64
     * \Serializable Objects and arrays will be saved into that format into the database
     * @param type $value
     * @return string
     */
    public function b64serialize($value): string {
        if (is_object($value)) {
            if (!($value instanceof Serializable)) {
                $this->debug("trying to serialize unserializable objet " . get_class($value) . " in " . get_class($this));
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
    public function b64unserialize(string $str = null) {
        $obj = null;
        if (!empty($str)) {
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
    public function import(array $data) {
        if (!count($data)) return;

        if (isset($data['$id'])) $this->load($data['id']);
        else $this->create();

        foreach ($data as $prop => $val) {
            if (is_int($prop) or is_null($prop)) continue;
            //convert data
            $this->$prop = $val;
        }
    }

    /**
     * Export data from bean
     * @param bool $convert convert data using schema
     * @return array
     */
    public function export(bool $convert = true): array {
        $export = [];
        $this->bean or $this->create();
        $properties = array_merge($this->bean->getMeta('sys.orig'), $this->bean->getProperties());

        foreach ($properties as $prop => $val) {
            //owned/shared lists are array, they won't be exported
            if (is_array($val)) continue;
            //to one are beans
            if ($val instanceof OODBBean) continue;
            //we use the converter like this
            if ($convert) $export[$prop] = $this->$prop;
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
    public function create() {
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
    public function load(int $id = 0) {
        $this->bean = null;
        if ($id == 0) $this->create();
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
    public function trash() {
        if (!$this->bean) return $this;
        R::trash($this->bean);
        return $this->create();
    }

    /**
     * Reloads data for the current bean from the database
     * @return $this
     */
    public function fresh() {
        return $this->load($this->id);
    }

    /**
     * Stores the bean into the database
     * trigger $this->update() before writing into the database
     * trigger $this->after_update() after
     *
     * @param bool $fresh Refresh the bean with saved data
     * @return $this
     */
    public function store(bool $fresh = false) {
        if (!$this->bean or ! $this->cansave) {
            if (!$this->cansave) $this->debug("trying to store a bean with cansave flag set to false in " . get_class($this));
            else $this->debug("trying to store a non existing bean, store() process halted in " . get_class($this));
            return $this;
        }
        $this->updateTainted();
        $this->addTimestamps();
        if ($this->checkRequired()) {
            R::store($this->bean);
            if ($fresh) return $this->fresh();
            else $this->initialize(false);
        } else {
            $this->debug("Trying to store a bean with not all the required columns set in " . get_class($this) . "\\store(), process halted.");
        }
        return $this;
    }

    //===============       RedBeanPHP\SimpleModel Overrides        ===============//


    public function &__get($prop) {
        $this->bean or $this->create();
        $prop = $this->getAliasTarget($prop);
        $val = $this->bean->$prop;
        if ($prop == 'id') {
            return $val;
        }
        if (is_array($val) and preg_match(self::TO_MANY_LIST, $prop)) {
            $val = &$this->bean->$prop;
            return $val;
        }
        if ($val instanceof OODBBean) {
            if ($bun = $val->getMeta('model')) {
                return $bun;
            }
            return $val;
        }
        if ($val == null and array_key_exists($prop, $this->properties)) {
            $val = $this->properties[$prop];
        } elseif ($this->getColumnType($prop)) {
            $val = $this->convertForGet($prop, $val);
        }
        if (is_object($val)) {
            $this->tainted = true;
        }

        $method = "get_$prop";
        if (method_exists($this, $method)) {
            $val = $this->$method($val);
        }
        $this->properties[$prop] = $val;
        return $val;
    }

    public function __isset($prop) {
        $this->bean or $this->create();
        return isset($this->bean->$prop);
    }

    public function __set($prop, $val) {
        $this->bean or $this->create();
        $prop = $this->getAliasTarget($prop);
        if ($prop == 'id') return;
        //fix owned list not updating entries (NULL value)
        if (!in_array($prop, [MANJU_CREATED_COLUMN, MANJU_UPDATED_COLUMN])) {
            $this->addTimestamps();
        }
        $method = "set_$prop";
        if (method_exists($this, $method)) {
            $val = $this->$method($val);
        }
        if ($val instanceof Bun) {
            $val = $val->unbox();
        }
        if (preg_match(self::TO_MANY_LIST, $prop)) {
            if (is_object($val)) {
                if ($val instanceof OODBBean or $val instanceof SimpleModel) {
                    $this->bean->{$prop}[] = $val;
                    return;
                }
            }
            if (!is_array($val)) return;
            $this->bean->$prop = $val;
            return;
        }
        if (is_null($val) and $val = $this->getColumnDefaults($prop)) {
            if (is_callable($val)) {
                $val = $val();
            }
        }

        if ($ctype = $this->getColumnType($prop)) {
            if ($ctype != gettype($val) and $ctype != 'datetime') {
                $this->debug("Trying to set value with type " . gettype($val) . " configured as $ctype in " . get_class($this));
                return;
            }
            $this->properties[$prop] = $val;
            $val = $this->convertForSet($prop, $val);
        }
        //last check
        else {
            if (is_array($val) or is_object($val) or is_resource($val)) {
                $this->debug("Trying to set value for non managed column with a type of " . gettype($val) . " for the property $prop in " . get_class($this) . ", value changed to NULL");
                $val = null;
            }
        }
        $this->bean->$prop = $val;
    }

    public function __unset($prop) {
        if (!$this->bean) return;
        if (!is_null($this->bean->$prop)) {
            if (is_array($this->bean->$prop)) {
                $this->bean->$prop = [];
                return;
            }
        }
        $this->$prop = null;
    }

    /**
     * Used by bean FUSE to return the model
     * @return $this
     */
    public function box() {
        return $this;
    }

    /**
     * Used by BeanHelper/BunHelper to import a bean into SimpleModel and the same SimpleModel into the bean just before $this->dispense()
     * @param OODBBean $bean
     * @return $this
     */
    public function loadBean(OODBBean $bean) {
        $this->bean = $bean;
        $this->initialize_bean();
        return $this;
    }

    /**
     * Get the bean directly
     * @return OODBBean
     */
    public function unbox() {
        $this->bean or $this->create();
        return $this->bean;
    }

    //===============       RedBean SQL (shortcuts)        ===============//

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
    public function find(string $sql = null, array $bindings = []) {
        count(self::$columns) or $this->initialize(false);
        $r = [];
        if ($rb = R::find($this->beantype(), $sql, $bindings)) {
            $r = $this->createPlate($rb);
        }
        return $r ?: $rb;
    }

    /**
     * @see Facade::find
     * This variation returns the first bun only.
     *
     * @param string $sql      sql    SQL query to find the desired bean, starting right after WHERE clause
     * @param array  $bindings values array of values to be bound to parameters in query
     * @return Bun|RedbeanPHP\OODBBean
     */
    public function findOne($sql = null, array $bindings = []) {
        count(self::$columns) or $this->initialize(false);
        $r = null;
        if ($rb = R::findOne($this->beantype(), $sql, $bindings)) {
            $r = $rb->getMeta('model');
        }
        return $r ?: $rb;
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
     * @return array
     */
    public function findAll($sql = null, array $bindings = []) {
        count(self::$columns) or $this->initialize(false);
        $r = [];
        if ($rb = R::findAll($this->beantype(), $sql, $bindings)) {
            $r = $this->createPlate($rb);
        }
        return $r ?: $rb;
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
    public function exec($sql = null, array $bindings = []): int {
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
    public function getAll($sql = null, array $bindings = []): array {
        count(self::$columns) or $this->initialize(false);
        return R::getAll($sql, $bindings);
    }

    /**
     * Gets the last insert id
     * @return int
     */
    public function getInsertID(): int {
        count(self::$columns) or $this->initialize(false);
        return R::getInsertID();
    }

    //===============       Bun Utils        ===============//

    /**
     * Gets an array of Bun corresponding to a One to Many or Many to Many relationship
     * @param string $prop
     * @return array
     */
    public function getPlate(string $prop): array {
        $this->bean or $this->create();
        $prop = $this->getAliasTarget($prop);
        $return = [];
        if ($val = $this->bean->$prop) {
            if (is_array($val) and preg_match(self::TO_MANY_LIST, $prop)) {
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
    public function createPlate(array $data): array {
        $r = [];
        foreach ($data as &$bean) {
            if (!($bean instanceof OODBBean)) continue;
            //inputing the $id as the key can create some unexpected results
            if ($bun = $bean->getMeta('model') and $bun instanceof Bun) {
                $r[] = $bun;
            } else $r[] = $bean;
        }
        return $r;
    }

    /**
     * Defines the bean type using the class basename
     *
     * @return string
     */
    public function beantype() {
        if ($this->beantype) return $this->beantype;
        if ($class = (new ReflectionClass($this))->getShortName()) {
            $type = strtolower($class);
            $cut = explode('_', $type);
            $beantype = array_pop($cut);
            if (preg_match(self::VALID_BEAN_TYPE, $beantype)) {
                return $this->beantype = $beantype;
            }
        }
        $this->error('Cannot detect bean type using class basename please set protected $beantype', [
            'classname' => get_class($this),
            'class' => $type
        ]);
        return '';
    }

    /**
     * Scan the folder containing the model for other models
     */
    private function setBeanlist() {
        self::$beanlist[$this->beantype()] = get_class($this);

        if ($filename = (new ReflectionClass($this))->getFileName()) {
            //scan the dir for class files
            $dir = dirname($filename);
            foreach (scandir($dir) as $file) {
                if (preg_match('/.php$/i', $file) and strlen($file) > 4) {
                    include_once $dir . DIRECTORY_SEPARATOR . $file;
                }
            }
        }
        //scan class list for Manju\Bun
        //and initialize them
        $list = array_reverse(get_declared_classes());
        foreach ($list as $class) {
            if ($class == get_class($this)) continue;
            if (in_array(__CLASS__, class_parents($class))) {
                new $class;
            }
        }
    }

    //===============       Logger        ===============//

    /**
     * Sets a PSR-3 logger.
     * Can make static call
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger) {
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
    protected function error($message, array $context = []) {
        $this->log('error', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    public function notice($message, array $context = array()) {
        $this->log('notice', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return void
     */
    protected function debug($message, array $context = []) {
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
    protected function log($level, $message, array $context = []) {
        if (!self::$logger and class_exists("Manju\\Logger")) {
            $this->setLogger(new Logger);
        }
        if (self::$logger instanceof LoggerInterface) self::$logger->log($level, $message, $context);
    }

    //===============       Interfaces        ===============//
    //IteratorAgregate
    public function getIterator() {
        return new ArrayIterator($this->export());
    }

    //Countable
    public function count() {
        return count($this->export());
    }

    //ArrayAccess
    public function offsetExists($prop) {
        return $this->__isset($prop);
    }

    public function &offsetGet($prop) {
        $val = $this->__get($prop);
        return $val;
    }

    public function offsetSet($prop, $val) {
        $this->__set($prop, $val);
    }

    public function offsetUnset($propt) {
        $this->__unset($propt);
    }

    //JsonSerializable
    public function jsonSerialize() {
        $data = $this->export();
        $return = [];

        foreach ($data as $prop => $val) {
            if (is_object($val)) {
                if ($val instanceof JsonSerializable) $val = $val->jsonSerialize();
                else {
                    //try to get most values
                    $val = json_decode(json_encode($val), true);
                }
            }
            $return[$prop] = $val;
        }
        return $return;
    }

    //jsonable
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

}
