<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/Login_Controller.php');

class Wall extends Login_Controller
{
	protected $methods = array(
		'index_get' => array()
	);

	public function index_get() {
		if ($this->get('count') && !is_numeric($this->get('count'))
			|| $this->get('start_by') && !is_numeric($this->get('start_by')))
			$this->response(array('error' => 'Invalid "count" or "start_by".'), 400);

		$this->load->model('Post_model');
		$result = $this->Post_model->get_wall(
			$this->user_id,
			array(
				'limit' => $this->get('count'),
				'offset' => $this->get('start_by')
			)
		);

		$this->response($result, 200);
	}
}
