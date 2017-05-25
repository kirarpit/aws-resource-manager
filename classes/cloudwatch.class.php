<?php
set_include_path(get_include_path().PATH_SEPARATOR.'/var/www/cloudwatch');

require_once 'includes/vendor/autoload.php';

use Aws\CloudWatch\CloudWatchClient;

class CloudWatch {

	const VERSION = '2010-08-01';

	public $cloudWatchClient;
	public $metric;
	public $namespace;
	public $dimensions;
	public $statistics;
	public $start_time;
	public $end_time;
	public $period;

	public function __construct($profile, $region) {

		$this->cloudWatchClient = new cloudWatchClient([
				'region' => $region,
				'version' => self::VERSION,
				'profile' => $profile['name']
		]);

	}

	public function set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		if(empty($namespace) || empty($dimensions)){
			throw new Exception('Namespace and Dimensions can\'t be empty!');
		}else{
			$this->namespace = $namespace;
			$this->dimensions = $dimensions;
		}

		if(empty($statistics)){
			$statistics = array('Maximum');
		}
		if(empty($start_time)){
			$start_time = date('Y-m-d', strtotime('-1 day'));
		}
		if(empty($end_time)){
			$end_time = date('Y-m-d');
		}
		if(empty($period)){//TODO: make it a function of specified time interval
			$period = 7200;
		}

		$this->statistics = $statistics;
		$this->start_time = $start_time;
		$this->end_time = $end_time;
		$this->period = $period;
	}

	public function get_result(){
		$result = $this->cloudWatchClient->getMetricStatistics(array(
					'Namespace' => $this->namespace,
					'MetricName' => $this->metric,
					'Dimensions' => $this->dimensions,
					'StartTime' => $this->start_time,
					'EndTime' => $this->end_time,
					'Period' => $this->period,
					'Statistics' => $this->statistics
					));

		return $result;
	}

	public function get_network_in_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'NetworkIn';

		return $this->get_result();
	}

	public function get_network_out_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'NetworkOut';

		return $this->get_result();
	}

	public function get_cpu_utilisation_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'CPUUtilization';

		return $this->get_result();
	}

	public function get_volume_read_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'VolumeReadOps';

		return $this->get_result();
	}

	public function get_volume_write_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'VolumeWriteOps';

		return $this->get_result();
	}

	public function get_incoming_bytes_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'IncomingBytes';

		return $this->get_result();
	}

	public function get_put_records_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'PutRecords.Bytes';

		return $this->get_result();
	}

	public function get_get_records_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'GetRecords.Bytes';

		return $this->get_result();
	}

	public function get_free_memory_stats($namespace, $dimensions, $statistics, $start_time, $end_time, $period){

		$this->set_metric_params($namespace, $dimensions, $statistics, $start_time, $end_time, $period);
		$this->metric = 'FreeableMemory';

		return $this->get_result();
	}
}
