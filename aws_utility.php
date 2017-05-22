<?php
$profiles = array(
		array('name'=>'cloudwatch-india','account_number'=>'317392809052','display_name'=>'AWS Indian Account'),
		array('name'=>'cloudwatch-old-account','account_number'=>'434528471519','display_name'=>'Old AWS Account')
		);

function get_class_name($filename){
	$filename = basename($filename, '.class.php');
	$class_name = implode('_', array_map('ucfirst', explode('_', $filename)));

	return $class_name;
}

?>
