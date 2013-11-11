<?php

# Preferences class
include_once 'userPreferences.php';


/** 
 * Class to access user preferences 
 */
class userController implements arrayaccess {

	/**
	 * Main user info
	 * @var array
	 */
	private $userData = array();
	
	/**
	 * Holds additional user information
	 * @var array
	 */
	public $info = array();
	
	/**
	 * Keeps current user preferences class
	 * @var userPreferences
	 */
	private $preferences = false;

	/**
	 * Indicates if user logged and not guest
	 * @var bool
	 */
	public $isLoggedIn = false;

	/**
	 * User object construct
	 * @param array $userData User data to be based on
	 */
	public function __construct($userData) {


		# Fill arrays
		$this->userData = $userData;


		# Set info owner
		if(!empty(sky::$config['login']['userInfo'])) {
			$this->info["owner"] = $userData["id"];
			if($userData['id'] && $info = sky::$db->make(sky::$config["login"]["userInfo"])->where("owner", $userData["id"])->get("single"))
				$this->info = $info;
		}
		else
			$this->info = false;


		# Get logged flag
		$this->isLoggedIn = auth::isLoggedIn();

	}
	
	/**
	 * Saves additional information
	 * @throws systemErrorException
	 */
	public function saveInfo() {

		# If not available
		if(!$this->info)
			throw new systemErrorException("Can't save user information, because of it's not activated in config");
		
		# Info save
		sky::$db->make(sky::$config['login']['userInfo'])->set($this->info)->insert(true);
		
	}

	/**
	 * 
	 * Gets users info
	 * @return array user information
	 * @throws systemErrorException
	 */
	public function getInfo() {
	
		# Logged in check
		if(!Auth::isLoggedIn())
			throw new systemErrorException("Try to get info of logged out user");
		
		
		# If not available
		if(!$this->info)
			throw new systemErrorException("Can't save user information, because of it's not activated in config");
		
		
		# User info get
		if(!$info = sky::$db->make(sky::$config['login']['userInfo'])->where($this->userData['id'], "owner")->get("single"))
			throw new systemErrorException("Can't get user info");
		
		# Save
		return $this->info = $info;
		
	}

	/**
	 * Return current user data array
	 * @return array
	 */
	public function get() {
		return $this->userData;
	}
	
	/**
	 * Returns true if user logged in and he is admin(auth::$me['usertype'] == 'admin')
	 * @return boolean 
	 */
	public function isAdmin() {
		if(auth::isLoggedIn() && !empty($this->userData["usertype"]) && mb_strtolower($this->userData["usertype"]) == "admin") return true;
		else return false;
	}
	
	/**
	 * Initialize user preferences
	 * @param array $preferences
	 */
	public function setPreferences($preferences) {
		$this->preferences = new userPreferences($preferences);
	}

	/**
	 * Sets or gets user preference
	 * @param String $name  Name of preference to get/set
	 * @param Mixed  $value Value of preference, if not set then you wil get instead of set
	 * @param Bool   $save  Indicates should be preferences saved immediately
	 * @throws systemErrorException
	 * @return Mixed
	 */
	public function pref($name, $value = null, $save = true) {

		# Check if we have preferences
		if(!$this->preferences)
			throw new systemErrorException("Try to work with preferences, but they weren't init");
		
		# Operations
		if($value === null) return  $this->preferences->get($name);
		else				return	$this->preferences->set($name, $value, $save);
	
	}

	/**
	 * Saves changes in preferences
	 * @param Bool $currentOnly Indicates that only changes in current script should be saved
	 * @throws systemErrorException
	 */
	public function savePreferences($currentOnly = true) {
		
		# Check if we have preferences
		if(!$this->preferences)
			throw new systemErrorException("Try to save preferences, but they wern't init");
		
		# Save
		$this->preferences->save($currentOnly);
		
	}
	
	/**
	 * Saves current user data
	 */
	public function save() {
		
		# If nothing to change
		if(empty(sky::$config['login']['changeable']))
			return;
		
		
		# Changes list
		$changes = array();
		
		
		# Compile
		foreach(sky::$config['login']['changeable'] as $change)
			$changes[$change] = $this->userData[$change];
		
		
		# Update records
		sky::$db->make(auth::$usersTable)->where($this->userData['id'])->set($changes)->update();
		
	}

	# Sets offset
	public function offsetSet($offset, $value) {
        if (is_null($offset)) {
			$_SESSION[] = $value;
			$this->userData[] = $value;
		}
        else {
			$_SESSION[$offset] = $value;
			$this->userData[$offset] = $value;
		}
    }
    
    # Checks of element exists
    public function offsetExists($offset) {
        return isset($this->userData[$offset]);
    }
    
    # Unset element
    public function offsetUnset($offset) {
        unset($this->userData[$offset]);
    }
    
    # Get data with current offset
    public function offsetGet($offset) {
        return isset($this->userData[$offset]) ? $this->userData[$offset] : null;
    }
	
}