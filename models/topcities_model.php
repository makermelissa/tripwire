<?php
defined( 'BASEPATH' ) or die( 'No direct script access allowed' );
require_once(APPPATH.'models/default_model.php');

class Topcities_model extends Default_Model {
	protected $tablename = 'TopCities';

	public function __construct() {
		parent::__construct();
	}
	
	public function getNextCity() {
		$city = $this->get_one_using_params(array('active'=>1,'order_by'=>array('last_query'=>'asc')));	
		$this->update($city->id, array('last_query'=>$this->timestamp(time())));
		return $city;
	}
}
?>
