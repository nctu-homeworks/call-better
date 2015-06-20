<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class Login extends REST_Controller
{
	protected $methods = array(
		'login_post' => array(),
		'register_post' => array()
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
		$this->response($login_user, 200);
	}

	public function register_post() {
		$arg_keys = array('name', 'sex', 'birthday', 'email', 'password');
		$args = array_combine($arg_keys, array_map(array($this, 'post'), $arg_keys));

		$bad_args = array();
		if (!$args['name'])
			$bad_args[] = 'name';

		if ($args['sex'] === FALSE || !in_array($args['sex'], array('0', '1'), TRUE))
			$bad_args[] = 'sex';

		if (!$args['birthday'])
			$bad_args[] = 'birthday';
		else {
			$d = DateTime::createFromFormat('Y-m-d', $args['birthday']);
			if (!$d || $d->format('Y-m-d') != $args['birthday'])
				$bad_args[] = 'birthday';
		}

		if (!$args['email'] || !filter_var($args['email'], FILTER_VALIDATE_EMAIL))
			$bad_args[] = 'email';

		if (!$args['password'])
			$bad_args[] = 'password';

		if (!empty($bad_args))
			$this->response(array('error' => 'Arguments not valid.', 'bad' => $bad_args), 400);


		$this->load->model("Account_model");
		$register_user = $this->Account_model->register($args);
		if (!$register_user)
			$this->response(array('error' => 'Cannot create user.'), 500);
		else if ($register_user == 'duplicated')
			$this->response(array('error' => 'Email duplicated.'), 409);

		$this->load->model("Session_model");
		$this->Session_model->login_session($this->_apiuser->id, $register_user['id']);
		$this->response($register_user, 201);
	}
}
