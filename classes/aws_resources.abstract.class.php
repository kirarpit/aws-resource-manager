<?php

abstract class AWS_Resources {

	public function check_tagging(){
		foreach($this->resources as $region=>$resources){
			foreach($resources as $resource){
				if(!$this->is_tagged($resource, $region)){
					$this->log_resource($resource, $region, 'untagged');
				}
			}
		}
	}

	abstract public function is_tagged($resource, $region);

	abstract public function log_resource($resource, $region, $remark);

	//public function monitor_resources();

	//public function examine_security();

	public function check_resources(){
		$functions = preg_grep("/check_/", get_class_methods(get_class($this)));
		$functions = array_diff($functions, array('check_resources'));

		$result = array();
		foreach($functions as $function){
			$key = ucwords(array_shift(explode('_', get_class($this)))." ".array_pop(explode('_', $function)));
			$result[$key] = $this->$function();
		}

		return $result;
	}

	public function find_tag($tags){
		foreach($tags as $tag){
			if(!empty($tag['Key']) && $tag['Key'] == 'project'){
				return true;
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
