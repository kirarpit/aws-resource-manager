<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\ElastiCache\ElastiCacheClient;
use Aws\Exception\AwsException;

class Elasticache_Resources extends AWS_Resources {

	const VERSION = '2015-02-02';

	public $profile;
	public $ElastiCacheClient;
	public $regions = array();
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$ElastiCacheClient= new ElastiCacheClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ca-central-1','ap-south-1','ap-northeast-2','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2','sa-east-1');
		$this->get_resources();
	}

	public function get_resources(){

		$clusters = array();
		foreach($this->regions as $region){
			$ElastiCacheClient= new ElastiCacheClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $ElastiCacheClient->describeCacheClusters();
			foreach($result['CacheClusters'] as $cluster){
				if(!empty($cluster)){
					$clusters[$region][] = $cluster;
				}
			}
		}

		$this->resources = $clusters;
	}

	public function is_under_utilised($instance, $region){
		return false;
	}

	public function is_tagged($cluster, $region){

		$ElastiCacheClient= new ElastiCacheClient([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);

		$tags = $this->get_cluster_tags($ElastiCacheClient, $cluster['CacheClusterId'], $region);

		if(empty($tags) || !$this->find_tag($tags)){
			return false;
		}

		return true;
	}

	public function log_resource($cluster, $region, $remark){
		$cluster_data = array();

		$cluster_data['cluster_name'] = $cluster['CacheClusterId'];
		$cluster_data['region'] = $region;
		$cluster_data['remark'] = $this->get_remark($remark);

		$this->log[] = $cluster_data;
	}

	private function get_cluster_tags($ElastiCacheClient, $cluster_name, $region){
		$resource_name = "arn:aws:elasticache:$region:".$this->profile['account_number'].":cluster:$cluster_name";

		try {
			$result = $ElastiCacheClient->ListTagsForResource(array(
						'ResourceName' => $resource_name
						));
		} catch(AwsException $e) {
			return false;
		}

		return $result['TagList'];
	}
}
