<?php
/**
 * PSR-4 autoloader
 * for running the package standalone
 */
spl_autoload_register(function($class){
    $path = sprintf("%s/%s.php", dirname(__DIR__), $class);
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
    if($path = realpath($path)){
        include $path;
    }
});

//composer
$composer = sprintf("%s/vendor/autoload.php", dirname(__DIR__));
if(file_exists($composer)) require_once $composer;
else{
    require_once __DIR__ . '/dist/rb.php';
}