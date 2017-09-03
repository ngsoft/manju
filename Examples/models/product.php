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
        $this->addRequired("brand");
    }
    
    public function dispense() {
        print __METHOD__.PHP_EOL;
    }
    public function open(){
        print __METHOD__.PHP_EOL;
    }
    public function update() {
        print __METHOD__.PHP_EOL;
    }
    public function after_update() {
        print __METHOD__.PHP_EOL;
    }
    public function delete() {
        print __METHOD__.PHP_EOL;
    }
    public function after_delete() {
        print __METHOD__.PHP_EOL;
    }
    

    
    
}
