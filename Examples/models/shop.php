<?php

namespace Examples\models;

class shop extends \Manju\Bun{
    
    
    protected function configure() {
        $this   ->addAlias('products', 'ownProductList')
                ->addAlias('p', 'prods')
                ->addAlias('prods', 'products')
                ->addRequired('fullname')
                ->addAlias('name', 'fullname')
                ->addRequired('city');
                
    }
    
    protected function set_city($city){
        $this->cansave = true;
        if(!preg_match('/^[a-z\ ]+$/i', $city)){
            $this->cansave = false;
        }
        return $city;
    }
    
    protected function get_city($city){
        $words = explode(" ", $city);
        foreach ($words as &$word){
            $word = ucfirst(strtolower($word));
        }
        $city = implode(" ", $words);
        return $city;
    }
    
    
    
}
