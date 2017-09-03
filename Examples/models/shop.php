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
    
    /**
     * validator example
     * @param type $city
     * @return type
     */
    protected function set_city($city){
        //flag that prevent storing the bean
        $this->cansave = true;
        if(!preg_match('/^[a-z\ ]+$/i', $city)){
            $this->cansave = false;
            //logging
            $this->error("validation error for city in ".$this->beantype());
        }
        return $city;
    }
    
    /**
     * Formatter example
     * @param type $city
     * @return type
     */
    protected function get_city($city){
        $words = explode(" ", $city);
        foreach ($words as &$word){
            $word = ucfirst(strtolower($word));
        }
        $city = implode(" ", $words);
        return $city;
    }
    
    
    
}
