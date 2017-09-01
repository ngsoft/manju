<?php

/**
 * Detect Composer
 */
try {
    $composer = "Composer\\Autoload\\ClassLoader";
    if(!class_exists($composer)){
        $paths = [realpath(dirname(__DIR__)), realpath(dirname(dirname(dirname(__DIR__))))];
        foreach ($paths as $dir){
            if($dir){
                $filename = sprintf("%s/vendor/autoload.php", $dir);
                if(file_exists($filename)){
                    return include_once $filename;
                }
            }
        }
        throw new Exception("Composer not found, please run composer install");
    }
} catch (Exception $ex) {
    print $ex->getMessage();
    exit(1);
}

if(defined('example')){
    //use composer autoloader
    $l = new Composer\Autoload\ClassLoader;
    $l->addPsr4('Examples\\', dirname(__DIR__) . '/Examples/');
    $l->register();
}

