<?php

class generalException extends Exception 
{
    public function __construct($message, $code=0)
    {
        parent::__construct($message,$code);
		$t = $this->getTrace();
 		$this->file = $t[0]['file'];
 		$this->line = $t[0]['line'];
    }    
    public function __toString()
    {
        return 'generalException: "'.$this->message.'" in file "'.$this->file.'" at line '.$this->line."\n";
    }
    
    public static function get($message = null,$code = null)
    {
		throw new generalException($message, $code);
    }
}