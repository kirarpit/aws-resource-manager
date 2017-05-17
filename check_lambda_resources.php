<?php
require 'includes/vendor/autoload.php';
require_once 'aws_utility.php';
use Aws\Lambda\LambdaClient;
use Aws\Exception\AwsException;

//Get all regions
$regions = array('us-east-2','us-east-1','us-west-1','us-west-2','ap-northeast-2','ap-south-1','ap-southeast-1','ap-southeast-2','ap-northeast-1','eu-central-1','eu-west-1','eu-west-2');

$functions = check_functions($regions);
var_dump($functions);

function check_functions($regions){

	$all_functions = array();
	foreach($regions as $region){
		$LambdaClient= new LambdaClient([
				'region' => $region,
				'version' => '2015-03-31',
				'profile' => 'default'
		]);
		$result = $LambdaClient->listFunctions();
		foreach($result['Functions'] as $function){
			$function_data = array();

			$tags = get_function_tags($LambdaClient, $function['FunctionArn']);

			if(empty($tags) || !find_tag($tags, 'Name')){
				$function_data['function_name'] = $function['FunctionName'];
				$function_data['region'] = $region;
			}

			if(!empty($function_data)){
				$all_functions[] = $function_data;
			}
		}
	}

	return $all_functions;
}

function get_function_tags($LambdaClient, $function_name){

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
