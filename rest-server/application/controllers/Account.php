<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/Login_Controller.php');

class Account extends Login_Controller
{
	protected $methods = array(
		'index_get' => array(),
		'index_put' => array()
	);

	public function index_get() {
		$this->load->model("Account_model");
		$this->response($this->Account_model->get_info($this->user_id), 200);
	}

	public function index_put() {
		if (!$this->put('old_password'))
			$this->response(array('error' => 'Require argument "old_password".'), 400);

		$to_modify = array();
		if ($this->put('name'))
			$to_modify['name'] = $this->put('name');
		if ($this->put('new_password'))
			$to_modify['password'] = $this->put('new_password');

		$this->load->model("Account_model");
		$result = $this->Account_model->modify_info($this->user_id, $this->put('old_password'), $to_modify);
		if (!$result)
			$this->response(array('error' => 'The "old_password" is incorrect.'), 403);
		else
			$this->response($result, 200);
	}
}
