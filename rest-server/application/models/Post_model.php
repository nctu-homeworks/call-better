<?php defined('BASEPATH') OR exit('No direct script access allowed');

class Post_model extends CI_Model
{
	public function get_user_posts($self_id, $user_id, $range) {
		if (!$this->_check_friendship($self_id, $user_id))
			return "invalid";

		$range = array_merge(
			array('limit' => 10, 'offset' => 0),
			array_map('intval', array_filter($range, 'is_numeric'))
		);

		return $this->db
			->select('
				post.id,
				user.name as creator,
				post.creater as creator_id,
				post.content,
				count(DISTINCT A.id) as like_count,
				count(DISTINCT response.id) as response_count,
				count(DISTINCT B.id) > 0 as is_like,
				post.time
			')->from('post')
			->join('user', 'post.creater = user.id')
			->join('like as A', 'post.id = A.post', 'left')
			->join('like as B', 'post.id = B.post and B.user = '.$self_id, 'left')
			->join('response', 'post.id = response.post', 'left')
			->where('post.creater', $user_id)
			->group_by('post.id')
			->order_by('post.time', 'DESC')
			->limit($range['limit'], $range['offset'])
			->get()->result();
	}

	public function get_detail($user_id, $post_id) {
		$qry = $this->db
			->select('
				post.id,
				user.name as creator,
				post.creater as creator_id,
				post.content,
				post.time,
				count(DISTINCT L.id) > 0 as is_like
			')->from('post')
			->join('user', 'post.creater = user.id')
			->join('like as L', 'post.id = L.post and L.user = '.$user_id, 'left')
			->where('post.id', $post_id)
			->group_by('post.id')
			->limit(1)
			->get();
		if ($qry->num_rows() != 1)
			return 'invalid';

		$post = $qry->row();
		if (!$this->_check_friendship($user_id, $post->creator_id))
			return 'invalid';

		$post->responses = $this->db
			->select('
				response.id,
				user.name as creator,
				response.user as creator_id,
				response.content,
				response.time
			')->from('response')
			->join('user', 'response.user = user.id')
			->where('post', $post_id)
			->get()->result();

		$post->likes = $this->db
			->select('
				`like`.id,
				user.name as creator,
				`like`.user as creator_id
			')->from('like')
			->join('user', 'like.user = user.id')
			->where('post', $post_id)
			->get()->result();

		if ($post->creator_id == $user_id)
			$post->additional_viewers = $this->db
				->select('
					user.id as user_id,
					user.name,
					user.sex,
					user.email
				')->from('additional_viewer')
				->join('user', 'additional_viewer.user = user.id')
				->where('post', $post_id)
				->get()->result();
		
		return $post;
	}

	public function create_post($user_id, $content, $addition_viewers = FALSE) {
		if ($addition_viewers !== FALSE) {
			$add_views = array_unique($addition_viewers, SORT_NUMERIC);
			if ($this->db->where_in('id', $add_views)->count_all_results('user') != count($add_views))
				return 'invalid';
		}

		$new_post = array(
			'creater' => $user_id,
			'content' => $content,
			'time' => date("Y-m-d H:i:s")
		);

		$this->db->trans_start();

		$this->db->insert('post', $new_post);
		$new_post['id'] = $this->db->insert_id();

		if ($addition_viewers !== FALSE) {
			$this->db->insert_batch('additional_viewer', array_map(function($id) {
				return array('id' => $id);
			}, $add_views));
		}

		$this->db->trans_complete();

		$new_post['creator_id'] = $user_id;
		unset($new_post['creater']);
		$new_post['creator'] = $this->db->select('name')->get_where('user', array('id' => $user_id))->row()->name;

		return $this->db->trans_status() === FALSE ? FALSE : $new_post;
	}

	public function delete_post($user_id, $post_id) {
		$this->db->delete('post', array(
			'id' => $post_id,
			'creater' => $user_id
		));

		return $this->db->affected_rows() == 1;
	}

	private function _check_friendship($self_id, $friend_id) {
		if ($self_id != $friend_id) {
			$this->db->group_start()
					->where('user1', $self_id)->where('user2', $friend_id)
					->or_where('user1', $friend_id)->where('user2', $self_id)
				->group_end()->where('accepted', 1);
			if ($this->db->count_all_results('friendship') == 0)
				return FALSE;
		}

		return TRUE;
	}

	public function make_response($user_id, $post_id, $content) {
		$post = $this->db->select('creater')->get_where('post', array('id' => $post_id));
		if ($post->num_rows() != 1 || !$this->_check_friendship($user_id, $post->row()->creater))
			return 'invalid';

		$this->db->insert('response', array(
			'user' => $user_id,
			'post' => $post_id,
			'time' => date('Y-m-d H:i:s'),
			'content' => $content
		));

		$response = $this->db->select('
				response.id,
				user.name as creator,
				response.user as creator_id,
				response.content,
				response.time
			')->from('response')
			->join('user', 'response.user = user.id')
			->where('response.id', $this->db->insert_id())
			->get()->row();

		$post = $this->db
			->select('
				post.id,
				user.name as creator,
				post.creater as creator_id,
				post.content,
				count(DISTINCT A.id) as like_count,
				count(DISTINCT response.id) as response_count,
				count(DISTINCT B.id) > 0 as is_like,
				post.time
			')->from('post')
			->join('user', 'post.creater = user.id')
			->join('like as A', 'post.id = A.post', 'left')
			->join('like as B', 'post.id = B.post and B.user = '.$user_id, 'left')
			->join('response', 'post.id = response.post', 'left')
			->where('post.id', $post_id)
			->group_by('post.id')
			->order_by('post.time', 'DESC')
			->get()->row();

		$response->post = $post;
		return $response;
	}

	public function delete_response($user_id, $response_id) {
		$this->db->delete('response', array(
			'id' => $response_id,
			'user' => $user_id
		));

		return $this->db->affected_rows() == 1;
	}

	public function like_post($user_id, $post_id) {
		$post = $this->db->select('creater')->get_where('post', array('id' => $post_id));
		if ($post->num_rows() != 1 || !$this->_check_friendship($user_id, $post->row()->creater))
			return 'invalid';

		$original_like = $this->db->get_where('like', array(
			'user' => $user_id,
			'post' => $post_id
		), 1);

		if ($original_like->num_rows() == 0) {
			$this->db->insert('like', array(
				'user' => $user_id,
				'post' => $post_id,
				'time' => date('Y-m-d H:i:s')
			));
			$like_id = $this->db->insert_id();
		} else
			$like_id = $original_like->row()->id;

		$like = $this->db->select('
				A.id,
				user.name as creator,
				A.user as creator_id,
				count(DISTINCT B.id) as like_count,
				1 as is_like
			')->from('like as A')
			->join('user', 'A.user = user.id')
			->join('like as B', 'A.post = B.post')
			->where('A.id', $like_id)
			->group_by('B.post')
			->get()->row();

		return $like;
	}

	public function dislike_post($user_id, $post_id) {
		$this->db->delete('like', array(
			'post' => $post_id,
			'user' => $user_id
		));

		return TRUE;
	}

	public function get_wall($user_id, $range) {
		$range = array_merge(
			array('limit' => 10, 'offset' => 0),
			array_map('intval', array_filter($range, 'is_numeric'))
		);

		return $this->db
			->select('
				post.id,
				user.name as creator,
				post.creater as creator_id,
				post.content,
				count(DISTINCT L_A.id) as like_count,
				count(DISTINCT response.id) as response_count,
				count(DISTINCT L_B.id) > 0 as is_like,
				post.time
			')->from('post')
			->join('user', 'post.creater = user.id')
			->join('like as L_A', 'post.id = L_A.post', 'left')
			->join('like as L_B', 'post.id = L_B.post and L_B.user = '.$user_id, 'left')
			->join('response', 'post.id = response.post', 'left')
			->join('friendship as F_A', 'post.creater = F_A.user1', 'left')
			->join('friendship as F_B', 'post.creater = F_B.user2', 'left')
			->join('additional_viewer', 'post.id = additional_viewer.post', 'left')
			->or_where('post.creater', $user_id)
			->or_where('F_A.user2', $user_id)
			->or_where('F_B.user1', $user_id)
			->or_where('additional_viewer.user', $user_id)
			->group_by('post.id')
			->order_by('post.time', 'DESC')
			->limit($range['limit'], $range['offset'])
			->get()->result();
	}
}
