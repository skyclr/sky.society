<?php

/**
 * Class folders
 */
class userFolders {

	public static
		/**
		 * Max revision query
		 * @var string
		 */
		$maxRevisionJoin = "SELECT MAX(`id`) as `id`, `folderId` FROM `foldersRevisions` GROUP BY `folderId` DESC";

	/**
	 * Root folder data
	 * @var array
	 */
	public static
		$root = array(
		"name"     => "Все",
		"id"       => 0,
		"parentId" => false,
		"folderId" => 0
	);

	/**
	 * Creates new folder
	 * @param array $data New folder data
	 * @return array|Mixed
	 */
	public static function add($data) {

		$record = $data;

		$id = sky::$db->make("folders")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();

		sky::$db->make("foldersRevisions")
			->set("revision", versionControl::getRevision())
			->set("folderId", $id)
			->set("name", $record["name"])
			->set("parentId", $record["parentId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->insert();

		return self::getById($id);

	}

	public static function getPath($folder) {

		$path = array();


		if($folder["parentId"] === false)
			return array();

		if($folder["parentId"] == 0)
			return array(self::$root);

		$parent =  self::getById($folder["parentId"]);

		$path[] = $parent;

		$path = array_merge($path, self::getPath($parent));

		return array_reverse($path);

	}

	/**
	 * Gets folder by id
	 * @param $id
	 * @return array|Mixed
	 * @throws userErrorException
	 */
	public static function getById($id) {


		# Validation
		validator::value($id, "natural", "Неверно указан номер альбома");


		# Get root
		if($id == 0)
			return self::$root;


		# Get folder
		if(!$folder = sky::$db->make("folders")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where($id)
			->where("foldersRevisions.deleted", 0)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get("single"))
			throw new userErrorException("Указанного альбома не существует");


		# Return
		return self::compile($folder);

	}

	/**
	 * Gets folders by parent id
	 * @param int $id Parent folder id
	 * @return Mixed
	 */
	public static function getByParent($id) {

		# Get list
		$folders = sky::$db->make("folders")
			->join("(" . self::$maxRevisionJoin .") as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->join("users", "users.id = folders.owner")
			->where("temp.id", null, "!=")
			->where("foldersRevisions.deleted", 0)
			->where("foldersRevisions.parentId", $id)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created", "users.username"))
			->get();


		# Compiling
		foreach($folders as $i => $folder)
			$folders[$i] = self::compile($folder);


		# Return
		return $folders;

	}

	/**
	 * Changes specified album
	 * @param array $data New data
	 * @throws userErrorException
	 * @return array|Mixed
	 */
	public static function change($data) {

		$record = $data;


		# Get folder
		$folder = self::getById($data["folderId"]);


		# Check permission
		if($folder["owner"] != auth::$me["id"])
			throw new userErrorException("У вас нет права удалять этот альбом");

		# Add revision
		sky::$db->make("foldersRevisions")
			->set("revision", versionControl::getRevision())
			->set("folderId", $folder["folderId"])
			->set("name", $record["name"])
			->set("parentId", $folder["parentId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->insert();


		# Get changed
		return self::getById($folder["folderId"]);

	}

	/**
	 * Deletes specified folder
	 * @param int $id Folder id
	 * @throws userErrorException
	 * @return string
	 */
	public static function delete($id) {

		# Get folder
		$folder = self::getById($id);


		# Check permission
		if($folder["owner"] != auth::$me["id"])
			throw new userErrorException("У вас нет права удалять этот альбом");


		# Delete all files
		if($sub = self::getByParent($id)) {
			foreach($sub as $s)
				self::delete($s["folderId"]);
		}


		# Delete children
		if($files = userFiles::getByFolder($id)) {
			foreach($files as $file)
				userFiles::delete($file["fileId"]);
		}


		# Add revision
		sky::$db->make("foldersRevisions")
			->set("revision", versionControl::getRevision())
			->set("folderId", $id)
			->set("name", $folder["name"])
			->set("parentId", $folder["parentId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->set("deleted", 1)
			->insert();


		# Return
		return "Deleted";

	}

	/**
	 * Compiles
	 * @param $folder
	 */
	private static function compile($folder) {

		# Add thumb
		if($thumb = userFiles::getByFolder($folder["folderId"], true))
			$folder["thumb"] = $thumb[0]["thumb"];

		$folder["created"] = AdvancedDateTime::make($folder["created"])->format(sky::DATE_TIME);

		return $folder;

	}


}