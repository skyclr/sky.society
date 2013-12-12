<?php

/**
 * Class permissions
 */
class permissions {

	const
		WRITE = "w",
		EDIT  = "w",
		READ  = "r";

	/**
	 * @var array
	 */
	private static $cache = array(
		"folders" => array(),
		"files"   => array()
	);

	/**
	 * Gets folder permission for current user
	 * @param int $id Folder id
	 * @return array
	 */
	public static function getFilePermissions($id) {


		# If in cache
		if(isset(self::$cache["files"][$id]))
			return self::$cache["files"][$id];


		# Get data
		$permission  = self::getDefault();
		$file 	 	 = userFiles::getById($id);
		$permissions = sky::$db->make("permissions")->where("type", "file")->where("resourceId", $id)->get();


		# Compile
		if($permissions)
			$permission = self::compile($permissions);


		# get parent permissions
		$parent = self::getFolderPermissions($file["folderId"]);


		# Merge
		$permission = self::merge($permission, $parent);


		# Cache
		return self::$cache["folders"][$id] = $permission;

	}

	/**
	 * Gets folder permission for current user
	 * @param int $id Folder id
	 * @return array
	 */
	public static function getFolderPermissions($id) {


		# If in cache
		if(isset(self::$cache["folders"][$id]))
			return self::$cache["folders"][$id];


		# Get data
		$permission  = self::getDefault();


		# Get folder
		if($id != 0)
			$folder = userFolders::getById($id);
		else
			$folder = userFolders::$root;


		# Get permission
		$permissions = sky::$db->make("permissions")->where("type", "folder")->where("resourceId", $id)->get();


		# Compile
		if($permissions)
			$permission = self::compile($permissions);


		# Get parent permission
		if($folder["parentId"]) {

			# get parent permissions
			$parent = self::getFolderPermissions($folder["parentId"]);

			# Merge
			$permission = self::merge($permission, $parent);

		}


		# Cache
		return self::$cache["folders"][$id] = $permission;

	}

	/**
	 * Gets single permission form array of permissions of object
	 * @param array $permissions array of permissions data
	 * @return array
	 */
	public static function compile($permissions) {


		# Prepare result
		$result = self::getDefault();


		# Go through
		foreach($permissions as $permission) {
			if(self::canApply($permission))
				$result = self::merge($result, json_decode($permission["permission"], true));
		}


		# Return;
		return $result;

	}

	/**
	 * Checks if permission restriction should be applied for current user
	 * @param array $permission Permission data
	 * @return bool
	 */
	public static function canApply($permission) {

		switch($permission["permissionType"]) {
			case "group":
				return auth::$me->inGroup($permission["applyId"]);
			case "user":
				return auth::$me["id"] == $permission["applyId"];
			default: return true;
		}

	}

	/**
	 * Returns default permissions
	 * @return array
	 */
	public static function getDefault() {
		return array(
			self::WRITE => "true",
			self::READ  => "true",
		);
	}

	/**
	 * Merges two permissions
	 * @param array $what Base permission
	 * @param array $with Permission to add
	 * @return array Merged permission
	 */
	public static function merge($what, $with) {


		# Prepare result
		$result = array();


		# Go through
		foreach($what as $name => $p) {
			if(!empty($with[$name]))
				$result[$name] = $p;
		}


		# Return result
		return $result;

	}

	/**
	 * Check operation available according to permission
	 * @param array $permission Permission data
	 * @param string $what Operation
	 * @return bool
	 */
	public static function available($permission, $what) {
		return !empty($permission[$what]);
	}

}