<?php

/** Errors handler */
function errorHandler($errno, $errstr, $errfile, $errline) {
	switch ($errno) {
	    case E_ERROR	: $type = "php error";  break;
	    case E_PARSE	: $type = "php parse"; 	break;
	    case E_STRICT	: $type = "php error";	break;
	    default: 		  $type = "php";		break;
    }

	baseException::log($errstr. ";\nLine: ".$errline.";\nFile: ".$errfile, $type, true);
}


register_shutdown_function(function() {
	$error = error_get_last();
	if($error === null) return;
	else baseException::log($error["message"]. ";\nLine: ".$error['line'].";\nFile: ".$error['file'], "error", true);
});

set_error_handler ("errorHandler", E_ALL | E_STRICT);

/**
 * Base class for all exceptions
 * <b>Extends</b> Exception
 */
class baseException extends Exception {

	/**
	 * Indicates that we should mail exceptions
	 * @var bool
	 */
	public static $mail = true;

	/**
	 * Exception type
	 * @var string
	 */
	private $type = "error";

	/**
	 * Error code
	 * @var Integer
	 */
	protected $code = 1;

	/**
	 * Logs data to log file with full backtrace
	 * @param string $message  Adds message to messages stack
	 * @param string $type     Message type
	 * @internal param int $code Define which type of message adds to stack:<br/>
	 * <b>0</b> - good, <b>1</b> - error, <b>2</b> - no message will be putted to stack
	 */
	public static function log($message, $type = "other") {


		# Get log file path
		$filePath = sky::location('logs')."errorLog_".@date("d.m").".txt";


		# Try to create file if not exists
		if(!file_exists($filePath) && !touch($filePath)) return;


		# Get backtrace
		$backtrace = debug_backtrace();
		$traceData = "";


		# Format backtrace
		foreach($backtrace as $trace) {
			if(!empty($trace["line"]))
				$traceData .= "\t line: {$trace["line"]} \t file: {$trace["file"]}\n";
		}


		# If we need to show
		if(!empty(sky::$config['development']['traceExceptions']) && sky::$config['development']['traceExceptions'] == "screen")
			echo '<pre>'.@date("d.m.Y H:i")."\nException($type): $message".";\n$traceData</pre>";


		# Log
		error_log(@date("d.m.Y H:i")."\nException($type): $message".";\n$traceData\n\n", 3, $filePath);

	}

	/**
	 * Pushes error to stack
	 */
	public function toInfo() {

		$type = "error";
		if($this->type == "success" || $this->type == "notice") 
			$type = $this->type;
		
		info::add($this->getMessage(), "global", $type);
		
	}

	/**
	 * Redefine the exception so message isn't optional
	 * @param string       $message        Adds message to messages stack
	 * @param int|integer  $code           Define which type of message adds to stack:<br/>
	 * @param bool         $show           Defines if this message will be putted to error stack
	 * @param bool         $log            Defines if this message should be logged
	 */
    public function __construct($message, $code = 0, $show = true, $log = false) {
    
		
        # Make sure everything is assigned properly
        parent::__construct($message, $code);
		
		
        # Determines exception type
        switch($code) {
            case "0": $this->type = "success"; 	break;
            case "1": $this->type = "error"; 	break;
            case "2": $this->type = "other"; 	break;
            case "3":							break;
            case "4": $this->type = "fatal"; 	break;
            case "5": $this->type = "database"; break;
            default : $this->type = "notice";
        }
        
        
        # Logs exception data
        if($log) 
			baseException::log($message, $this->type);
        
                
        # Adds message to stack
        if($show) 
			$this->toInfo();
	     
    }

    /**
     * Custom string representation of object
     * @return string String class representation
     */
    public function __toString() {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

}



/* Advanced exception */


/** This exception class always logged and never showed to user */
class systemException extends baseException {  
    public function __construct($message, $code = 1) {
        parent::__construct($message, $code, false, true);    
    }
}

/** Exception on system error */
class systemErrorException extends systemException {
    public function __construct($message) {

        parent::__construct($message, 1);    
    }
}

/** Exception on system notice error */
class systemNoticeException extends systemException {
    public function __construct($message) {
        parent::__construct($message, 1);    
    }
}    

/** System fatal exception */
class systemFatalException extends systemException {
    public function __construct($message) {
        parent::__construct($message, 4);    
    }
}        

/** Class of exception which throws database messages */
class databaseException extends systemException {
	# Redefine the exception so message isn't optional
    public function __construct($message) {
        parent::__construct($message, 5);

	}
}

/** Exception by user fault */
class userException extends baseException {
    public function __construct($message, $type, $show = true)  {
       parent::__construct($message, $type, $show, false);
    }
}

/** Error exception by user fault */
class userErrorException extends userException {
    public function __construct($message, $show = true) {
       parent::__construct($message, 1, $show);
    }
}

/** Notice exception by user fault */
class userNoticeException extends userException {
    public function __construct($message, $show = true) {
       parent::__construct($message, 3, $show);
    }
}

/** Exception for authorization */ 
class userAuthorisationException extends userException {
    public function __construct($message = "Вы должны войти в систему", $show = true) {
       parent::__construct($message, 1, $show);
    }
}
