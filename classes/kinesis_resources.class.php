<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Kinesis\KinesisClient;

class Kinesis_Resources extends AWS_Resources {

	const VERSION = '2013-12-02';

	public $profile;
	public $kinesisClient;
	public $regions = array();
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->KinesisClient = new KinesisClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->regions = array('ap-south-1','ap-southeast-1','us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-northeast-2','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');
		$this->get_resources();
	}

	public function get_resources(){

		$streams = array();
		foreach($this->regions as $region){
			$kinesisClient = new KinesisClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $kinesisClient->ListStreams();
			foreach($result['StreamNames'] as $stream){
				if(!empty($stream)){
					$stream = $kinesisClient->describeStream(array(
								'StreamName' => $stream
								));
					$streams[$region][] = $stream['StreamDescription'];
				}
			}
		}

		$this->resources = $streams;
	}

	public function is_tagged($stream, $region){

		$kinesisClient = new KinesisClient([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);

		$tags = $this->get_stream_tags($kinesisClient, $stream['StreamName']);

		if(!$this->find_tag($tags)){
			return false;
		}

		return true;
	}

	public function is_under_utilised($stream, $region){
		$namespace = 'AWS/Kinesis';

		$dimensions = array(array(
					'Name' => 'StreamName',
					'Value' => $stream['StreamName'],
					));

		if(!empty($stream['StreamStatus']) && $stream['StreamStatus'] == 'ACTIVE'){
			$launch_time = strtotime($stream['StreamCreationTimestamp']);
			if($launch_time > strtotime('-2 day')){
				return false;
			}

			$incoming_bytes_stats = $this->cloudWatch->get_incoming_bytes_stats($namespace, $dimensions);
			$incoming_bytes = max(array_column($incoming_bytes_stats['Datapoints'], 'Maximum'));

			$put_records_stats = $this->cloudWatch->get_put_records_stats($namespace, $dimensions);
			$put_records = max(array_column($put_records_stats['Datapoints'], 'Maximum'));

			$get_records_stats = $this->cloudWatch->get_get_records_stats($namespace, $dimensions);
			$get_records = max(array_column($get_records_stats['Datapoints'], 'Maximum'));

			//echo "$get_records $put_records $incoming_bytes & stream_id:{$stream['StreamName']}".PHP_EOL;
			$GPrecords = (int)(($put_records + $get_records)/1000);

			if($GPrecords < 100){
				return true;
			}
		}

		return false;
	}

	public function log_resource($stream, $region, $remark){
		$stream_data = array();

		$stream_data['stream_name'] = $stream['StreamName'];
		$stream_data['region'] = $region;
		$stream_data['remark'] = $this->get_remark($remark);

		$this->log[] = $stream_data;
	}

	public function get_stream_tags($kinesisClient, $stream_name){
		$result = $kinesisClient->listTagsForStream(array(
					'StreamName' => $stream_name
					));

		return $result['Tags'];
	}
}
