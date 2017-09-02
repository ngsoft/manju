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
    
    
    final public function __construct() {
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
        if($this->savetimestamps){
            foreach ([MANJU_CREATED_COLUMN, MANJU_UPDATED_COLUMN] as $prop){
                $this->setColumnType($prop, 'datetime');
            }
        }
        
        //set defaults values to bean using the filter
        foreach (self::$defaults[get_called_class()] as $prop => $val){
            if(is_null($this->bean->$prop)){
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
    
    
    //===============       RedBeanPHP\SimpleModel Overrides        ===============//



    public function __get($prop) {
        return parent::__get($prop);
    }

    public function __isset($key) {
        return parent::__isset($key);
    }

    public function __set($prop, $value){
        parent::__set($prop, $value);
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
     * Used by BeanHelper to import a bean into SimpleModel
     * @param OODBBean $bean
     * @return $this
     */
    public function loadBean(OODBBean $bean){
        $this->initialize(false);
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
