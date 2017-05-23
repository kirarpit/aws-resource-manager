<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require_once 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Ec2\Ec2Client;

class EC2_Volume_Resources extends AWS_Resources {

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

		$volumes = array();
		foreach($this->regions as $region){
			$ec2Client = new Ec2Client([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $ec2Client->describeVolumes();
			foreach($result['Volumes'] as $volume){
				if(!empty($volume)){
					$volumes[$region][] = $volume;
				}
			}
		}

		$this->resources = $volumes;
	}

	public function is_tagged($volume, $region){

		if(false && (empty($volume['Tags']) || !$this->find_tag($volume['Tags']))){
			return false;
		}

		return true;
	}

	public function log_resource($volume, $region, $remark){
		$volume_data = array();

		$volume_data['volume_id'] = $volume['VolumeId'];
		$volume_data['region'] = $volume['AvailabilityZone'];
		$volume_data['volume_type'] = $volume['VolumeType'];
		$volume_data['remark'] = $this->get_remark($remark);

		$this->log[] = $volume_data;
	}
}
