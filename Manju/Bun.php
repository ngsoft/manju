<?php

namespace Manju;
use RedBeanPHP\SimpleModel;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;



/**
 * Constants used
 */
@define('MANJU_CREATED_COLUMN', 'created_at');
@define('MANJU_UPDATED_COLUMN', 'created_at');



class Bun extends SimpleModel{
    /**
     * Logger
     */
    use \Psr\Log\LoggerTrait;
    
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
     * Scallar typed properties converted from bean
     * @var $array
     */
    private $properties = [];
    
    /**
     * Link to logger
     * @var Manju\Logger 
     */
    private static $logger;










    /**
     * @var OODBBean
     */
    protected $bean;
    























    public function __construct() {
        $this->notice('test');
    }

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

    public function box() {
        return $this;
    }

    public function loadBean(OODBBean $bean){
        return parent::loadBean($bean);
    }

    public function unbox() {
        return $this->bean;
    }

    

    public function __invoke() {
        
    }

    public function __toString() {
        
    }

    
    protected function log($level, $message, array $context = []){
        if(!self::$logger){
            if(class_exists('Manju\\Loader')) self::$logger = new Logger();
        }
        if(self::$logger) self::$logger->$level($message,$context);
    }

    
    



    
}
