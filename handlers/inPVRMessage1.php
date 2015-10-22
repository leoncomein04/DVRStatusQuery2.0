<?php

class inPVRMessage1 extends inHandlerWebDB {
	protected	$msgProcessed = false;
		
    public function __construct () {
        parent::__construct();
    }
	
    public function IH_init(&$driverobj) {
		parent::IH_init($driverobj);
		$text = $this->getInputParam ('p', false);
		if (isset($text) && strlen(trim($text)) > 0) {
			$this->driverobj->pvrId = $text;
		} else {
			header("HTTP/1.1 400 Failure: Missing required parameter 'p'.");
			return false;
		}
		
		$text = $this->getInputParam ('m', false);
		if (!isset($text) || strlen(($this->driverobj->msgId = trim($text))) < 1) {
			header("HTTP/1.1 400 Failure: Missing required parameter 'm'.");
			return false;
		}
		if ($this->driverobj->msgId != 'MPEG2_recording_begin' &&
			$this->driverobj->msgId != 'MPEG2_recording_end' &&
			$this->driverobj->msgId != 'MPEG2_moved' &&
			$this->driverobj->msgId != 'MPEG4_moved' &&
			$this->driverobj->msgId != 'MPEG4_recording_end') {
			header("HTTP/1.1 400 Failure: Message id '{$this->driverobj->msgId}' is not supported.");
			return false;
		}
		// we have a message to process, load the input handler for that message type
		try {
        	$handlerFileName = $this->driverobj->handlerPath . 'in' . $this->driverobj->msgId . '.php';             
        	require $handlerFileName;
        	$handlerClass = 'in' . $this->driverobj->msgId;             
	    	$this->driverobj->msgTypeInHandler = new $handlerClass(); //create object
            $rc = $this->driverobj->msgTypeInHandler->IH_init($this->driverobj);
			if (!$rc) {
				return false;
			}
		} catch (Exception $ex) {
			header("HTTP/1.1 400 Failure: Message id 'in' '$this->driverobj->msgId' failed.");
			return false;
		}
		return true;
    }
    public function IH_process() 
    {
        try
        {
			if ($this->msgProcessed) { // test for message already processed
				return false;		   // quit if yes
			}
			$this->msgProcessed = true;
			$this->driverobj->dataObj->resetObject();
            $rc = $this->driverobj->msgTypeInHandler->IH_process();
			if (!$rc) {
				return false;
			}
			return true;
        } catch (Exception $ex) 
        {
			$this->writeLog ("$this->driverobj->msgId IH_process exception:");
			$this->writeLog ($ex->getMessage());
            return false;
        }        
    }
    public function IH_exit() 
    {
        if (isset($this->driverobj->msgTypeInHandler)) {
            $this->driverobj->msgTypeInHandler->IH_exit();
		}
		parent::IH_exit ();
 		return true;
	}
	
	// ---------- show messages ---------------------  in driver ?
	//protected function outputResponse () {
	//	header('HTTP/1.1 200 OK');
	//	echo implode ($this->driverobj->htmlOut); 
	//}
}
