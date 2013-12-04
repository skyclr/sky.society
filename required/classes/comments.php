<?php

/**
 * Class comments
 */
class comments {

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
			->where("resourceType", $type);


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
			->rule("text", "trim", "Не указан текст комментария");


		# Add
		$id = sky::$db->make("comments")
			->set("text", $data["text"])
			->set("resourceId", $data["id"])
			->set("resourceType", $data["type"])
			->set("owner", auth::$me["id"])
			->set("created", "", "now")
			->insert();


		# Return
		return $id;

	}

	public static function compile($comment) {

		# Return
		return $comment;

	}

}