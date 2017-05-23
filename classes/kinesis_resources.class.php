<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Kinesis\KinesisClient;

class Kinesis_Resources extends AWS_Resources {

	const VERSION = '2013-12-02';

	private $profile;
	public $kinesisClient;
	public $regions = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->KinesisClient = new KinesisClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-south-1','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');
	}

	public function check_streams(){

		$all_streams = array();
		foreach($this->regions as $region){
			$kinesisClient = new KinesisClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $kinesisClient->ListStreams();
			foreach($result['StreamNames'] as $stream){
				$stream_data = array();

				$tags = $this->get_stream_tags($kinesisClient, $stream);

				if(!$this->is_tagged($tags)){
					$stream_data['stream_name'] = $stream;
					$stream_data['region'] = $region;
					$stream_data['remark'] = $this->get_remark('untagged');
				}

				if(!empty($stream_data)){
					$all_streams[] = $stream_data;
				}
			}
		}

		return $all_streams;
	}

	public function get_stream_tags($kinesisClient, $stream_name){
		$result = $kinesisClient->listTagsForStream(array(
					'StreamName' => $stream_name
					));

		return $result['Tags'];
	}
}
