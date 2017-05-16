<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\Rds\RdsClient;

$rdsClient = new RdsClient([
    'region' => 'ap-southeast-1',
    'version' => '2014-10-31',
    'profile' => 'default'
]);

//Get all regions
$result = $rdsClient->describeSourceRegions();
foreach($result['SourceRegions'] as $region){
	if($region['Status'] == 'available'){
		$regions[] = $region['RegionName'];
	}
}

//$instances = check_instances($regions);
$snapshots = check_snapshots($regions);
var_dump($snapshots);

function check_instances($regions){

	$all_instances = array();
	foreach($regions as $region){
		$rdsClient = new RdsClient([
				'region' => $region,
				'version' => '2014-10-31',
				'profile' => 'default'
		]);
		$result = $rdsClient->describeDBInstances();
		foreach($result['DBInstances'] as $instance){
			$instance_data = array();

			$tags = get_resource_tags($rdsClient, $instance['DBInstanceArn']);
			if(!find_tag($tags, 'Name')){
				$instance_data['instance_id'] = $instance['DBInstanceIdentifier'];
				$instance_data['instance_type'] = $instance['DBInstanceClass'];
				$instance_data['region'] = $instance['AvailabilityZone'];
			}

			if(!empty($instance_data)){
				$all_instances[] = $instance_data;
			}
		}
	}

	return $all_instances;
}

function check_snapshots($regions){

	$all_snapshots = array();
	foreach($regions as $region){
		$rdsClient = new RdsClient([
				'region' => $region,
				'version' => '2014-10-31',
				'profile' => 'default'
		]);
		$result = $rdsClient->describeDBSnapshots();
		foreach($result['DBSnapshots'] as $snapshot){
			$snapshot_data = array();

			$tags = get_resource_tags($rdsClient, $snapshot['DBSnapshotArn']);

			if(!find_tag($tags, 'Name')){
				$snapshot_data['instance_id'] = $snapshot['DBInstanceIdentifier'];
				$snapshot_data['snapshot_id'] = $snapshot['DBSnapshotIdentifier'];
				$snapshot_data['region'] = $snapshot['AvailabilityZone'];
			}

			if(!empty($snapshot_data)){
				$all_snapshots[] = $snapshot_data;
			}
		}
	}

	return $all_snapshots;
}

function get_resource_tags($rdsClient, $resource_name){
	$result = $rdsClient->listTagsForResource(array(
				'ResourceName' => $resource_name
				));

	return $result['TagList'];
}
