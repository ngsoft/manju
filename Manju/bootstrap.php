<?php

/**
 * Detect Composer
 */
try {
    $composer = "Composer\\Autoload\\ClassLoader";
    if(!class_exists($composer)){
        $paths = [realpath(dirname(__DIR__))];
        foreach ($paths as $dir){
            if($dir){
                $filename = sprintf("%s/vendor/autoload.php", $dir);
                if(file_exists($filename)){
                    return include_once $filename;
                }
            }
        }
        throw new Exception("Composer not found, please run composer install where composer.json file is located.\n");
    }
} catch (Exception $ex) {
    print $ex->getMessage();
    exit(1);
}

