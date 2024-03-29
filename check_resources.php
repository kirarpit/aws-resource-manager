<?php
error_reporting(E_ERROR); ini_set('display_errors', 1);
require_once 'aws_utility.php';

foreach($profiles as $profile){
	$html = '';
	$results = array();

	foreach (glob("/var/www/cloudwatch/classes/*.class.php") as $filename){
		if(preg_match('/abstract/', $filename) || preg_match('/cloudwatch\.class/', $filename)){
			continue;
		}

		include_once $filename;
		$class = get_class_name($filename);
		$object = new $class($profile);

		if(get_parent_class($object) == 'AWS_Resources'){
			$object->check_tagging();
			$object->check_under_utilisation();

			$data = explode('_', $class);
			array_pop($data);
			$key = ucwords(implode(' ', $data));

			$results[$key][] = $object->get_log();
		}
	}

	foreach($results as $key=>$result){
		foreach($result as $value){
			if(!empty($value)){
				$html .= generate_table($value, $key);
			}
		}
	}

	$html = wordwrap($html, 75, "\n");
	//mail("arpit@mysmartprice.com", "AWS Resources - {$profile['display_name']}", $html, 'Content-Type: text/html');
	mail("arpit@mysmartprice.com, arun@mysmartprice.com", "AWS Resources - {$profile['display_name']}", $html, 'Content-Type: text/html');
}

function generate_table($data, $caption){

	ob_start();
	?>

	<table width='100'  border="2" align="left" cellpadding="5" cellspacing="1" class="table" style="margin-top: 20px;">
	  <caption><?php echo $caption; ?></caption>
	 <tr>
	    <?php
		foreach(array_keys($data[0]) as $colName) {
			echo "<th>$colName</th>";
		}
	    ?>
	 </tr>

	    <?php
	       foreach(array_values($data) as $values) {
		  echo "<tr>";
		  echo '<td>'.implode("</td><td>", $values).'</td>';
		  echo "</tr>";
	       }
	    ?>
	 </table>
	<?php 
	$table = ob_get_clean();

	    return $table;
}
