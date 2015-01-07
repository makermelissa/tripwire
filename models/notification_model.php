<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Notification_model extends Default_Model {
	protected $tablename = 'Notification';

	public function __construct() {
		parent::__construct();
	}

	public function getAllSearchIDs() {
		$this->db->distinct();
		$this->db->select('SearchID');
		$hits = $this->get_all();

		$search_ids = array();
		foreach($hits as $hit) {
			$search_ids[] = $hit->SearchID;	
		}
		
		return $search_ids;
	}

	public function notificationCount($search_id = NULL) {
		if (is_null($search_id)) return count($this->get_all_using_params(array('Type !='=>'')));
		
		return count($this->get_all_using_params(array('SearchID'=>$search_id)));
	}
	
	public function archiveNotification($notification_id) {
		// Move	the Notification from Notification to ArchiveNotification
		$this->move($notification_id, 'ArchiveNotification');
		$this->optimize();
	}

	public function addNotification($type, $user_id, $search_id, $text = FALSE) {
		if (!in_array($type, array('initial', 'renew', 'hit', 'expired', 'deactivated'))) return FALSE;
		$data = array(	'Type' => $type, 
						'UserProfileID' => $user_id, 
						'SearchID' => $search_id, 
						'Text' => ($text ? 1 : 0), 
						'Email' => 1);
		$this->add($data);
		
		return TRUE;
	}

}
?>
