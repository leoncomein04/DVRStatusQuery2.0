<?php

// base program for all RS tables

class tblBase {
	public	$driverobj;
	public	$dbStmt;
	public	$colNames;
	public	$idColName;		// id column name(s), string or array
	public	$tblName;
	public	$rcExecute;
	public	$rcFetch;
	public	$errorExecute;
	public	$phpType;
	public	$sqlType;
	
	public function __construct (&$driverobj) {
		$this->driverobj = $driverobj;	
	}

	protected function getSQLPHPTypes ($val) {
		if (is_string ($val)) {
			$l = strlen ($val);
			if ($l < 1) { $l = 1; }
			$this->phpType = SQLSRV_PHPTYPE_STRING('UTF-8');
			$this->sqlType = SQLSRV_SQLTYPE_VARCHAR($l);
			return;
		}
		if (is_int ($val)) {
			$this->phpType = SQLSRV_PHPTYPE_INT;
			$this->sqlType = SQLSRV_SQLTYPE_INT;
			return;
		}
		if (is_float ($val)) {
			$this->phpType = SQLSRV_PHPTYPE_FLOAT;
			$this->sqlType = SQLSRV_SQLTYPE_FLOAT;
			return;
		}
		$valClass =  get_class ($val);
		if ($valClass === 'DateTime') {
			$this->phpType = SQLSRV_PHPTYPE_DATETIME;
			$this->sqlType = SQLSRV_SQLTYPE_DATETIME;
			return;			
		}
		$this->phpType = SQLSRV_PHPTYPE_STRING('UTF-8');
		$this->sqlType = SQLSRV_SQLTYPE_VARCHAR(100);
	}
	
