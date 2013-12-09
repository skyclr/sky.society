<?php

class messages {

	public static function add($data) {


		# Validate
		$data = validator::init($data)
			->rule("id", "positive", "Не указан получатель сообщения")
			->rule("message", "trim", "Не указан текст сообщения")
			->get();


		# Add new message
		return sky::$db->make("messages")
			->set("to", $data["id"])
			->set("from", auth::$me["id"])
			->set("created", "", "now")
			->set("message", $data["message"])
			->insert();

	}

	public static function getUnreadCount() {

		# Get number of messages
		return sky::$db->make("messages")
			->where("to", auth::$me["id"], "private")
			->where("read", 0)
			->records("COUNT(*)")
			->get("value");

	}

	public static function getList() {

		# Prepare result
		$result = array("pages" => 0, "list" => array());


		# Join
		$join = sky::$db->make("messages")
			->where("to", auth::$me["id"], "private")
			->where("from", auth::$me["id"], "OR", "private")
			->records(array("MAX(id) as max", "IF(`to` = '" . auth::$me["id"] . "', `from`, `to`) as `opponent`"))
			->group("opponent")
			->get("query");


		# Get list
		if(!$list = sky::$db->make("$join as `m`")
			->join("messages", "m.id = messages.id")
			->get())
			return $result;


		# compile
		foreach($list as $message)
			$result["list"][] = self::compile($message);


		# Return
		return $result;

	}

	private static function compile($message) {
		return $message;
	}

}