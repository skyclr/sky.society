<?php

class permissions {

	public static
		$full = array(
			"read" => true,
			"write" => true
		),
		$cache = array();

	/**
	 * Gets folder permissions
	 */
	public static function forFolder($folder) {


		# Get folder data
		if(!is_array($folder))
			$folder = userFolders::getById($folder);


		# Return full
		if($folder["folderId"] == 0)
			return self::$full;


		# Get permissions
		if(!$permissions = sky::$db->make("permissions")
			->where("resourceType", "folder")
			->where("resourceId", $folder["folderId"]))
			$permissions = array();


		# Get parent permissions
		$parent = self::forFolder($folder["parentId"]);


		# Count total
		return self::compile(array_merge($parent, $permissions));

	}

	/**
	 * Compiles permissions
	 * @param $permissions
	 * @return array
	 */
	private static function compile($permissions) {


		# Compiled result
		$result = self::$full;


		# Go through
		foreach($permissions as $permission) {
			if(self::canApply($permission)) {
				foreach($permission["permission"] as $name => $p)
					if($p == false)
						$result[$name] = false;
			}
		}


		# Return
		return $result;

	}

	/**
	 * returns true if rule can be applied for current user
	 * @param array $permission Permission data
	 * @return bool
	 */
	public static function canApply($permission) {

		# Specified users only
		if($permission["restrictionType"] == "user") {
			if(auth::$me["id"] != $permission["restrictionId"])
				return false;
		}

		# Specified users only
		if($permission["restrictionType"] == "group") {
			if(auth::$me->inGroup($permission["restrictionId"]))
				return false;
		}

		# BAse restrictions are appliable
		return true;

	}

}