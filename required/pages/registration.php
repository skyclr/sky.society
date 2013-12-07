<?php

/**
 * Class page
 * Used for creation main page
 */
class page extends basePage {

	/**
	 * Page title
	 * @var string html
	 */
	public $title 	 = "Регистрация";

	public $parentTemplate = "simple";

	/**
	 * Page creation
	 */
	public function main() {


		try {

			if(vars::type() == "nextStep" || vars::type() == "registration") {

				# Get code
				if(!$code = vars::post("code", "trim"))
					throw new userErrorException("Не указан код активации");

				# Get user
				if(!$user = sky::$db->make("users")->where("code", $code)->where("activated", 0)->get("single"))
					throw new userErrorException("Код активации указан не верно");

				# If registration
				if(vars::type() == "registration") {
					$user = users::register($user["id"], $_POST);
					auth::authorisation($user, true);
					sky::goToPage("/");
				}

			}
		} catch(Exception $e) {
			if(!is_a($e, "userException"))
				info::error("Во время работы произошла ошибка, пожалуйста попробуйте позже");
		}

		# Render page
		$this->renderContent(array("user" => isset($user) ? $user : false));

	}

}