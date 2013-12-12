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
	public $title 	 = "Сообщения";

	/**
	 * Page creation
	 */
	public function main() {

		# render page
		$this->content = $this->render();

	}

}