<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Session_model extends CI_Model
{
	public function create_session($device_info) {
		$key = self::_generate_key();
		$result = $this->db->insert(config_item('rest_keys_table'), array(
			config_item('rest_key_column') => $key,
			'create_time' => date('Y-m-d H:i:s'),
			'device_info' => $device_info
		));

		return $result ? $key : FALSE;
    }

	private function _generate_key() {
		$this->load->helper('security');

		do {
			$salt = do_hash(time().mt_rand());
			$new_key = substr($salt, 0, config_item('rest_key_length'));
		} while (self::_key_exists($new_key));

		return $new_key;
	}

	private function _key_exists($key) {
		return $this->db
			->where(config_item('rest_key_column'), $key)
			->count_all_results(config_item('rest_keys_table')) > 0;
	}
}
