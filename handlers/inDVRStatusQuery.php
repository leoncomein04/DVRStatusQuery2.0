<?php
class DVRStatusQueryServer { // database fields returned for each input to be processed
	public $Port;
	public $Address;
	public $FilePath;
	public $RemoteFilePath;
	public $Name;
	public $Email;    
	public $Market;    
}
function FormatSQLErrors ($title,$errors)
{
    echo $title,"\n";
    foreach ($errors as $error) {
          echo "SQLSTATE: ".$error['SQLSTATE']."\n";
          echo "Code: ".$error['code']."\n";
          echo "Message: ".$error['message']."\n";
    }
}

class inDVRStatusQuery extends inHandler {
    protected $driverobj;
    protected $dbConn;
    protected $dbStmt;  
    protected $trialOnly = false;
	
    public function __construct(){}
	
    public function IH_init(&$driverobj) 
    {
        $this->driverobj = $driverobj;
        try 
        {
			$e1 = $this->driverobj;
			$e2 = $e1->configParams;
			$e3 = $e2->getElementsByTagName ('dbserver');
			$elem = $this->driverobj->configParams->getElementsByTagName ('dbserver');
			$db_server = $elem->item(0)->nodeValue;
			$elem = $this->driverobj->configParams->getElementsByTagName ('dbname');
			$db_database = $elem->item(0)->nodeValue;
			$elem = $this->driverobj->configParams->getElementsByTagName ('dblogin');
			$db_user = $elem->item(0)->nodeValue;
			$elem = $this->driverobj->configParams->getElementsByTagName ('dbpassword');
			$db_passwd = $elem->item(0)->nodeValue;
            
			$connectionOptions = array(
				'Database'=>$db_database
				,'UID'=>$db_user
				,'PWD'=>$db_passwd);
			$this->dbConn = sqlsrv_connect ($db_server, $connectionOptions);
			if( $this->dbConn === false )
			{	
				$this->writeLog ($db_server);
				$this->writeLog ($db_database);
				$this->writeLog ($db_user);
				$this->formatSQLErrors ('IH_init sql connect failed:', sqlsrv_errors() );
				return false;
			}
			
			$elem = $this->driverobj->configParams->getElementsByTagName ('procname');
			$sql = $elem->item(0)->nodeValue;
			$this->dbStmt = sqlsrv_prepare( $this->dbConn, $sql); //, $params);
			if ( $this->dbStmt === false)
			{	
				echo  FormatSQLErrors('IH_init sql prepare failed:', sqlsrv_errors() );
				return false;
			}
			$success = sqlsrv_execute($this->dbStmt);
			if ($success) {
				return true;
			} else {
				echo  FormatSQLErrors('IH_init sql execute failed:', sqlsrv_errors() );
				return false;
			}     
        } catch (Exception $ex) 
        {
			echo 'IH_Init exception:', "\n";
			echo $ex->getMessage(), "\n";
            return false;
        }        
    }
    public function IH_process() 
    {
		$obj = '';       
        try
        {
			$found = false;
			while (!$found) {
				$obj = sqlsrv_fetch_object ($this->dbStmt, 'DVRStatusQueryServer');
				if (!$obj) { // null when no more rows
					sqlsrv_free_stmt( $this->dbStmt);
					sqlsrv_close( $this->dbConn);				
					return false; // at end, nothing else to do
				}

				if ($this->trialOnly) {
					if ((strcasecmp ($obj->Address,'http://97.77.106.242')==0 && strcasecmp ($obj->Port,'80')==0 && strcasecmp ($obj->FilePath,'streamSA')==0) ||
						(strcasecmp ($obj->Address,'http://24.249.158.126')==0 && strcasecmp ($obj->Port,'8090')==0 && strcasecmp ($obj->FilePath,'stream')==0) ||
						(strcasecmp ($obj->Address,'http://NDS3.fednews.com')==0 && strcasecmp ($obj->Port,'80')==0 && strcasecmp ($obj->FilePath,'stream')==0) ||
						(strcasecmp ($obj->Address,'http://videopreview.dyndns.biz')==0 && strcasecmp ($obj->Port,'80')==0 && strcasecmp ($obj->FilePath,'stream')==0) ||
						(strcasecmp ($obj->Address,'http://jwinealt.dyndns.org')==0 && strcasecmp ($obj->Port,'8090')==0 && strcasecmp ($obj->FilePath,'stream')==0) ||
						(strcasecmp ($obj->Address,'http://38.121.115.15')==0 && strcasecmp ($obj->Port,'8099')==0 && strcasecmp ($obj->FilePath,'stream')==0))
					{
						// test the web server
					} else {
						continue; // skip to next row
					}
				}
				
        		$found = true;	
				$this->driverobj->dataObj->resetObject();
				$this->driverobj->dataObj->Port			= $obj->Port;
				$this->driverobj->dataObj->Address		= $obj->Address;
				$this->driverobj->dataObj->FilePath		= $obj->FilePath;
				$this->driverobj->dataObj->RemoteFilePath = $obj->RemoteFilePath;
				$this->driverobj->dataObj->Name			= $obj->Name;
				$this->driverobj->dataObj->Email		= $obj->Email;
				$this->driverobj->dataObj->Market		= $obj->Market;
				if ($this->trialOnly) {
					$this->driverobj->dataObj->Email = 'dfunk@newsdataservice.com'; // 'leoncomein04@gmail.com'; // 
				}
			}

            return true;
        } catch (Exception $ex) 
        {
			$this->writeLog ('IH_process exception:');
			$this->writeLog ($ex->getMessage());
			return false;
        }        
    }
    public function IH_exit() 
    {
		// close the database connection   
		try {
			if ($this->dbStmt != null) {
				sqlsrv_free_stmt($this->dbStmt);
				$this->dbStmt = null;
			}
			if ($this->dbConn != null) {
				sqlsrv_close($this->dbConn);				
				$this->dbConn = null;				
			}
		} catch (Exception $ex) {
			$this->writeLog ('IH_exit exception:');
			$this->writeLog ($ex->getMessage());
		}
    }
}
