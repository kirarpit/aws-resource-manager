<?php

abstract class AWS_Resources {

	const VERSION = 'abstract';

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

	public function is_tagged($tags){
		foreach($tags as $tag){
			if(!empty($tag['Key']) && $tag['Key'] == 'Project'){
				return true;
			}
		}

		return false;
	}
}

?>
