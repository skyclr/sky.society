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
	 * User groups list
	 * @var array
	 */
	public $groups = array();

	/**
	 * User object construct
	 * @param array $userData User data to be based on
	 */
	public function __construct($userData) {


		# Set allowed operations
		$userData["allowed"]["sms"]		= (bool)$userData['sms_allowed'];
		$userData["allowed"]["dispatch"]= (bool)$userData['sms_allowed'];
		$userData["allowed"]["smsApi"]	= $userData['smsapi_allowed'];
		$userData["allowed"]["pseudo"]	= (bool)($userData['direct_pseudo_subs'] || $userData['ifree_pseudo_subs']);
		$userData["allowed"]["subs"]	= (bool)($userData['subscriptions0_allowed'] || $userData['subscriptions_direct_allowed']);
		$userData["allowed"]["commerce"]= (bool)($userData['mcommerce_allowed']);


		# Get support
		if($userData["support"] > 0)
			$userData["support"] = sky::$db->make("site_admin")->where($userData["support"])->get("single");


		# Fill arrays
		$this->userData = $userData;


		# Get user groups
		$this->getUserGroups($userData);


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
	 * Sends email
	 * @param $subject
	 * @param $text
	 */
	public function email($subject, $text) {
		//TODO::write code here
	}

	/**
	 * Gets list of user groups
	 * @param $userData
	 * @return bool
	 */
	public function getUserGroups($userData) {


		# Base group
		$groups = array("all");


		# Check other groups only if user active
		if($userData['active'] == 1) {

			# Including messages for users with I-Free subscriptions
			if($userData['subscriptions0_allowed'] == 1)
				$groups[] = 'subs';


			# Including messages for users with Direct subscriptions
			if($userData['subscriptions_direct_allowed'] == 1)
				$groups[] = 'direct';


			# Including messages for users with Pseudo
			if($userData['direct_pseudo_subs'] == 1)
				$groups[] = 'pseudo';


			# Including messages for users with Dispatch
			if(!empty($userData['smsapi_allowed']))
				$groups[] = 'dispatch';


			# Selecting active projects
			if(sky::$db->make("site_projects")->where("user_id", $userData['id'])->where("active", 1)->records("COUNT(*)")->get("value"))
				$groups[] = 'active';


			# Get spec project counters
			if($projectCounts = sky::$db
				->make("site_projects")->where("user_id", $userData['id'])->where("active", 1)->where("spec", array(1, 2, 4, 5, 9))
				->records(array("count(*) as counter", "spec"))->group("spec")->get()) {

				# Go through counters
				foreach($projectCounts as $counter) {
					if($counter['counter'] < 1) continue;
					if($counter["spec"] == 1) $groups[] = "ifree";
					if($counter["spec"] == 2) $groups[] = "a1";
					if($counter["spec"] == 4) $groups[] = "project-direct";
					if($counter["spec"] == 5) $groups[] = "project-streamline";
					if($counter["spec"] == 9) $groups[] = "zero";
				}

			}

		}


		# Save groups to self
		return $this->groups = $groups;

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
			$this->userData[] = $value;
		}
        else {
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