<?php

class inMPEG2_moved extends inHandlerWebDB {

	public function __construct() {}

    public function IH_init(&$driverobj) 
    {
		parent::IH_init($driverobj);

		$t = $this->driverobj->getInputParam ('recid', false, '');
		if ($t === '') {
			header("HTTP/1.1 400 Failure: Required parameter 'recid' is not supplied or is invalid.");
			return false;
		}

		return true;        
    }
    public function IH_process() 
    {
		return true;
    }
    public function IH_exit() 
    {
		parent::IH_exit ();
 		return true;
	}
}
