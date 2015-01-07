<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class User_model extends Default_Model {
	protected $tablename = 'UserProfile';

	public function __construct() {
		parent::__construct();
	}
	
	public function email($user_id) {
		$user = $this->get_one_using_params(array($this->id_field => $user_id));
		
		if ($user) return $user->Email;
		return NULL;
	}

	public function emailExists($email) {
		$email = trim($email);
		$user = $this->get_one_using_params(array('like:Email' => $email));
		
		if ($user) return TRUE;
		return FALSE;
	}

	public function getUserIDByEmail($email) {
		$email = trim($email);
		$user = $this->get_one_using_params(array('like:Email' => $email));
		//echo $this->db->last_query();
		if ($user) return $user->id;
		
		// Looks like we need to create a user
		$data = array(	'Email' => $email,
						'NumSearchesAllowed' => 10);
		
		return $this->add($data)->{$this->id_field};
	}
	
	public function removeAllowedSearch($user_id) {
		$user = $this->get($user_id);
		$allowed_searches = $user->NumSearchesAllowed;
		if ($allowed_searches > 0) $allowed_searches--;
		$this->update($user_id, array('NumSearchesAllowed' => $allowed_searches));
	}

	public function addAllowedSearch($user_id, $increment_amount = 1) {
		if ($increment_amount < 1) $increment_amount = 1;
		$user = $this->get($user_id);
		$allowed_searches = $user->NumSearchesAllowed;
		$allowed_searches += $increment_amount;
		$this->update($user_id, array('NumSearchesAllowed' => $allowed_searches));
	}

}
?>