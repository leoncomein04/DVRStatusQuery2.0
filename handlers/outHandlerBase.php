<?php
class outHandler {
    protected $driverobj;
    public function OH_init(&$driverobj) {} 
    public function OH_process() {} 
    public function OH_exit() {} 
	protected function writeLog ($text) {
		return $this->driverobj->writeLog ($text);
	}
	protected function formatSQLErrors ($title,$errors) {
		return $this->driverobj->formatSQLErrors ($title,$errors);
	}
}
