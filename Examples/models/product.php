<?php

namespace Examples\models;


/**
 * @property object $test Description
 * @property object $t alias of $test
 * @property shop $s Shop
 * @property float $price Price
 * @property string $brand Brand
 */
class product extends \Manju\Bun{
    
    protected $savetimestamps = true;
    
    protected function configure() {
        $this   ->addCol('test', 'object')
                ->addAlias('t', 'test')
                ->addAlias('s', 'shop')
                ->addCol('price','float')->addRequired('price')
                ->addPlugin(new \Obj())
                ->addRequired("brand");
        $this->plugins()->obj->test = "can access property like that.";
        
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
