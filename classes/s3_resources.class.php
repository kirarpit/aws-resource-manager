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
	public $regions = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->S3Client = new S3Client([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);
	}

	public function check_buckets(){

		$all_buckets = array();
		$S3Client= new S3Client([
				'region' => 'us-west-2',
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);
		$result = $S3Client->ListBuckets();
		foreach($result['Buckets'] as $bucket){
			$bucket_data = array();

			$region = $this->get_bucket_location($S3Client, $bucket['Name']);
			if(!empty($region)){
				$tags = $this->get_bucket_tags($region, $bucket['Name']);

				if(empty($tags) || !$this->is_tagged($tags)){
					$bucket_data['bucket_name'] = $bucket['Name'];
					$bucket_data['region'] = $region;
					$bucket_data['remark'] = $this->get_remark('untagged');
				}

				if(!empty($bucket_data)){
					$all_buckets[] = $bucket_data;
				}
			}
		}

		return $all_buckets;
	}

	private function get_bucket_tags($region, $bucket_name){
		$S3Client= new S3Client([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
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

	private function get_bucket_location($S3Client, $bucket_name){
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
}
