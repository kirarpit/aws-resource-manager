<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\ElastiCache\ElastiCacheClient;
use Aws\Exception\AwsException;

//Get all regions
$regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-south-1','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');

$clusters = check_clusters($regions);
var_dump($clusters);

function check_clusters($regions){

	$all_clusters = array();
	foreach($regions as $region){
		$ElastiCacheClient= new ElastiCacheClient([
				'region' => $region,
				'version' => '2015-02-02',
				'profile' => 'default'
		]);
		$result = $ElastiCacheClient->describeCacheClusters();
		foreach($result['CacheClusters'] as $cluster){
			$cluster_data = array();

			$tags = get_cluster_tags($ElastiCacheClient, $cluster['CacheClusterId'], $region);

			if(empty($tags) || !find_tag($tags, 'Name')){
				$cluster_data['cluster_name'] = $cluster['CacheClusterId'];
				$cluster_data['region'] = $region;
			}

			if(!empty($cluster_data)){
				$all_clusters[] = $cluster_data;
			}
		}
	}

	return $all_clusters;
}

function get_cluster_tags($ElastiCacheClient, $cluster_name, $region){
	$resource_name = "arn:aws:elasticache:$region:317392809052:cluster:$cluster_name";

	try {
		$result = $ElastiCacheClient->ListTagsForResource(array(
					'ResourceName' => $resource_name
					));
	} catch(AwsException $e) {
		echo $e->getMessage();
		return false;
	}

	return $result['TagList'];
}
