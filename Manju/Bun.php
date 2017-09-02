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


class Bun extends SimpleModel{
    
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
 
    

    public function __construct() {
        $this->error('test error');
        var_dump(self::$logger);
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
    
    

    //===============       Logger        ===============//
    
    /**
     * Sets a PSR-3 logger.
     * @param LoggerInterface $logger
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
