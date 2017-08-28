<?php

namespace Manju;
use RedBeanPHP\SimpleModel;
use RedBeanPHP\OODBBean;
use RedBeanPHP\OODB;
use RedBeanPHP\Facade as R;
if (version_compare(PHP_VERSION, '7.0', '<')) {
    throw new Exception('This program needs php version > 7.0 to run correctly.');
}

abstract class Bun extends SimpleModel{
    
    const MANJU_VERSION = '1.0.alpha1';
    const ACCEPTED_BEAN_NAMES = '/^[^0-9][a-z0-9]+$/';
    const ACCEPTED_PROP_NAMES = '/^[^0-9_][a-z0-9_]+$/';
    public static $beanlist = [];


    
    /**
     * Type of the bean
     * @param string $bean
     */
    protected $beantype;
    
    
    /**
     * @var OODBBean
     */
    protected $bean;
    
    
    protected $properties = [];
    
    
    
    
    public function configure(){}

    


    public function __construct($bean = null) {
        BeanHelper::$registered or new BeanHelper;
        self::$beanlist or $this->beanlist();
        isset(self::$beanlist[$this->beantype()])?:self::$beanlist[$this->beantype()] = get_called_class();
        $bean = is_null($bean)?false:$bean;
        $this->initialize($bean);
    }
    
    public function __invoke($bean = null) {
        $this->initialize($bean);
        return $this;
    }
    
    
    private function initialize($bean = null){
        if(!R::testConnection()){
            $message = "Cannot connect to the database please run R::setup() before calling a model.";
            throw new Exception($message);
            exit;
        }
        if(is_bool($bean)) return;
        switch (gettype($bean)){
            case "integer":
                $this->load($bean);
                break;
            case "NULL":
                $this->create();
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
    
    
    public function beantype(){
        if($this->beantype) return $this->beantype;
        if($class = get_called_class()){
            $classpath = explode("\\", $class);
            $basename = array_pop($classpath);
            $basename = strtolower($basename);
            $basename = str_replace(['model','own','shared','_'], '', $basename);
        }
        if(!preg_match(self::ACCEPTED_BEAN_NAMES, $basename)){
            $message = sprintf('%s : invalid Bean Type please set "public static $beantype".', get_called_class());
            throw new Exception($message);
            exit;
        }
        return $this->beantype = $basename;
    }
    
    
    //=============== FUSE Methods ===============//
    
    public function dispense() {}
    public function open(){}
    public function update() {}
    public function after_update() {}
    public function after_delete() {}
    public function delete() {}
    
    
    public function __get($prop) {
        return $this->bean->$prop;
    }

    public function __isset($key) {
        return isset( $this->bean->$key );
    }

    public function __set($prop, $value): void {
        $this->bean->$prop = $value;
    }

    public function box() {
        return $this;
    }

    public function loadBean(OODBBean $bean){
        $this->bean = $bean;
    }

    public function unbox() {
        return $this->bean;
    }
    
    
    //=============== RedBeanAdapter ===============//
    
    public function load(int $id = 0){
        BeanHelper::$enabled = false;
        $bean = R::load($this->beantype(), $id);
        $bean->setMeta('model', $this);
        $this->bean = $bean;
        BeanHelper::$enabled = true;
        $this->open();
        return $this;
    }
    
    public function create(){
        BeanHelper::$enabled = false;
        $bean = R::dispense($this->beantype());
        $bean->setMeta('model', $this);
        $this->bean = $bean;
        BeanHelper::$enabled = true;
        $this->dispense();
        return $this;
    }

    
    
    
}
