<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require 'includes/vendor/autoload.php';
require_once 'classes/aws_resources.abstract.class.php';

use Aws\Lambda\LambdaClient;
use Aws\Exception\AwsException;

class Lambda_Resources extends AWS_Resources {

	const VERSION = '2015-03-31';

	private $profile;
	public $LambdaClient;
	public $regions = array();

	public function __construct($profile) {
		$this->profile = $profile;

		$this->LambdaClient = new LambdaClient([
				'region' => 'ap-southeast-1',
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

		$this->regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ap-northeast-2','ap-south-1','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2');
	}

	public function check_functions(){

		$all_functions = array();
		foreach($this->regions as $region){
			$LambdaClient= new LambdaClient([
					'region' => $region,
					'version' => self::VERSION,
					'profile' => $this->profile['name']
			]);
			$result = $LambdaClient->listFunctions();
			foreach($result['Functions'] as $function){
				$function_data = array();

				$tags = $this->get_function_tags($LambdaClient, $function['FunctionArn']);

				if(empty($tags) || !$this->is_tagged($tags)){
					$function_data['function_name'] = $function['FunctionName'];
					$function_data['region'] = $region;
					$function_data['remark'] = "'Project' Tag Not Found";
				}

				if(!empty($function_data)){
					$all_functions[] = $function_data;
				}
			}
		}

		return $all_functions;
	}

	private function get_function_tags($LambdaClient, $function_name){

		try {
			$result = $LambdaClient->ListTags(array(
						'Resource' => $function_name
						));
		} catch(AwsException $e) {
			//echo $e->getMessage();
			return false;
		}

		$tags = array();
		foreach($result['Tags'] as $key=>$value){
			$tags[] = array('Key'=>$key, 'Value'=>$value);
		}

		return $tags;
	}
}
