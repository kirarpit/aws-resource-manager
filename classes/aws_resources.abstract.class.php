<?php
require_once 'classes/cloudwatch.class.php';

abstract class AWS_Resources {

	abstract public function is_tagged($resource, $region);

	abstract public function log_resource($resource, $region, $remark);

	abstract public function is_under_utilised($resource, $region);

	public function check_tagging(){
		foreach($this->resources as $region=>$resources){
			foreach($resources as $resource){
				if(!$this->is_tagged($resource, $region)){
					$this->log_resource($resource, $region, 'untagged');
				}
			}
		}
	}

	public function check_under_utilisation(){
		foreach($this->resources as $region=>$resources){
			foreach($resources as $resource){
				$this->cloudWatch = new CloudWatch($this->profile, $region);

				if($this->is_under_utilised($resource, $region)){
					$this->log_resource($resource, $region, 'under-utilised');
				}
			}
		}
	}

	//public function examine_security();

	public function find_tag($tags, $key=''){
		if(empty($key)){
			$key = 'project';
		}

		foreach($tags as $tag){
			if(!empty($tag['Key']) && $tag['Key'] == $key){
				return $tag['Value'];
			}
		}

		return false;
	}

	public function get_remark($error){
		switch ($error) {
			case 'not-in-use':
				$remark = 'resource is not being used';
				break;

			case 'untagged':
				$remark = "'project' tag not found";
				break;

			case 'under-utilised':
				$remark = "resource is under utilised";
				break;

			case 'default':
				$remark = "";
				break;
		}

		return $remark;
	}

	public function get_log(){
		return $this->log;
	}
}

?>
