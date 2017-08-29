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
     * New column to be added as a way to record the php scalar type
     * to retrieve the corresponding typed data
     * 
     * @var string 
     */
    protected $scalar_type_suffix = '_phptype';
    
    
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

        













    const MANJU_VERSION = '1.0.alpha1';
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
    protected $properties = [];
    
    
    
    /**
     * Model Construct method
     */
    public function configure(){}

    


    public function __construct($bean = null) {
        BeanHelper::$registered or new BeanHelper;
        self::$beanlist or $this->beanlist();
        isset(self::$beanlist[$this->beantype()])?:self::$beanlist[$this->beantype()] = get_called_class();
        $bean = is_null($bean)?false:$bean;
        $this->configure();
        $this->initialize($bean);
        
    }
    
    public function __invoke($bean = null) {
        $this->initialize($bean);
        return $this;
    }
    
    
    private function initialize($bean = null){
        
        $this->cansave = true;
        $this->properties = [];
        
        if(!self::$connected) self::$connected = R::testConnection();
        if(!self::$connected){
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
    
    
    //=============== FUSE Methods + RedBeanPHP\SimpleModel extended methods ===============//
    
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

    public function unbox() {
        return $this->bean;
    }
    
    
    //=============== RedBean CRUD ===============//
    
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
    public function loadBean(OODBBean $bean): Bun{
        $this->initialize(false);
        $this->bean = $bean;
        return $this;
    }


    
    
    /**
     * Create a new empty bean
     * @return $this
     */
    public function create(): Bun{
        //prevents double Manju\Bun instance
        BeanHelper::$enabled = false;
        $this->bean = null;
        $this->initialize(false);
        if($bean = R::dispense($this->beantype())){
            $this->takeown($bean);
        }
        BeanHelper::$enabled = true;
        $this->dispense();
        return $this;
    }
    
    /**
     * Loads a bean with the data corresponding to the id column
     * @param int $id id field of the bean
     * @return $this
     */
    public function load(int $id = 0): Bun{
        if(!$id) return $this->dispense ();
        //prevents double Manju\Bun instance
        BeanHelper::$enabled = false;
        $this->bean = null;
        $this->initialize(false);
        if($bean = R::load($this->beantype(), $id)){
            $this->takeown($bean);
        }
        BeanHelper::$enabled = true;
        if($this->id) $this->open();
        else $this->dispense ();
        return $this;
    }
    
    /**
     * Stores the bean into the database
     * @param bool $fresh Refresh the bean with saved data
     * @return $this
     */
    public function store(bool $fresh = false): Bun{
        if(!$this->bean or !$this->cansave) return $this;
        $this->initialize(false);
        R::store($this->bean);
        if($fresh) return $this->fresh ();
        return $this;
    }
    
    /**
     * Reloads data for the current bean from the database
     * @return $this
     */
    public function fresh(): Bun{
        $this->bean or $this->initialize ();
        return $this->load($this->id);
    }
    
    /**
     * Removes a bean from the database
     * @return $this;
     */
    public function trash(): Bun{
        if(!$this->bean) return $this;
        R::trash($this->bean);
        $this->bean = null;
        return $this;
    }
    
}
