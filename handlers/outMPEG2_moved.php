<?php

require_once 'outHandlerRS.php';

class outMPEG2_moved extends outHandlerRS {
		
	public function __construct() {}

    public function OH_init(&$driverobj) 
    {
		parent::OH_init($driverobj);
		return true;
    }
    public function OH_process() 
    {
		return true;        
    }
    public function OH_exit() 
    {
		return parent::OH_exit ();
	}
}
