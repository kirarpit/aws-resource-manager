<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

$buckets = check_buckets();
var_dump($buckets);

function check_buckets(){

	$all_buckets = array();
	$S3Client= new S3Client([
			'region' => 'us-west-2',
			'version' => '2006-03-01',
			'profile' => 'default'
	]);
	$result = $S3Client->ListBuckets();
	foreach($result['Buckets'] as $bucket){
		$bucket_data = array();

		$region = get_bucket_location($S3Client, $bucket['Name']);
		if(!empty($region)){
			$tags = get_bucket_tags($region, $bucket['Name']);

			if(empty($tags) || !find_tag($tags, 'Name')){
				$bucket_data['bucket_name'] = $bucket['Name'];
				$bucket_data['region'] = $region;
			}

			if(!empty($bucket_data)){
				$all_buckets[] = $bucket_data;
			}
		}
	}

	return $all_buckets;
}

function get_bucket_tags($region, $bucket_name){
	$S3Client= new S3Client([
			'region' => $region,
			'version' => '2006-03-01',
			'profile' => 'default'
	]);

	try {
		$result = $S3Client->getBucketTagging(array(
					'Bucket' => $bucket_name
					));
	} catch(AwsException $e) {
		//echo $e->getMessage();
		return false;
	}

	return $result['TagSet'];
}

function get_bucket_location($S3Client, $bucket_name){
	try {
		$result = $S3Client->getBucketLocation(array(
					'Bucket' => $bucket_name
					));
	} catch(AwsException $e) {
		//echo $e->getMessage();
		return false;
	}

	return $result['LocationConstraint'];
}
