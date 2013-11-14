<?php

/**
 * Class folders
 */
class folders {

	/**
	 * Root folder data
	 * @var array
	 */
	public static
		$root = array(
			"name"     => "Корень",
			"id"       => 0,
			"parentId" => false
		);

	public static function add($data) {

		$record = $data;

		$id = sky::$db->make("folders")
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();

		sky::$db->make("foldersRevisions")
			->set("folderId", $id)
			->set("name", $record["name"])
			->set("parentId", $record["folderId"])
			->set("modified", "", "now")
			->set("ownerId", auth::$me["id"])
			->insert();

		return $id;

	}

	public static function getAll() {

		# Get max revisions
		$join = sky::$db->make("foldersRevisions")
			->group("folderId")
			->records(array("MAX(id) as `id`", "folderId"))
			->get("query");

		# Get list
		return $folders = sky::$db->make("folders")
			->join("($join) as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get();

	}


	public static function getById($id) {

		if($id == 0)
			return self::$root;

		# Get max revisions
		$join = sky::$db->make("foldersRevisions")
			->group("folderId")
			->records(array("MAX(id) as `id`", "folderId"))
			->get("query");

		# Get list
		return $folder = sky::$db->make("folders")
			->join("($join) as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where($id)
			->limit(1)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get("single");

	}

	public static function getByParent($id) {

		# Get max revisions
		$join = sky::$db->make("foldersRevisions")
			->group("folderId")
			->records(array("MAX(id) as `id`", "folderId"))
			->get("query");

		# Get list
		$folders = sky::$db->make("folders")
			->join("($join) as temp", "temp.folderId = folders.id")
			->join("foldersRevisions", "foldersRevisions.id = temp.id")
			->where("temp.id", null, "!=")
			->where("foldersRevisions.parentId", $id)
			->records(array("foldersRevisions.*", "folders.owner", "folders.created"))
			->get();

		return $folders;

	}


}