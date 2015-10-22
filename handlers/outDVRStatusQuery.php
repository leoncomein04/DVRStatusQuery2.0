<?php
class outDVRStatusQuery extends outHandler {
    protected $driverobj;
    protected $outFileName;
    protected $outFileH;
    
    protected $smtpServer, $smtpPort, $smtpAuthId, $smtpAuthPassword;
    protected $subject, $body, $bcc;
    protected $from, $to;
    protected $webaddress;
    protected $retry;
	protected $curlError;
	protected $curlErrorNumber;
	                
    public function __construct() {}
	
    public function OH_init(&$driverobj) {
        $this->driverobj = $driverobj;
        try{            
            $elem = $this->driverobj->configXML->getElementsByTagName ('smtpServer');
            $this->smtpServer = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('smtpPort');
            $this->smtpPort = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('smtpAuthId');
            $this->smtpAuthId = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('smtpAuthPassword');
            $this->smtpAuthPassword = $elem->item(0)->nodeValue;            
            
            $elem = $this->driverobj->configXML->getElementsByTagName ('emailSubject');
            $this->subject = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('emailBody');
            $this->body = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('emailBCC');
            $this->bcc = $elem->item(0)->nodeValue;
            $elem = $this->driverobj->configXML->getElementsByTagName ('emailFrom');
            $this->from = $elem->item(0)->nodeValue;
            //set the number of times failed email should be attempted to sent. 0=default, no retry
            $this->retry = 0;          
            
            $this->outFileName = $driverobj->getOutputFileName();
            $this->outFileH = fopen($this->outFileName, 'w');                
            
            return true;
        } catch (Exception $ex) {
			$this->writeLog ('output handler init exception:');
			$this->writeLog ($ex->getMessage());
            return false;
        }
    }
    public function OH_process() {
        try {
            $result = true;
			if (strcmp ($this->driverobj->dataObj->Port,"80") == 0) {
				$this->webAddress = $this->driverobj->dataObj->Address."/".$this->driverobj->dataObj->FilePath;    
			} else {	
				$this->webAddress = $this->driverobj->dataObj->Address.":".$this->driverobj->dataObj->Port."/".$this->driverobj->dataObj->FilePath;    
			}
            //check failed addresses to send an email
  			$name = $this->driverobj->dataObj->Name;
            if(!$this->GetUrlResponse($this->webAddress))
            {
				$this->writeLog ("$name\t$this->webAddress\t$this->curlErrorNumber\t$this->curlError");
                $result = $this->SendMail();
            } else {
				$this->writeLog ("$name\t$this->webAddress\tOK");
			}
			return $result;
        } catch (Exception $ex) {
			$this->writeLog ('OH_process exception:');
			$this->writeLog ($ex->getMessage());
            return false;
        }
    }
    public function OH_exit() {
        fclose ($this->outFileH);
    }
    
    private function GetUrlResponse($url,$timeout = 15)
	{
		//set_time_limit(0);
		//$start = microtime(true);
		$this->curlError = '';
		$this->curlErrorNumber = 0;
		$rc = true;
		$ch = curl_init();		
		curl_setopt ($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt ($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt ($ch, CURLOPT_MAXREDIRS, 1); // changed from 0 to 1 2014-12-29
		curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 0);
		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);		
		$response = curl_exec($ch);
		if(false === $response)
		{
			$this->curlError = curl_error($ch);
			$this->curlErrorNumber = curl_errno($ch);
			$rc = false;
		}
		curl_close($ch);
		return $rc;
	}
	
    private function SendMail(){
require_once "class.PHPMailer.php";
require_once "class.smtp.php";

		$subj = $this->subject;
        $subj = str_replace('%Name%',$this->driverobj->dataObj->Name,$subj);
        $subj = str_replace('%Address%',$this->driverobj->dataObj->Address,$subj);
        $subj = str_replace('%Port%',$this->driverobj->dataObj->Port,$subj);
        $subj = str_replace('%FilePath%',$this->driverobj->dataObj->FilePath,$subj);
        $subj = str_replace('%Market%',$this->driverobj->dataObj->Market,$subj);
		
		$bdy = $this->body;
        $bdy = str_replace('%Name%',$this->driverobj->dataObj->Name,$bdy);
        $bdy = str_replace('%Address%',$this->driverobj->dataObj->Address,$bdy);
        $bdy = str_replace('%Port%',$this->driverobj->dataObj->Port,$bdy);
        $bdy = str_replace('%FilePath%',$this->driverobj->dataObj->FilePath,$bdy);
        $bdy = str_replace('%Market%',$this->driverobj->dataObj->Market,$bdy);
        $bdy = str_replace('%webAddress%',$this->webAddress,$bdy);
        $bdy = str_replace('%error%',$this->curlError,$bdy);
		
		$mail = new PHPMailer();
		$mail->setFrom($this->from);
		$mail->addReplyTo($this->from);
		$addr = explode (',', $this->driverobj->dataObj->Email);
		foreach ($addr as $a) {
			$mail->addAddress($a);
		}
		$addr = explode (',', $this->bcc);
		foreach ($addr as $a) {
			$mail->addBCC($a);
		}
		$mail->Subject = $subj;
		$mail->msgHTML($bdy);
		$mail->Host = $this->smtpServer;
		$mail->Port = $this->smtpPort;
		if ($this->smtpAuthId == '') {
			$mail->SMTPAuth = false;
		} else {
			$mail->SMTPAuth = true;
			$mail->Username = $this->smtpAuthId;
			$mail->Password = $this->smtpAuthPassword;
		}
		$mail->Mailer = 'smtp';

		//send the message, check for errors
		if (!$mail->send()) {
			$text = $this->driverobj->dataObj->Email;
			$this->writeLog ("Error writing email: $mail->ErrorInfo : $text");
		}
        return true;
    }
}