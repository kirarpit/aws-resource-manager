<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Lambda\LambdaClient;
use Aws\Exception\AwsException;

class Lambda_Resources extends AWS_Resources {

	const VERSION = '2015-03-31';

	public $profile;
	public $LambdaClient;
	public $regions = array();
	public $resources = array();
	public $log = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->LambdaClient = new LambdaClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ap-northeast-2','ap-south-1','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2');
		$this->get_resources();
	}

	public function get_resources(){

		$functions = array();
		foreach($this->regions as $region){
			$LambdaClient= new LambdaClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $LambdaClient->listFunctions();
			foreach($result['Functions'] as $function){
				if(!empty($function)){
					$functions[$region][] = $function;
				}
			}
		}

		$this->resources = $functions;
	}

	public function is_tagged($function, $region){

		$LambdaClient= new LambdaClient([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $this->profile['name']
		]);

		$tags = $this->get_function_tags($LambdaClient, $function['FunctionArn']);

		if(empty($tags) || !$this->find_tag($tags)){
			return false;
		}

		return true;
	}

	public function is_under_utilised($instance, $region){
		return false;
	}

	public function log_resource($function, $region, $remark){
		$function_data = array();

		$function_data['function_name'] = $function['FunctionName'];
		$function_data['region'] = $region;
		$function_data['remark'] = $this->get_remark($remark);

		$this->log[] = $function_data;
	}


	private function get_function_tags($LambdaClient, $function_name){

		try {
			$result = $LambdaClient->ListTags(array(
						'Resource' => $function_name
						));
		} catch(AwsException $e) {
			return false;
		}

		$tags = array();
		foreach($result['Tags'] as $key=>$value){
			$tags[] = array('Key'=>$key, 'Value'=>$value);
		}

		return $tags;
	}
}
