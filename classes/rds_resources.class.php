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

	public function __construct($profile) {
		$this->profile = $profile;

		$this->rdsClient = new RdsClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->get_regions();
	}

	private function get_regions() {
		$result = $this->rdsClient->describeSourceRegions();
		foreach($result['SourceRegions'] as $region){
			if($region['Status'] == 'available'){
				$this->regions[] = $region['RegionName'];
			}
		}
	}

	public function check_instances(){

		$all_instances = array();
		foreach($this->regions as $region){
			$rdsClient = new RdsClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $rdsClient->describeDBInstances();
			foreach($result['DBInstances'] as $instance){
				$instance_data = array();

				$tags = $this->get_resource_tags($rdsClient, $instance['DBInstanceArn']);
				if(!$this->is_tagged($tags)){
					$instance_data['instance_id'] = $instance['DBInstanceIdentifier'];
					$instance_data['instance_type'] = $instance['DBInstanceClass'];
					$instance_data['region'] = $instance['AvailabilityZone'];
					$instance_data['remark'] = "'Project' Tag Not Found";
				}

				if(!empty($instance_data)){
					$all_instances[] = $instance_data;
				}
			}
		}

		return $all_instances;
	}

	public function check_snapshots(){

		$all_snapshots = array();
		foreach($this->regions as $region){
			$rdsClient = new RdsClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $rdsClient->describeDBSnapshots();
			foreach($result['DBSnapshots'] as $snapshot){
				$snapshot_data = array();

				$tags = $this->get_resource_tags($rdsClient, $snapshot['DBSnapshotArn']);

				if(!$this->is_tagged($tags)){
					$snapshot_data['instance_id'] = $snapshot['DBInstanceIdentifier'];
					$snapshot_data['snapshot_id'] = $snapshot['DBSnapshotIdentifier'];
					$snapshot_data['region'] = $snapshot['AvailabilityZone'];
					$snapshot_data['remark'] = "'Project' Tag Not Found";
				}

				if(!empty($snapshot_data)){
					$all_snapshots[] = $snapshot_data;
				}
			}
		}

		return $all_snapshots;
	}

	private function get_resource_tags($rdsClient, $resource_name){
		$result = $rdsClient->listTagsForResource(array(
					'ResourceName' => $resource_name
					));

		return $result['TagList'];
	}
}
