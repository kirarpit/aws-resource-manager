<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\Ec2\Ec2Client;

$ec2Client = new Ec2Client([
    'region' => 'ap-southeast-1',
    'version' => '2016-11-15',
    'profile' => 'default'
]);

//Get all regions for EC2
$result = $ec2Client->describeRegions();
foreach($result['Regions'] as $region){
	$regions[] = $region['RegionName'];
}

//$instances = check_instances($regions);
//$volumes = check_volumes($regions);
var_dump($result);

function check_volumes($regions){

	$all_volumes = array();
	foreach($regions as $region){
		$ec2Client = new Ec2Client([
				'region' => $region,
				'version' => '2016-11-15',
				'profile' => 'default'
		]);
		$result = $ec2Client->describeVolumes();
		foreach($result['Volumes'] as $volume){
			$volume_data = array();

			if(!empty($volume['State']) && $volume['State'] == 'available'){
				$volume_data = get_volume_data($volume, 'not-in-use');
			}

			if(!empty($volume['Tags'])){
				$tags = $volume['Tags'];
				if(!find_tag($tags, 'Name')){
					$volume_data = get_volume_data($volume, 'untagged');
				}
			}else{
				$volume_data = get_volume_data($volume, 'untagged');
			}

			if(!empty($volume_data)){
				$all_volumes[] = $volume_data;
			}
		}
	}

	return $all_volumes;
}

function get_volume_data($volume, $reason){
	$volume_data = array();
	$volume_data['volume_id'] = $volume['VolumeId'];
	$volume_data['region'] = $volume['AvailabilityZone'];
	$volume_data['volume_type'] = $volume['VolumeType'];
	$volume_data['reason'] = $reason;

	return $volume_data;
}

function check_instances($regions){

	$all_instances = array();
	foreach($regions as $region){
		$ec2Client = new Ec2Client([
				'region' => $region,
				'version' => '2016-11-15',
				'profile' => 'default'
		]);
		$result = $ec2Client->describeInstances();
		foreach($result['Reservations'] as $reservation){
			foreach($reservation['Instances'] as $instance){
				$instance_data = array();

				if(!empty($instance['State']['Name']) && $instance['State']['Name'] == 'running'){
					$tags = $instance['Tags'];
					if(!find_tag($tags, 'Name')){
						$instance_data['instance_id'] = $instance['InstanceId'];
						$instance_data['instance_type'] = $instance['InstanceType'];
						$instance_data['region'] = $instance['Placement']['AvailabilityZone'];
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
