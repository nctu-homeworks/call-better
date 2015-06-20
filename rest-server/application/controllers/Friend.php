<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/Login_Controller.php');

class Friend extends Login_Controller
{
	protected $methods = array(
		'index_get' => array(),
		'index_post' => array(),
		'index_delete' => array(),
		'current_get' => array(),
		'inviting_get' => array(),
		'invited_get' => array(),
		'find_get' => array()
	);

	public function index_get() {
		$this->load->model('Friend_model');
		$this->response($this->Friend_model->get_friends($this->user_id), 200);
	}

	public function current_get() {
		$this->load->model('Friend_model');
		$this->response($this->Friend_model->get_friends($this->user_id)['friends'], 200);
	}

	public function inviting_get() {
		$this->load->model('Friend_model');
		$this->response($this->Friend_model->get_friends($this->user_id)['inviting'], 200);
	}

	public function invited_get() {
		$this->load->model('Friend_model');
		$this->response($this->Friend_model->get_friends($this->user_id)['invited'], 200);
	}

	public function index_post() {
		if (!$this->post('user_id'))
			$this->response(array('error' => 'Missing "user_id".'), 400);

		$this->load->model('Friend_model');
		$result = $this->Friend_model->make_invitation($this->user_id, $this->post('user_id'));
		if ($result == 'inviting')
			$this->response(array('error' => 'The login user is already inviting this user.'), 409);
		else if ($result == 'invalid')
			$this->response(array('error' => 'Invalid "user_id".'), 404);
		else
			$this->response($result, 201);
	}

	public function index_delete($user_id = null) {
		if ($user_id === null)
			$this->response(array('error' => 'Missing id to delete.'), 400);

		$this->load->model('Friend_model');
		if ($this->Friend_model->delete_friend($this->user_id, $user_id))
			$this->response(null, 204);
		else
			$this->response(array('error' => 'This id does not belong to any friend of the login user.'), 404);
	}

	public function find_get() {
		if (!$this->get('name'))
			$this->response(array('error' => 'Name required.'), 400);

		$this->load->model('Friend_model');
		$result = $this->Friend_model->find_users($this->get('name'));
		if (count($result) == 0)
			$this->response(array('error' => 'No match result.'), 404);

		$this->response($result, 200);
	}
}
