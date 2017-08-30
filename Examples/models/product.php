<?php

namespace Examples\models;

class product extends \Manju\Bun{
    
    protected $savetimestamps = true;
    
    protected function configure() {
        $this   ->addCol('test', 'object')
                ->addAlias('t', 'test')
                ->addAlias('s', 'shop');
        $this->addPlugin(new \Obj());
        $this->plugins()->obj->test = "can access property like that.";
    }
    

    
    
}