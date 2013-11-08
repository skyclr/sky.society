<?php

/**
 * Class for work with user preferences 
 */
class userPreferences {
    
    private $settings = false, $changes = false;

	/**
	 * This function initialised user preferences class
	 * @param Array $preferences Array of user preferences values
	 * @throws systemNoticeException
	 */
    function __construct($preferences) {
        
		# Check
        if(!is_array($preferences))
            throw new systemNoticeException("Настройки пользователя имеют неверный формат");
        
		# Save
        $_SESSION["preferences"] = $this->settings = $preferences;
        $this->settings = &$_SESSION["preferences"];
        
    }
    
    /**
     * Get preferences variable function 
     * @param String $name Name of user preferences variable to get
     * @return Mixed Null if variable not set or variable value otherwise
     */
    public function get($name) { 
        
        if (isset($this->settings[$name])) return $this->settings[$name];
        return null;
        
    }

	/**
	 * Saves current settings list
	 * @param Bool $current Indicates that only changes during this page should be saved
	 * @throws userErrorException
	 */
	public function save($current = false) {
		
		
		# auth check
		if(!auth::isLoggedIn()) {
			baseException::log("Save with no auth, user data:".var_export($_SESSION));
			throw new userErrorException("Вы должны быть авторизованы для этой операции"); 
		}
		
		
		# Current page changes
		if($current && !$this->changes) return;
		
		
		# Find changes
		$changes = $this->settings;
		unset($changes['id']);
		unset($changes['owner']);
		
		
		# Save settings
		sky::$db->make(sky::$config['login']['preferences'])->where("owner", auth::$me['id'])->set($changes)->update();
		
	}

	/**
	 * Sets preference variable
	 * @param String $name  Name of user preferences variable to set.
	 * @param Mixed  $value Value for variable
	 * @param bool   $save
	 * @return Bool True if value set and false otherwise
	 */
    public function set($name, $value, $save = true) {
        

		# If we have special class
		if(class_exists("userPreferencesAdvanced")) {

			# Check of setting correct
			if(method_exists("userPreferencesAdvanced", "checkCorrect") && !call_user_func(array("userPreferencesAdvanced", "checkCorrect"), $name, $value))
				return false;

			# Perform some other correction
			if(method_exists("userPreferencesAdvanced", "correction"))
				$this->settings[$name] = call_user_func(array("userPreferencesAdvanced", "correction"), $name, $value);
		
		} else {
			$this->settings[$name] = $value;
		}


        # Save preference data
        if($save && auth::isLoggedIn())
            sky::$db->make(sky::$config['login']['preferences'])->where(auth::$me['id'])->set($name, $value)->update();
        
		
		# Mark changes
		$this->changes = true;
		
			
		# Post processing
		if(class_exists("userPreferencesAdvanced") && method_exists("userPreferencesAdvanced", "postProcessing"))
			call_user_func(array("userPreferencesAdvanced", "postProcessing"), $name, $value);
		
		
        return true;
		
    }

}

