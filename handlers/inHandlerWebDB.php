<?php
require_once 'handlerBase.php';

class inHandlerWebDB extends handlerBase {
	protected $inDirIter;
	protected $inDirFile;
	protected $useDirIterCurrent = false;
	
	public function __construct () {
        parent::__construct();
    }
	
	public function IH_init(&$driverobj) {
        $this->driverobj = $driverobj;
	} 
    public function IH_process() {}
    public function IH_exit() {}

	public function getInputParam ($name, $bRequired, $default='') {
		return $this->driverobj->getInputParam ($name, $bRequired, $default);
	}
	protected function exitWithError ($msg) {
		$this->writeLog($msg);
		header("HTTP/1.1 400 Failure: $msg");
		return false;
	}
}