	public function getByKey ($conn, $keyName, $keyValue, $keyComp) {
		// clear possible error indicators
		unset ($this->rcPrepare);
		unset ($this->errorPrepare);
		unset ($this->rcExecute);
		unset ($this->errorExecute);
		unset ($this->rcFetch);
	
		if (!isset($this->dbStmt)) {
			$sql = 'SELECT ' . $this->getColumnNameString();
			$sql.= ' FROM ' . $this->tblName;
			$whereAnd = ' WHERE ';
			if (is_array ($keyName)) {
				$x = 0;
				foreach ($keyName as $key => $value) {			
					$sql .= $whereAnd;
					$sql .= $value;
					$sql .= ($keyComp==null)?'=':$keyComp[$x++];
					$sql .= '?';
					$whereAnd = ' AND ';
				}
			} else {
				$sql .= $whereAnd;			
				$sql .= $keyName;			
				$sql .= ($keyComp==null)?'=':$keyComp;			
				$sql .= '?';			
			}

			$aVal = [];
			if (is_array ($keyValue)) {
				foreach ($keyValue as $key => $value) {
					$this->getSQLPHPTypes ($keyValue[count($aVal)]);
					$aVal [] = array (&$keyValue[count($aVal)], SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
				}
			} else {
				$this->getSQLPHPTypes ($keyValue);
				$aVal [] = array (&$keyValue, SQLSRV_PARAM_IN,  $this->phpType, $this->sqlType);
			}
			
			$this->dbStmt = sqlsrv_prepare( $conn, $sql, $aVal);
				
			if ($this->dbStmt === false)
			{	
				$this->rcPrepare = false;
				$this->errorPrepare = sqlsrv_errors();
				$this->driverobj->formatSQLErrors("{$this->driverobj->msgId} OH_init sql prepare failed:". sqlsrv_errors() );
				return false;
			}
		}
		$this->rcExecute = sqlsrv_execute ($this->dbStmt);
		if (!$this->rcExecute) { // false
			$this->errorExecute = sqlsrv_errors();
			$this->driverobj->formatSQLErrors("{$this->driverobj->msgId} OH_process get data failed (1):", sqlsrv_errors());
			//$this->driverobj->writeLog ("P=$this->idPortal F=$this->idFolder PP=$this->perPage PN=$this->pageNumber");
			return false;
		}
		return $this->fetchNextRow ();
	}
	public function fetchNextRow () {
		$row = sqlsrv_fetch_array ($this->dbStmt, SQLSRV_FETCH_ASSOC); // null=no more results false=error
		if ($row == null) {
			$this->rcFetch = false;
			return false; // not found
		} else {
			$this->rcFetch = true;
			foreach ($this->colNames as $key => $value) {
				$this->$key = $row [$key];
			}
			return true;
		}
	}
	public function getNextResult () {
		$next_result = sqlsrv_next_result($this->dbStmt); // null=no more results false=error
		if ($next_result === false) { 
			$this->rcExecute = false;
			$this->errorExecute = sqlsrv_errors();
			$this->driverobj->formatSQLErrors("{$this->driverobj->msgId} OH_process get data failed (2):", sqlsrv_errors());
			//$this->driverobj->writeLog ("P=$this->idPortal F=$this->idFolder PP=$this->perPage PN=$this->pageNumber");
			return false;
		}
		if ($next_result == null) { // no more results
			$this->rcExecute = false;
			unset ($this->errorExecute);
			return false;
		}
		$this->rcExecute = true;
		unset ($this->errorExecute);
		return true;
	}
	
	public function getById ($conn, $keyValue, $keyComp) {
		return $this->getByKey ($conn, $this->idColName, $keyValue, $keyComp);
	}

	public function updateByKey ($conn, $keyName, $keyValue, $colName, $colValue) {
		$sql = 'UPDATE ' . $this->tblName . ' SET ';

		$delim = '';
		if (is_array ($colName)) {
			foreach ($colName as $key => $value) {			
				$sql .= $delim . $value . '=?';
				if ($delim === '') { $delim=','; }
			}
		} else {
			$sql .= $colName . '=?';			
		}
	
		$whereAnd = ' WHERE ';
		if (is_array ($keyName)) {
			$x = 0;
			foreach ($keyName as $key => $value) {			
				$sql .= $whereAnd . $value . '=?';
				$whereAnd = 'AND ';
			}
		} else {
			$sql .= $whereAnd . $keyName . '=?';			
		}

		$aVal = [];

		if (is_array ($colValue)) {
			foreach ($colValue as $key => $value) {
				$this->getSQLPHPTypes ($colValue[count($aVal)]);
				$aVal [] = array (&$colValue[count($aVal)], SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
			}
		} else {
			$this->getSQLPHPTypes ($colValue);
			$aVal [] = array (&$colValue, SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
		}

		if (is_array ($keyValue)) {
			foreach ($keyValue as $key => $value) {
				//TODO use key/value to get parameter types
				$this->getSQLPHPTypes ($keyValue[count($aVal)]);
				$aVal [] = array (&$keyValue[count($aVal)], SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
			}
		} else {
			$this->getSQLPHPTypes ($keyValue);
			$aVal [] = array (&$keyValue, SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
		}

		$stmt = sqlsrv_query ($this->driverobj->dbRS, $sql, $aVal);
		if ($stmt === false)
		{	
			$this->errorPrepare = sqlsrv_errors();
			$this->driverobj->formatSQLErrors('update ' . $this->tblName . ' failed:', sqlsrv_errors() );
			return false;
		}
		$this->driverobj->dbStmtClose ($stmt);
		return true;
	}

	public function doInsert ($conn) {
		$colNames = $this->getColumnNameString ();

		$sql = 'INSERT ' . $this->tblName . " ($colNames) VALUES (";

		$aVal = [];
		$aCols = explode (',', $colNames);

		$delim = '';
		foreach ($aCols as $key => $value) {			
			$sql .= $delim . '?';
			if ($delim === '') { $delim=','; }
			$this->getSQLPHPTypes ($this->$value);
			$aVal [] = array (&$this->$value, SQLSRV_PARAM_IN, $this->phpType, $this->sqlType);
		}
	
		$sql .= ')';

		$stmt = sqlsrv_query ($this->driverobj->dbRS, $sql, $aVal);
		if ($stmt === false)
		{	
			$this->errorPrepare = sqlsrv_errors();
			$this->driverobj->formatSQLErrors('insert ' . $this->tblName . ' failed:', sqlsrv_errors() );
			return false;
		}
		$this->driverobj->dbStmtClose ($stmt);
		return true;
	}
	
	public function close () {
		if (isset ($this->dbStmt)) {
			$this->driverobj->dbStmtClose ($this->dbStmt);
			unset ($this->dbStmt);
		}
		if (isset ($this->colNames)) {
			//$this->driverobj->dbStmtClose ($this->dbStmt);
			unset ($this->colNames);
		}
		// we didn't create conections, let their creator close them
		//if (isset ($this->dbConn)) {
		//	$this->driverobj->dbClose ($this->dbconn);
		//	unset ($this->dbConn);
		//}
	}

	public function getColumnNameString () {
		$tblVars = get_class_vars(get_class($this));
		$baseVars = get_class_vars('tblBase');
		$this->colNames = array_diff_key ($tblVars, $baseVars);	
		$delim = '';
		$out = '';
		foreach ($this->colNames as $key => $value) {			
			$out .= $delim . $key;
			if ($delim === '') {
				$delim = ',';
			}
		}
		return $out;
	}

	public function makeNVArray () {
		$tblVars = get_class_vars(get_class($this));
		$baseVars = get_class_vars('tblBase');
		$this->colNames = array_diff_key ($tblVars, $baseVars);	
		$out = [];
		foreach ($this->colNames as $key => $value) {			
			$out [$key] = $this->$key;
		}
		return $out;
	}
}
	
