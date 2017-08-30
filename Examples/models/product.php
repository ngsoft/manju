<?php

namespace Examples\models;

class product extends \Manju\Bun{
    
    protected $savetimestamps = true;
    
    protected function configure() {
        $this   ->addCol('test', 'object')
                ->addAlias('t', 'test')
                ->addAlias('s', 'shop');
    }
    

    
    
}
