<?php
	/**
	 * Kill the page, send JSON content type and data
	 * @param string $status Optional. Type of status
	 * @param mixed $cargo Optional. 
	 * @return void
	 */
	function die_json($status="success", $cargo=false, $message="") {
		$data = array(
			'status' => strtolower(trim($status)),
		);
		
		if(!empty($cargo) && is_array($cargo)) {
			$data['cargo'] = $cargo;
		}
		
		if("" !== ($message = trim($message))) {
			$data['message'] = $message;
		}
		
		header("Content-Type: application/json");
		
		print json_encode($data);
		
		die;
	}
	
	
	/**
	 * Generate security hash needed to rate media by currently logged in user. Returns false when user is not logged in
	 * @param int $media_id `media`.`id`
	 * @return string|false
	 */
	function get_post_rate_ajax_hash($media_id) {
		if(!isAuthedUser()) {
			return false;
		}
		
		return md5("rate_content:user_". getAuthedUserID() .":media_". $media_id);
	}
	
	/**
	 * Generate security hash needed to favorite media by currently logged in user. Returns false when user is not logged in
	 * @param int $media_id `media`.`id`
	 * @return string|false
	 */
	function get_post_favorite_ajax_hash($media_id) {
		if(!isAuthedUser()) {
			return false;
		}
		
		return md5("favorite_content:user_". getAuthedUserID() .":media_". $media_id);
	}
	
	/**
	 * Generate security hash needed to add comment to media by currently logged in user. Returns false when user is not logged in
	 * @param int $media_id `media`.`id`
	 * @param int|false $reply_to Optional. The `media_comment`.`id` that this comment is repyling to. Defaults to false (no parent)
	 * @return string|false
	 */
	function get_comment_add_ajax_hash($media_id, $reply_to=false) {
		if(!isAuthedUser()) {
			return false;
		}
		
		if(!empty($reply_to) && is_numeric($reply_to)) {
			return md5("add_comment:user_". getAuthedUserID() .":media_". $media_id .":reply_to_". $reply_to);
		}
		
		return md5("add_comment:user_". getAuthedUserID() .":media_". $media_id);
	}
	
	/**
	 * Generate security hash needed to delete a comment by currently logged in user. Returns false when user is not logged in
	 * @param int $comment_id `media_comment`.`id`
	 * @return string|false
	 */
	function get_comment_delete_ajax_hash($comment_id) {
		if(!isAuthedUser()) {
			return false;
		}
		
		return md5("delete_comment:user_". getAuthedUserID() .":comment_". $comment_id);
	}
	
	/**
	 * Generate security hash needed to rate a comment by currently logged in user. Returns false when user is not logged in
	 * @param int $comment_id `media_comment`.`id`
	 * @return string|false
	 */
	function get_comment_rate_ajax_hash($comment_id) {
		if(!isAuthedUser()) {
			return false;
		}
		
		return md5("rate_comment:user_". getAuthedUserID() .":media_". $comment_id);
	}