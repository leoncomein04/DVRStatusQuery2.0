<?php
class outPVRMessage1 extends outHandlerWebDB {

    public function __construct () {
        parent::__construct();
    }
	
    public function OH_init(&$driverobj) {
		parent::OH_init($driverobj);
		try {
			if (!isset($this->driverobj->msgId) || strlen($this->driverobj->msgId) < 1) {
				return false;
			}
        	$handlerFileName = $this->driverobj->handlerPath . 'out' . $this->driverobj->msgId . '.php';             
        	require $handlerFileName;
	    	$handlerClass = 'out' . $this->driverobj->msgId;
	    	$this->driverobj->msgTypeOutHandler = new $handlerClass(); //create object
            $rc = $this->driverobj->msgTypeOutHandler->OH_init($this->driverobj);
			if (!$rc) {
				return false;
			}
		} catch (Exception $ex) {
			$this->writeLog ("OH_init 'out' '$this->driverobj->msgId' exception:");
			$this->writeLog ($ex->getMessage());
			header("HTTP/1.1 400 Failure: Message id 'out' '$this->driverobj->msgId' failed.");
			return false;
		}
		return true;
    }
    public function OH_process() {
		try {
			if (isset($this->driverobj->msgTypeOutHandler)) {
        		$rc = $this->driverobj->msgTypeOutHandler->OH_process();
			} else {
				$rc = false;
			}
			return $rc;
        } catch (Exception $ex) {
			$this->writeLog ("OH_process 'out' '$this->driverobj->msgId' exception:");
			$this->writeLog ($ex->getMessage());
            return false;
        }        
    }
    public function OH_exit() {
		if (isset($this->driverobj->msgTypeOutHandler)) {
       		$this->driverobj->msgTypeOutHandler->OH_exit();
		}
		parent::OH_exit ();
		return true;
    }
}
