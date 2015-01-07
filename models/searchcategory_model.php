<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Searchcategory_model extends Default_Model {
	protected $tablename = 'SearchCategories';

	public function __construct() {
		parent::__construct();
	}
	
	public function category_list($type) {
		return $this->obj_to_array($this->get_all_using_params(array('Type'=>$type, 'Active'=>1)), 'Category', 'id');
	}

	public function getCategoryIdByCode($URLCode) {
		if (empty($URLCode)) return '';
		$data = $this->get_one_using_params(array('URLCode'=>$URLCode));	
		if ($data) return $data->id;
		return '';
	}
	
	public function get_category_types() {
		return $this->obj_to_array($this->get_all_using_params(array('distinct'), array('Type')), 'Type');
	}
}
?>