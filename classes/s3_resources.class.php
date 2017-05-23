<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class S3_Resources extends AWS_Resources {

	const VERSION = '2006-03-01';

	private $profile;
	public $S3Client;
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->S3Client = new S3Client([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->get_resources();
	}

	public function get_resources(){

		$buckets = array();
		$result = $this->S3Client->ListBuckets();
		foreach($result['Buckets'] as $bucket){

			$region = $this->get_bucket_location($this->S3Client, $bucket['Name']);
			if(!empty($region)){
				if(!empty($bucket)){
					$buckets[$region][] = $bucket;
				}
			}
		}

		$this->resources = $buckets;
	}

	public function is_tagged($bucket, $region){
		$tags = $this->get_bucket_tags($region, $bucket['Name']);

		if(empty($tags) || !$this->find_tag($tags)){
			return false;
		}

		return true;
	}

	public function log_resource($bucket, $region, $remark){
		$bucket_data = array();

		$bucket_data['bucket_name'] = $bucket['Name'];
		$bucket_data['region'] = $region;
		$bucket_data['remark'] = $this->get_remark($remark);

		$this->log[] = $bucket_data;
	}

	private function get_bucket_tags($region, $bucket_name){
		$S3Client = new S3Client([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);

		try {
			$result = $S3Client->getBucketTagging(array(
						'Bucket' => $bucket_name
						));
		} catch(AwsException $e) {
			return false;
		}

		return $result['TagSet'];
	}

	private function get_bucket_location($S3Client, $bucket_name){
		try {
			$result = $S3Client->getBucketLocation(array(
						'Bucket' => $bucket_name
						));
		} catch(AwsException $e) {
			return false;
		}

		return $result['LocationConstraint'];
	}
}
