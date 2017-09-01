<?php

namespace Manju;
use Exception;

@define('MANJU_LOCALE', 'en-US');
@define('MANJU_ENCODING', 'UTF-8');
@define('MANJU_MESSAGES_PATH', __DIR__ . '/messages');


class Translator {
    
    /**
     * Store the messages
     * @var array
     */
    private static $messages = [];
    
    /**
     * Store the locale
     * @var string 
     */
    private static $locale = 'en-US';
    
    private static $encoding = 'UTF-8';
    
    private static $path = __DIR__ . '/messages';

    /**
     * Get the translated message
     * @param string $message
     * @param string $arg1, $arg2 ... message arguments
     */
    public static function getMessage(string $message):string{
        self::$messages or self::initialize();
        $string = '';
        
        $args = func_get_args();
        $messagename = $args[0];
        
        if(isset(self::$messages[$messagename])){
            $args[0] = self::$messages[$messagename];
            $string = call_user_func_array('sprintf', $args);
        }
        return $string;
    }
    
    
    private function initialize(){
        self::$locale = defined('MANJU_LOCALE')?MANJU_LOCALE:self::$locale;
        self::$encoding = defined('MANJU_ENCODING')?MANJU_ENCODING:self::$encoding;
        self::$path = defined('MANJU_MESSAGES_PATH')?MANJU_MESSAGES_PATH:self::$path;
        
        $filename = self::$path . DIRECTORY_SEPARATOR . self::$locale . '.php';
        try {
            if(!file_exists($filename)){
                $message = sprintf("%s cannot find translation for locale %s, file '%s' not found\n", __CLASS__, self::$locale, $filename);
                throw new Exception($message);
            }
            self::$messages = include $filename;
            
        } catch (Exception $ex) {
           print $ex->getMessage() . $ex->getTraceAsString();
           exit(1);
        }
    }
    
    
    
    
    
    
}
