<?php

require_once dirname(__DIR__) . '/Manju/autoloader.php';
R::setup(sprintf('sqlite:%s',__DIR__ . '/data/example.db'));
R::debug(true);



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
/*
$p->t = new Obj;
$p->price = 299.99;
$p->store();*/

//$p(2)->t->unserialized = "updating works";

//$p->store();

//print_r ($p->t);




print_r($p(2)->t);