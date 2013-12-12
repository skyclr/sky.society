<?php

/**
 * Class comments
 */
class comments {

	/**
	 * Gets number of unread messages
	 * @return Mixed
	 */
	public static function getUnreadCount() {

		# Get number of comments
		return sky::$db->make("comments")
			->records("count(*)")
			->join("permissions", "permissions.resourceId = comments.resourceId AND permissions.resourceType = comments.resourceType")
			->where("permissions.permission")
			->get("value");

	}

	/**
	 * Get new comments
	 * @param int $offset
	 * @return Mixed
	 */
	public static function getNew($offset = 0) {


		# Prepare request
		$request= sky::$db->make("comments")
			->join("users", "comments.ownerId = users.id")
			->records(array("comments.*", "users.name", "users.lastname", "users.username", "users.hasAvatar", "users.avatarExtension"))
			->order("created");


		# Set page offset
		$request
			->limit(auth::$me->pref("perPage"))
			->offset($offset);


		# Get comments list
		$comments = $request->get();


		# Compile
		foreach($comments as $i => $comment) {
			//TODO:: add permission check
		}


		# Compile
		foreach($comments as $i => $comment)
			$comments[$i] = self::compile($comment);


		# Return
		return $comments;

	}

	/**
	 * Gets comments list for resource
	 * @param int $id Resource id
	 * @param string $type Resource type
	 * @param int $offset PAge offset
	 * @return Mixed
	 */
	public static function get($id, $type, $offset = 0) {


		# Prepare request
		$request= sky::$db->make("comments")
			->where("resourceId", $id)
			->where("resourceType", $type)
			->join("users", "comments.ownerId = users.id")
			->records(array("comments.*", "users.name", "users.lastname", "users.username", "users.hasAvatar", "users.avatarExtension"))
			->order("created");


		# Set page offset
		$request
			->limit(auth::$me->pref("perPage"))
			->offset($offset);


		# Get comments list
		$comments = $request->get();


		# Compile
		foreach($comments as $i => $comment)
			$comments[$i] = self::compile($comment);


		# Return
		return $comments;

	}


	/**
	 * Creates new comment
	 * @param array $data New comment data
	 * @return Int
	 */
	public static function add($data) {

		# Data
		$data = validator::init($data)
			->rule("type", "trim", "Неверно указано к чему добавить комментарий")
			->rule("id", "positive", "Неверно указано к чему добавить комментарий")
			->rule("text", "trim", "Не указан текст комментария")
			->get();


		# Add
		$id = sky::$db->make("comments")
			->set("text", $data["text"])
			->set("resourceId", $data["id"])
			->set("resourceType", $data["type"])
			->set("ownerId", auth::$me["id"])
			->set("created", "", "now")
			->insert();


		# Return
		return self::compile(sky::$db->make("comments")->where($id)->join("users", "comments.ownerId = users.id")
			->records(array("comments.*", "users.name", "users.lastname", "users.username"))->get("single"));

	}

	/**
	 * Compiles comment
	 * @param $comment
	 * @return mixed
	 */
	public static function compile($comment) {

		/* Date convert*/
		$comment["created"] = AdvancedDateTime::make($comment["created"])->format(sky::DATE_TIME);

		# Return
		return $comment;

	}

}