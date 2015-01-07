<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Hit_model extends Default_Model {
	protected $tablename = 'NewHit';

	public function __construct() {
		parent::__construct();
	}
	
	public function archiveHits($search_id) {
		// Move Hits for a particular search from NewHit to ArchiveHit
		$this->move_using_params('ArchiveHit', array('SearchID'=>$search_id));
		$this->optimize();
	}
	
	public function getLatestHit($search_id) {
		return $this->get_one_using_params(array('SearchID'=>$search_id, 'order_by'=>array('Date'=>'desc')));
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

	public function hitExists($search_id, $url) {
		// Do a query for the hit to see if it exists
		$hits = $this->get_all_using_params(array('SearchID' => $search_id, 'URL' => $url));
		return (is_array($hits) && count($hits) > 0) ;
	}
}
?>
