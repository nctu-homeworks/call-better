<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class Login extends REST_Controller
{
	protected $methods = array(
		'login_post' => array(),
		'register_pos' => array()
	);

	public function login_post() {
		if (!$this->post('email') || !$this->post('password'))
			$this->response(array('error' => 'Missing email or password.'), 400);

		$this->load->model("Account_model");
		$login_user = $this->Account_model->login($this->post('email'), $this->post('password'));

		if (!$login_user)
			$this->response(array('error' => 'Fail to login.'), 404);

		$this->load->model("Session_model");
		$this->Session_model->login_session($this->_apiuser->id, $login_user['id']);
		$this->response($result, 200);
	}
}
