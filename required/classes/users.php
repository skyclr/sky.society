<?php

/**
 * Class users
 */
class users {

	public static function saveSettings($data) {

	}


	public static function register($id, $data) {


		# Validation
		$data = validator::init($data)
			->rule("username", "trim", "Вы не указали логин")
			->rule("password", "trim", "Вы не указали пароль")
			->get();


		# Check if already
		if($same = sky::$db->make("users")->where("username", $data['username'])->get())
			throw new userErrorException("Пользователь с таким именем уже зарегестрирован");


		# Update
		sky::$db->make("users")->where($id)
			->set("username", $data["username"])
			->set("password", $data["password"], "password")
			->set("activated", 1)
			->update();

		# Return user
		return sky::$db->make("users")->where($id)->get("single");

	}

}