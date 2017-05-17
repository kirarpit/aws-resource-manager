<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\Sqs\SqsClient;
use Aws\Exception\AwsException;

//Get all regions
$regions = array('ap-southeast-1', 'us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-south-1','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');

$queues = check_queues($regions);
var_dump($queues);

function check_queues($regions){

	$all_queues = array();
	foreach($regions as $region){
		$SqsClient= new SqsClient([
				'region' => $region,
				'version' => '2012-11-05',
				'profile' => 'default'
		]);
		$result = $SqsClient->listQueues();
		foreach($result['QueueUrls'] as $queue){
			$queue_data = array();

			$tags = get_queue_tags($SqsClient, $queue['CacheClusterId'], $region);

			if(empty($tags) || !find_tag($tags, 'Name')){
				$queue_data['queue_name'] = $queue['CacheClusterId'];
				$queue_data['region'] = $region;
			}

			if(!empty($queue_data)){
				$all_queues[] = $queue_data;
			}
		}
	}

	return $all_queues;
}

function get_queue_tags($SqsClient, $queue_name, $region){
	$resource_name = "arn:aws:elasticache:$region:317392809052:queue:$queue_name";

	try {
		$result = $SqsClient->ListTagsForResource(array(
					'ResourceName' => $resource_name
					));
	} catch(AwsException $e) {
		echo $e->getMessage();
		return false;
	}

	return $result['TagList'];
}
