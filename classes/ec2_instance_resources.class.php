<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require_once 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Ec2\Ec2Client;

class EC2_Instance_Resources extends AWS_Resources {

	const VERSION = '2016-11-15';

	private $profile;
	public $ec2Client;
	public $regions = array();
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->ec2Client = new Ec2Client([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->get_regions();
		$this->get_resources();
	}

	private function get_regions() {
		$result = $this->ec2Client->describeRegions();
		foreach($result['Regions'] as $region){
			$this->regions[] = $region['RegionName'];
		}
	}

	public function get_resources(){

		$instances = array();
		foreach($this->regions as $region){
			$ec2Client = new Ec2Client([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $ec2Client->describeInstances();
			foreach($result['Reservations'] as $reservation){
				foreach($reservation['Instances'] as $instance){
					if(!empty($instance)){
						$instances[$region][] = $instance;
					}
				}
			}
		}

		$this->resources = $instances;
	}

	public function is_tagged($instance, $region){

		if(!empty($instance['State']['Name']) && $instance['State']['Name'] == 'running'){
			$tags = $instance['Tags'];
			if(!$this->find_tag($tags)){
				return false;
			}
		}

		return true;
	}

	public function log_resource($instance, $region, $remark){
		$instance_data = array();

		$instance_data['instance_id'] = $instance['InstanceId'];
		$instance_data['instance_type'] = $instance['InstanceType'];
		$instance_data['region'] = $instance['Placement']['AvailabilityZone'];
		$instance_data['remark'] = $this->get_remark($remark);

		$this->log[] = $instance_data;
	}
}
