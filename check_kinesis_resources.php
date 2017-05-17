<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\Kinesis\KinesisClient;

//Get all regions
$regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-south-1','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');

$streams = check_streams($regions);
var_dump($streams);

function check_streams($regions){

	$all_streams = array();
	foreach($regions as $region){
		$kinesisClient = new KinesisClient([
				'region' => $region,
				'version' => '2013-12-02',
				'profile' => 'default'
		]);
		$result = $kinesisClient->ListStreams();
		foreach($result['StreamNames'] as $stream){
			$stream_data = array();

			$tags = get_stream_tags($kinesisClient, $stream);

			if(!find_tag($tags, 'Name')){
				$stream_data['stream_name'] = $stream;
				$stream_data['region'] = $region;
			}

			if(!empty($stream_data)){
				$all_streams[] = $stream_data;
			}
		}
	}

	return $all_streams;
}

function get_stream_tags($kinesisClient, $stream_name){
	$result = $kinesisClient->listTagsForStream(array(
				'StreamName' => $stream_name
				));

	return $result['Tags'];
}
