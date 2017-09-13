<?php
use RedBeanPHP\Facade as R;
define('example',1);
require_once dirname(__DIR__) . '/Manju/bootstrap.php';

//use composer autoloader
$l = new Composer\Autoload\ClassLoader;
$l->addPsr4(sprintf('%s\\', basename(__DIR__)), __DIR__);
$l->register();
//=======================//


R::setup(sprintf('sqlite:%s',__DIR__ . '/data/example.db'));
//R::debug(true);

define('MANJU_DEBUG',true);
define('MANJU_TIMEZONE', "Europe/Paris");


class Obj implements Serializable{
    
    
    public $test = "it works";
    
    public $unserialized = "serialize works!";
    
    public function serialize(){
        return serialize($this->unserialized);
    }

    public function unserialize($serialized){
        $this->unserialized = unserialize($serialized);
        
    }

    
    
}

$p = new Examples\models\product(1);
$s = new Examples\models\shop();

//$p->t = new Obj;
/*
$p->brand = "Samsung";
$p->t->unserialized = [
    'setting new values works on store',
    'setting another',
    'serialzable'=>new Obj()
];
$p->price = 299.99;

$p->store();*/

//$p(3)->trash();
/*
print_r($p(3));
print_r($p->findAll());*/
/*
$s->p = $p(1);
$s->name = "Carrefour";
$s->city = "London";
$s->store();*/
/*
print_r($s(1)->getPlate('p')[1]->t);
print($s);
*/
$s->getCityStreet();
