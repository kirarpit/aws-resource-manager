<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Rds\RdsClient;

class Rds_Resources extends AWS_Resources {

	const VERSION = '2014-10-31';

	public $profile;
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
		if(!empty($instance['DBInstanceStatus']) && $instance['DBInstanceStatus'] == 'available'){
			if(!$this->find_tag($tags)){
				return false;
			}
		}

		return true;
	}

	public function is_under_utilised($instance, $region){
		$namespace = 'AWS/RDS';

		$dimensions = array(array(
					'Name' => 'DBInstanceIdentifier',
					'Value' => $instance['DBInstanceIdentifier'],
				     ));

		if(!empty($instance['DBInstanceStatus']) && $instance['DBInstanceStatus'] == 'available'){
			$launch_time = strtotime($instance['InstanceCreateTime']);
			if($launch_time > strtotime('-2 day')){
				return false;
			}

			$free_memory_stats = $this->cloudWatch->get_free_memory_stats($namespace, $dimensions);
			$free_memory = max(array_column($free_memory_stats['Datapoints'], 'Maximum'))/1000000;

			$cpu_utilisation = $this->cloudWatch->get_cpu_utilisation_stats($namespace, $dimensions);
			$cpu_utilisation = max(array_column($cpu_utilisation['Datapoints'], 'Maximum'));

			//echo "%$cpu_utilisation & MB$free_memory & instance_id:{$instance['DBInstanceIdentifier']}".PHP_EOL;
			if($cpu_utilisation < 40 && $free_memory < 10){
				return true;
			}
		}

		return false;
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
