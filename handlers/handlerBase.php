<?php

class handlerBase {
    protected $driverobj;
	
    public function __construct () {
	}
	
    public function OH_init(&$driverobj) {
        $this->driverobj = $driverobj;
	} 
	protected function writeLog ($text) {
		return $this->driverobj->writeLog ($text);
	}
	protected function formatSQLErrors ($title,$errors) {
		return $this->driverobj->formatSQLErrors ($title,$errors);
	}
	protected function cleanupText ($text) {
		return $this->driverobj->cleanupText ($text);
	}
	public function beginsWith( $str, $sub ) {
		return $this->driverobj->beginsWith ($str, $sub);
	}
	public function endsWith ($str, $sub) {
		return $this->driverobj->endsWith($str, $sub);
	}
	protected function getDateTimeFromString ($in) { // yyyymmdd_hhmmss
		return DateTime::createFromFormat ('Ymd*His', $in, $this->driverobj->dtzUTC); // new DateTimeZone('UTC')); // false if invalid date format
	}
	protected function getTimeInMSec ($dt) {
		$ts = $dt->getTimestamp ();
		$hh = intval(date('H', $ts));
		$mm = intval(date('i', $ts)); 
		$ss = intval(date('s', $ts)); 
		return (($hh * 3600) + ($mm * 60) + ($ss)) * 1000;  // msec 
	}
}
