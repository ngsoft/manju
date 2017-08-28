<?php

spl_autoload_register(function($class){
    $path = sprintf("%s/%s.php", dirname(__DIR__), $class);
    $path = str_replace("\\", DIRECTORY_SEPARATOR, $path);
    if($path = realpath($path)){
        include $path;
    }
});