<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require_once 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Ec2\Ec2Client;

class EC2_Resources extends AWS_Resources {

	const VERSION = '2016-11-15';

	private $profile;
	public $ec2Client;
	public $regions = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->ec2Client = new Ec2Client([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->get_regions();
	}

	private function get_regions() {
		$result = $this->ec2Client->describeRegions();
		foreach($result['Regions'] as $region){
			$this->regions[] = $region['RegionName'];
		}
	}

	public function check_instances(){

		$all_instances = array();
		foreach($this->regions as $region){
			$ec2Client = new Ec2Client([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $ec2Client->describeInstances();
			foreach($result['Reservations'] as $reservation){
				foreach($reservation['Instances'] as $instance){
					$instance_data = array();

					// Checking for appropriate tags
					if(!empty($instance['State']['Name']) && $instance['State']['Name'] == 'running'){
						$tags = $instance['Tags'];
						if(!$this->is_tagged($tags)){
							$instance_data['instance_id'] = $instance['InstanceId'];
							$instance_data['instance_type'] = $instance['InstanceType'];
							$instance_data['region'] = $instance['Placement']['AvailabilityZone'];
							$instance_data['remark'] = "'Project' Tag Not Found";
						}
					}

					if(!empty($instance_data)){
						$all_instances[] = $instance_data;
					}
				}
			}
		}

		return $all_instances;
	}

	public function check_volumes(){

		$all_volumes = array();
		foreach($this->regions as $region){
			$ec2Client = new Ec2Client([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $ec2Client->describeVolumes();
			foreach($result['Volumes'] as $volume){
				$volume_data = array();

				if(!empty($volume['State']) && $volume['State'] == 'available'){
					$volume_data = $this->get_volume_data($volume, 'not-in-use');
					$all_volumes[] = $volume_data;
				}

				if(!empty($volume['Tags'])){
					$tags = $volume['Tags'];
					if(!$this->is_tagged($tags)){
						$volume_data = $this->get_volume_data($volume, 'untagged');
						$all_volumes[] = $volume_data;
					}
				}else{
					$volume_data = $this->get_volume_data($volume, 'untagged');
					$all_volumes[] = $volume_data;
				}
			}
		}

		return $all_volumes;
	}

	private function get_volume_data($volume, $reason){
		$volume_data = array();
		$volume_data['volume_id'] = $volume['VolumeId'];
		$volume_data['region'] = $volume['AvailabilityZone'];
		$volume_data['volume_type'] = $volume['VolumeType'];

		if($reason == 'not-in-use'){
			$remark = 'Resource is not in use';
		}else if($reason == 'untagged'){
			$remark = "'Project' Tag Not Found";
		}

		$volume_data['remark'] = $remark;

		return $volume_data;
	}
}
