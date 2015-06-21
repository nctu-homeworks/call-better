<?php defined('BASEPATH') OR exit('No direct script access allowed');

require(APPPATH.'/libraries/Login_Controller.php');

class Post extends Login_Controller
{
	protected $methods = array(
		'index_get' => array(),
		'index_post' => array(),
		'index_delete' => array(),
		'view_get' => array(),
		'response_post' => array(),
		'response_delete' => array(),
		'like_post' => array(),
		'like_delete' => array()
	);

	public function index_get() {
		if (!$this->get('user_id'))
			$this->response(array('error' => 'Require "user_id".'), 400);
		if ($this->get('count') && !is_numeric($this->get('count'))
			|| $this->get('start_by') && !is_numeric($this->get('start_by')))
			$this->response(array('error' => 'Invalid "count" or "start_by".'), 400);

		$this->load->model('Post_model');
		$result = $this->Post_model->get_user_posts(
			$this->user_id,
			$this->get('user_id') == 'self' ? $this->user_id : $this->get('user_id'),
			array(
				'limit' => $this->get('count'),
				'offset' => $this->get('start_by')
			)
		);

		if ($result == 'invalid')
			$this->response(array('error' => 'No permission for the given "user_id".'), 404);
		else
			$this->response($result, 200);
	}

	public function index_post() {
		if (!$this->post('content'))
			$this->response(array('error' => 'Require "content".'), 400);
		if ($this->post('additional_viewers') 
			&& (!is_array($this->post('additional_viewers')) || count(array_filter($this->post('additional_viewers'), 'is_numeric')) != count($this->post('additional_viewers'))))
			$this->response(array('error' => 'Invalid "additional_viewers".'), 400);

		$this->load->model('Post_model');
		$new_post = $this->Post_model->create_post($this->user_id, $this->post('content'), $this->post('additional_viewers'));

		if($new_post == 'invalid')
			$this->response(array('error' => 'Cannot find some users in "additional_viewers".'), 404);
		else if ($new_post === FALSE)
			$this->response(array('error' => 'Cannot create post.'), 500);
		else
			$this->response($new_post, 201);
	}

	public function index_delete($post_id = null) {
		if ($post_id === null)
			$this->response(array('error' => 'Require "post_id".'), 400);

		$this->load->model('Post_model');
		if ($this->Post_model->delete_post($this->user_id, $post_id))
			$this->response(null, 204);
		else
			$this->response(array('error' => 'The login user does not own this post.'), 404);
	}

	public function view_get() {
		if (!$this->get('post_id'))
			$this->response(array('error' => 'Require "post_id".'), 400);

		$this->load->model('Post_model');
		$post = $this->Post_model->get_detail($this->user_id, $this->get('post_id'));
		if ($post == 'invalid')
			$this->response(array('error' => 'Invalid "post_id".'), 404);
		else
			$this->response($post, 200);
	}

	public function response_post() {
		if (!$this->post('content') || !$this->post('post_id'))
			$this->response(array('error' => 'Require "content" and "post_id".'), 400);

		$this->load->model('Post_model');
		$result = $this->Post_model->make_response($this->user_id, $this->post('post_id'), $this->post('content'));
		if ($result == 'invalid')
			$this->response(array('error' => 'Invalid "post_id".'), 404);
		else
			$this->response($result, 201);
	}

	public function response_delete($response_id = null) {
		if ($response_id === null)
			$this->response(array('error' => 'Require "response_id".'), 400);

		$this->load->model('Post_model');
		if ($this->Post_model->delete_response($this->user_id, $response_id))
			$this->response(null, 204);
		else
			$this->response(array('error' => 'The login user does not own this response.'), 404);
	}

	public function like_post() {
		if (!$this->post('post_id'))
			$this->response(array('error' => 'Require "post_id".'), 400);

		$this->load->model('Post_model');
		$result = $this->Post_model->like_post($this->user_id, $this->post('post_id'));
		if ($result == 'invalid')
			$this->response(array('error' => 'Invalid "post_id".'), 404);
		else
			$this->response($result, 201);
	}

	public function like_delete($post_id = null) {
		if ($post_id === null)
			$this->response(array('error' => 'Require "post_id".'), 400);

		$this->load->model('Post_model');
		$this->Post_model->dislike_post($this->user_id, $post_id);
		$this->response(null, 204);
	}
}
