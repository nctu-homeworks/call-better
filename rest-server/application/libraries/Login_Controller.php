<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class Login_Controller extends REST_Controller
{
	protected $user_id;

	public function __construct() {
		parent::__construct();
		if (!$this->rest->user_id)
			$this->response(array('error' => "Login required."), 403);

		$this->user_id = $this->rest->user_id;
	}
}
