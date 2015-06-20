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
}
