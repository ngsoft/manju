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
R::debug(true);

define('MANJU_DEBUG',true);


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

$p = new Examples\models\product();
//$s = new Examples\models\shop();

$p->t = new Obj;

$p->store();
print_r($p);