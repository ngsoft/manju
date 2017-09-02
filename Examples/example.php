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
//$s = new Examples\models\shop();
/*
$p->t = new Obj;
$p->price = 299.99;
$p->store();*/
/*
$p(2)->t->unserialized = "updating works";

$p->store();*/

//print_r ($p->t);




//print_r($p(2)->export());

//print $p(2);

//$p(2)->brand = 'Samsung';
//$p->store();
//print_r($p);

/*$s->name = 'Darty';
//$s->p[]=$p(2);
$s->store();
print_r($s);*/

//$s(1)->p = [];
//$s->store();
//$s(1)->p = $p(2);
//$s->store();
//$s(1)->p[] = $p(1);
//$s->store();
//$s(1)->xownProductList = [];
//$s->store();
//print json_encode($s(1)->getPlate('p'), JSON_PRETTY_PRINT);
//print json_encode($s, JSON_PRETTY_PRINT);

//$b = R::load('ownProduct',1);
//R::trash($b);
//print_r($b);


//print_r(RedBeanPHP\Facade::inspect('ownProduct'));

//print_r($s);

//$s(1)->p=$p(1);
//$s->store();

//print_r($s(1));
/*
foreach (R::findAll('product') as $p){
    $p = $p->box();
    $p->test;
    print_r($p);
} */


//$s(1)->p = $p(1);
//$s->store();
/*
$s(1)->p = [];
$s->store();
print_r($s);*/
