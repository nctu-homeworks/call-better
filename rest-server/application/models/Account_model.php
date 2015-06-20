<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Account_model extends CI_Model
{
	public function login($email, $password) {
		$qry = $this->db->get_where('user', array('email' => $email), 1);
		if ($qry->num_rows() != 1)
			return FALSE;

		$user = $qry->row_array();
		if (!password_verify($password, $user['password']))
			return FALSE;

		return array_intersect_key($user, array_flip(array('id', 'name', 'email', 'sex', 'birthday')));
	}

	public function register($data) {
		$pwd_hash = password_hash($data['password'], PASSWORD_DEFAULT);
		if (!$pwd_hash)
			return FALSE;

		$user_data = array(
			'name' => $data['name'],
			'birthday' => $data['birthday'],
			'sex' => $data['sex'],
			'register_time' => date('Y-m-d H:i:s'),
			'email' => $data['email'],
			'password' => $pwd_hash
		);
		$qry = $this->db->insert('user', $user_data);

		if ($this->db->affected_rows() != 1)
			return $this->db->error()['code'] == 1062 ? 'duplicated' : FALSE;

		unset($user_data['password']);
		$user_data['id'] = $this->db->insert_id();
		return $user_data;
	}
}
