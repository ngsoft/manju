<?php

namespace Manju;

if(!defined('MANJU_TIMEZONE')) define ('MANJU_TIMEZONE', 'UTC');
if(!defined ('MANJU_TIMEFORMAT')) define ('MANJU_TIMEFORMAT', 'Y-m-d H:i:s');

class DateTime extends \DateTime implements \JsonSerializable, \Serializable{
    
    const DB = 'Y-m-d H:i:s';
    
    public $timestamp;
    
    




    public function __construct($time = 'now') {
        $tz = MANJU_TIMEZONE;
        date_default_timezone_set($tz);
        if(is_numeric($time)) $time = date (MANJU_TIMEFORMAT, $time);
        parent::__construct($time, new \DateTimeZone($tz));
        $this->timestamp = $this->getTimestamp();
    }
    
    
    public function format($format = null) {
        !$format and $format = MANJU_TIMEFORMAT;
        return parent::format($format);
    }
    
    public function __toString() {
        return $this->format();
    }
    
    public function jsonSerialize() {
        $a = [
            'timestamp'     =>  $this->getTimestamp(),
            'date'          =>  $this->format(),
            'timezone'      => $this->getTimezone()->getName()
        ];
        return $a;
    }
    
    
    public function serialize(){
        $args = [
            $this->getTimestamp()
        ];
        return serialize($args);
    }

    public function unserialize($serialized){
        $args = unserialize($serialized);
        call_user_func_array([$this, '__construct'], $args);
    }
}