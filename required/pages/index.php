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
	public $title 	 = "main";

	/**
	 * Page creation
	 */
	public function __construct() {

		# render page
		$this->content = $this->render();

	}

}