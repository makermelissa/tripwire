<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Thumbnails_model extends Default_Model {
	protected $tablename = 'Thumbnails';

	public function __construct() {
		parent::__construct();
	}
	
	public function addImages($hit_id, $images) {
		if (!is_array($images)) return FALSE;
		foreach ($images as $image) {
			$this->add(array('HitID'=>$hit_id, 'URL'=>$image));
		}
	}
	
	public function getImages($hit_id) {
		return $this->get_all_using_params(array('HitID'=>$hit_id), array('URL'));
	}
}
?>
