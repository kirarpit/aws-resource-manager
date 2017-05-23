<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Rds\RdsClient;

class Rds_Resources extends AWS_Resources {

	const VERSION = '2014-10-31';

	private $profile;
	public $rdsClient;
	public $regions = array();
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->rdsClient = new RdsClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->get_regions();
		$this->get_resources();
	}

	private function get_regions() {
		$result = $this->rdsClient->describeSourceRegions();
		foreach($result['SourceRegions'] as $region){
			if($region['Status'] == 'available'){
				$this->regions[] = $region['RegionName'];
			}
		}
	}

	public function get_resources(){

		$instances = array();
		foreach($this->regions as $region){
			$rdsClient = new RdsClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $rdsClient->describeDBInstances();
			foreach($result['DBInstances'] as $instance){
				if(!empty($instance)){
					$instances[$region][] = $instance;
				}
			}
		}

		$this->resources = $instances;
	}

	public function is_tagged($instance, $region){

		$rdsClient = new RdsClient([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);

		$tags = $this->get_resource_tags($rdsClient, $instance['DBInstanceArn']);
		if(!$this->find_tag($tags)){
			return false;
		}

		return true;
	}

	public function log_resource($instance, $region, $remark){
		$instance_data = array();

		$instance_data['instance_id'] = $instance['DBInstanceIdentifier'];
		$instance_data['instance_type'] = $instance['DBInstanceClass'];
		$instance_data['region'] = $instance['AvailabilityZone'];
		$instance_data['remark'] = $this->get_remark($remark);

		$this->log[] = $instance_data;
	}

	private function get_resource_tags($rdsClient, $resource_name){
		$result = $rdsClient->listTagsForResource(array(
					'ResourceName' => $resource_name
					));

		return $result['TagList'];
	}
}
