<?php

namespace Manju;
use Monolog\Logger as Monolog;
use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\ErrorHandler;
use Monolog\Handler\NullHandler;


/**
 * Constants used
 */
@define('MANJU_LOG_FILE', null);
@define('MANJU_DEBUG', false);

/**
 * Logger needs Monolog
 * dependency check
 */

if(!class_exists('Monolog\\Logger') or !class_exists("Psr\\Log\\LoggerInterface")){
    return;
}

/**
 * Sends and gets logs from monolog
 */
class Logger extends AbstractProcessingHandler implements LoggerInterface, \JsonSerializable{
    
    use \Psr\Log\LoggerTrait;
    
    const LOG_FORMAT = '[%datetime% | %channel% | %level_name%] %message%';
    
    private static $monolog;
    private static $setup = false;
    private static $debug = false;
    private static $logfile;
    private $log = [];
    private $timeline = [];
    private $ishandler = false;
    
    
    public static function setDebug(bool $flag = false){
        self::$debug = $flag;
        self::setup();
    }
    
    public static function setLogFile(string $filename){
        self::$logfile = $filename;
        self::setup();
    }


    private static function setup(){
        self::$debug = self::$debug?:MANJU_DEBUG;
        self::$logfile= self::$logfile?:MANJU_LOG_FILE;
        $handlers = [];
        if(self::$logfile){
            $stream = new StreamHandler(self::$logfile);
            $stream->setFormatter(new LineFormatter(self::LOG_FORMAT . PHP_EOL,DateTime::DB));
            $handlers[] = $stream;
        }
        //R::debug(true)
        if(self::$debug) $handlers[] = new self(true);
        if(count($handlers)){
            self::$monolog = new Monolog('Manju');
            foreach ($handlers as $handler){
                self::$monolog->pushHandler($handler);
            }
            ErrorHandler::register(self::$monolog);
            set_error_handler(null);       
        }
        self::$setup = true;
    }

    public function log($level, $message, array $context = array()){
        
        if(!self::$monolog and !self::$setup){
            self::setup();
        }
        if(self::$monolog) self::$monolog->$level($message,$context);
    }

    public function __construct(bool $ishandler = false) {
        $this->ishandler = $ishandler;
        if($ishandler){
            parent::__construct(Monolog::DEBUG, true);
            $this->setFormatter(new LineFormatter(self::LOG_FORMAT,'H:i:s'));
        }
    }
    
    protected function write(array $record){

        $this->log[$record['channel']][$record['level_name']] = [
            'date'      =>  $record['datetime']->format(DateTime::DB),
            'message'   =>  $record['message'],
            'context'   =>  $record['context'],
            'extra'     =>  $record['extra']
        ];
        
        $this->timeline[] = $record['formatted'];
    }
    
    public function close(){
        if(!self::$debug) return;
        if($this->ishandler and $this->log){
            ksort($this->log);
            $this->log['timeline'] = $this->timeline;
            print $this;
        }
    }

    
    public function jsonSerialize() {
        return $this->log;
    }
    
    public function __toString() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

}
