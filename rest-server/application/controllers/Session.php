<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/REST_Controller.php');

class Session extends REST_Controller
{
	protected $methods = array(
		'index_post' => array('key' => FALSE)
	);

	public function index_post() {
		if (!$this->post('device_info'))
			$this->response(array('error' => 'Argument "device_info" is required.'), 400);

		$this->load->model("Session_model");
		$key = $this->Session_model->create_session($this->post('device_info'));

		if ($key) {
			$this->response(array('session_key' => $key), 201);
		} else {
			$this->response(array('error' => 'Could not save the key.'), 500);
		}
    }
}
