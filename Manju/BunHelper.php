<?php

namespace Manju;

use RedBeanPHP\BeanHelper\SimpleFacadeBeanHelper;
use RedBeanPHP\OODBBean;
use RedBeanPHP\Facade as R;
use Exception;


class BunHelper extends SimpleFacadeBeanHelper{
    
    private static $caster;
    private static $connected;

    

    public static function connect(){
        if(is_bool(self::$connected)) return self::$connected;
        try {
            self::$connected = self::$connected?:R::testConnection();
            if(!self::$connected){
                throw new Exception("Cannot connect to the database please run R::setup() before calling a model.");
            }
        }
        catch (Exception $ex) {
            print $ex->getMessage() . PHP_EOL;
            exit(1);
        }
    }


    
    
    
    public static function register(){
        if(R::getRedBean()->getBeanHelper() instanceof self) return;
        R::getRedBean()->setBeanHelper(new self);
    }
    
    public static function unregister(){
        R::getRedBean()->setBeanHelper(new SimpleFacadeBeanHelper);
        
    }

    
    
    public static function dispense(Bun $model){
        self::setCaster($model);
        return R::dispense($model->beantype());
    }
    
    
    public static function load(Bun $model, int $id){
        self::setCaster($model);
        $beantype = $model->beantype();
        return R::load($beantype, $id);
    }
    
    private static function setCaster(Bun $model){
        self::$caster = $model;
    }
    
    private static function unsetCaster(){
        self::$caster = null;
    }
    
    private static function getCaster(){
        return self::$caster;
    }
    
    
    /**
     * Overrides RedBeanPHP default behavior
     * @param OODBBean $bean
     * @return Manju\Bun | RedBeanPHP\SimpleModel | null
     */
    public function getModelForBean(OODBBean $bean) {
        $model = $bean->getMeta( 'type' );
        if(self::getCaster()){
            if($type == self::getCaster()->beantype()){
                $obj = self::getCaster();
                $obj->loadBean($bean);
            }
            self::unsetCaster();
        }
        elseif(isset(Bun::$beanlist[$model])){
            $obj = self::factory( Bun::$beanlist[$model] );
            $obj->loadBean( $bean );
            return $obj;
        }
        if(isset($obj)) return $obj;
        return parent::getModelForBean($bean);
    }
    
}