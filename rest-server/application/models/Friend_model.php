<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Friend_model extends CI_Model
{
	public function find_users($name) {
		return $this->db
			->select('id as user_id, name, sex, email')
			->like('name', $name)
			->get('user')
			->result();
	}

	public function get_friends($user_id) {
		$result = array();

		// friends
		$sql_before_join = '
			SELECT 	`id` as `friendship_id`,
					`user2` as `user_id`,
					`create_time`
			FROM 	`friendship`
			WHERE 	`user1` = ? and `accepted` = 1
			UNION (
				SELECT 	`id`,
						`user1`,
						`create_time`
				FROM 	`friendship`
				WHERE 	`user2` = ? and `accepted` = 1
			)
		';
		$sql_after_join = "
			SELECT 	`f`.`friendship_id`,
					`f`.`user_id`,
					`u`.`name`,
					`u`.`sex`,
					`u`.`email`
			FROM ( $sql_before_join ) as `f`
			INNER JOIN `user` as `u`
				ON `f`.`user_id` = `u`.`id`
			ORDER BY `f`.`create_time` desc
		";
		$result['friends'] = $this->db->query($sql_after_join, array($user_id, $user_id))->result();

		// inviting
		$result['inviting'] = $this->db
			->select('
				friendship.id as friendship_id,
				friendship.user2 as user_id,
				user.name,
				user.sex,
				user.email
			')->from('friendship')
			->join('user', 'friendship.user2 = user.id')
			->where('friendship.user1', $user_id)
			->where('friendship.accepted', 0)
			->get()->result();

		// invited
		$result['invited'] = $this->db
			->select('
				friendship.id as friendship_id,
				friendship.user1 as user_id,
				user.name,
				user.sex,
				user.email
			')->from('friendship')
			->join('user', 'friendship.user1 = user.id')
			->where('friendship.user2', $user_id)
			->where('friendship.accepted', 0)
			->get()->result();

		return $result;
	}

	public function make_invitation($user_id, $invite_id) {
		$invited_user = $this->db
			->select('id, name, sex, email')
			->get_where('user', array('id' => $invite_id), 1);
		if ($invited_user->num_rows() != 1)
			return "invalid";
		else
			$invited_user = $invited_user->row();

		$ing = $this->db
			->where('user1', $user_id)
			->where('user2', $invite_id)
			->get('friendship');
		if ($ing->num_rows() == 1) {
			$ing = $ing->row();
			$friendship_id = $ing->id;
			$accepted = $ing->accepted;
		} else {

			$ed = $this->db
				->where('user1', $invite_id)
				->where('user2', $user_id)
				->get('friendship');
			if ($ed->num_rows() == 1) {
				$friendship_id = $ed->row()->id;
				$accepted = 1;

				$this->db->update('friendship', array(
					'accepted' => 1,
					'create_time' => date('Y-m-d H:i:s')
				), array('id' => $friendship_id));
			} else {
				$this->db->insert('friendship', array(
					'create_time' => date('Y-m-d H:i:s'),
					'user1' => $user_id,
					'user2' => $invite_id,
					'accepted' => 0
				));
				
				$friendship_id = $this->db->insert_id();
				$accepted = 0;
			}
		}

		return array(
			'friendship_id' => $friendship_id,
			'accepted' => $accepted,
			'user_id' => $invited_user->id,
			'name' => $invited_user->name,
			'sex' => $invited_user->sex,
			'email' => $invited_user->email
		);
	}

	public function delete_friend($user_id, $friend_id) {
		$this->db
			->where('user1', $user_id)
			->where('user2', $friend_id)
			->or_where('user2', $user_id)
			->where('user1', $friend_id)
			->delete('friendship');

		return $this->db->affected_rows() > 0;
	}
}
