<?php

/**
 * Class page
 * Used for creation main page
 */
class page extends basePage {

	/**
	 * Page title
	 * @var string|boolean
	 */
	public $title 	 = "тайный Санта";

	/**
	 * Page creation
	 */
	public function main() {

		try {

			if(vars::type() == "decision") {

				# Decision check
				if(!$decision = vars::post("decision" ,"trim"))
					throw new userErrorException("Вы не сделали выбор");

				# Insert/update
				sky::$db->make("santa")->set("decision", $decision)->set("owner", auth::$me["id"])->insert(true);

			}

		} catch(Exception $e) {
			if(!is_a($e, "userException"))
				info::error("Во время работы произошла ошибка, сообщите о ней");
		}


		# Get santa
		$santa = $this->getSanta();

		# render page
		$this->content = $this->render(array("santa" => $santa));

	}


	/**
	 * Randomize present circle
	 * @return array of who => to_whom values
	 * @throws userErrorException
	 */
	public function randomize() {


		# Get users that accepts
		if(!$users = sky::$db->make("santa")->where("decision", "accept")->order("to"))
			throw new userErrorException("No one accepted");


		# Will hold values who => to whom gives present
		$pairs = array();


		# Go through users
		foreach($users as $key => $user) {


			# If last user not choose by someone, so he out of circle, this is not acceptable
			if(($key == sizeof($users) - 1) && !in_array($user['owner'], $pairs)) {
				$pairs = $this->randomize();
				break;
			}


			# pre defined pairs
			if($user['to']) {
				$pairs[$user['owner']] = $user['to'];
				continue;
			}


			while(true) {


				# Get random value
				$random = mt_rand(0, sizeof($users) - 1);


				# Can't give present to self
				if($users[$random]['owner'] == $user["owner"])
					continue;


				# If this user is free
				if(!in_array($users[$random]['owner'], $pairs))
					break;


			}

			# Save pair
			$pairs[$user["owner"]] = $users[$random]['owner'];

		}

		return $pairs;

	}

	/**
	 * Gets santa data
	 * @return boolean or array
	 */
	private function getSanta() {


		# Get santa data
		if(!$santa = sky::$db->make("santa")->where("owner", auth::$me['id'])->get("single"))
			return false;


		# Get person
		if($santa['to'])
			$santa['person'] = sky::$db->make("users")->where($santa["to"])->get("single");


		# Return
		return $santa;

	}

}