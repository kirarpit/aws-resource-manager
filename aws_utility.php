<?php

function find_tag($tags, $key){
	foreach($tags as $tag){
		if(!empty($tag['Key']) && $tag['Key'] == $key){
			return $tag['Value'];
		}
	}

	return false;
}

?>
