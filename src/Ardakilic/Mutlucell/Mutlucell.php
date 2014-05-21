<?php namespace Ardakilic\Mutlucell;

/**
 * Laravel 4 Mutlucell SMS
 * @author Arda Kılıçdağı <ardakilicdagi@gmail.com>
 * @web http://arda.pw
 *
*/

class Mutlucell {

	protected $app;
	protected $config;
	protected $lang;
	protected $code;
	//protected $success;

    public function __construct($app) {
		$this->app    = $app;
		$locale       = $app['config']['app.locale'];
		$this->lang   = $app['translator']->get("mutlucell::{$locale}");
		$this->config = $app['config']['mutlucell::config'];
    }
    
    /**
     * Send same bulk message to many people 
     * @param $recipents array recipents
     * @param $message string message to be sent
     * @param $senderID string originator/sender id (may be a text or number)
     * @return status
     */
    public function sendBulk($recipents, $message='', $date='', $senderID='') {
        
        
        //Pre-checks act1
        if($senderID==null || !strlen(trim($senderID))) {
            $senderID = $this->config['default_sender'];
        }
        
        
        //Pre-checks act2
        if($message==null || !strlen(trim($message))) {
            return 100;
        }
        
        //Sending for future date
        $dateStr='';
        if(strlen($date)) {
            $datestr=' tarih="'.$date.'"';
        }
        
        
        //Recipents check + XML initialise
        if(is_array($recipents)) {
            
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<smspack ka="'.$this->config['auth']['username'].'" pwd="'.$this->config['auth']['password'].'"'.$dateStr.' org="'.$senderID.'" >';
            
            
            $recipentsString = '';
            
            for($i=0;$i<count($recipents);$i++) {
                if($i>0) { $recipentsString.=', '; }
                $recipentsString.= $recipents[$i];
            }
            
            $xml.='<mesaj>'.
                    '<metin>'.$this->stripText($message).'</metin>'.
                    '<nums>'.$recipentsString.'</nums>'.
                '</mesaj>';
            
            
            $xml.='</smspack>';
            
            
            return $this->postXML($xml);
            
            
        } else {
            //Recipents must be an array
            return 101;
        }
        

    }
    
    /**
     * Sends a single SMS to a single person 
     * @param $recipent string recipent
     * @param $message string message to be sent
     * @param $senderID string originator/sender id (may be a text or number)
     * @return status
     */
    public function send($receiver, $message='', $date='', $senderID=''){
    
        //Pre-checks act1
        if($senderID==null || !strlen(trim($senderID))) {
            $senderID = $this->config['default_sender'];
        }
        
        
        //Pre-checks act2
        if($message==null || !strlen(trim($message))) {
            return 100;
        }
        
        //Pre-checks act3
        if($receiver==null || !strlen(trim($receiver))) {
            //no receiver
            return 102;
        }
        
        //Sending for future date
        $dateStr='';
        if(strlen($date)) {
            $datestr=' tarih="'.$date.'"';
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<smspack ka="'.$this->config['auth']['username'].'" pwd="'.$this->config['auth']['password'].'"'.$dateStr.' org="'.$senderID.'" >';
        
        $xml.='<mesaj>'.
                    '<metin>'.$this->stripText($message).'</metin>'.
                    '<nums>'.$receiver.'</nums>'.
                '</mesaj>';
        
        $xml.='</smspack>';
        
        return $this->postXML($xml);
    
    }
    
    
    /**
     * Sends multiple SMSes to various people with various content 
     * @param $reciversMessage array recipents and message
     * @param $senderID string originator/sender id (may be a text or number)
     * @return status
     */
    public function sendMulti($reciversMessage, $date='', $senderID='') {
        
        //Pre-checks act1
        if($senderID==null || !strlen(trim($senderID))) {
            $senderID = $this->config['default_sender'];
        }
        
        //Sending for future date
        $dateStr='';
        if(strlen($date)) {
            $datestr=' tarih="'.$date.'"';
        }
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<smspack ka="'.$this->config['auth']['username'].'" pwd="'.$this->config['auth']['password'].'"'.$dateStr.' org="'.$senderID.'" >';
        
        foreach($reciversMessage as $eachMessageBlock) {
            
            $number     = $eachMessageBlock[0];
            $message    = $eachMessageBlock[1];
            
            $xml.='<mesaj>'.
                    '<metin>'.$this->stripText($message).'</metin>'.
                    '<nums>'.$recipentsString.'</nums>'.
                '</mesaj>';
        
        }
        
        $xml.='</smspack>';
        
        return $this->postXML($xml);
        
        
    }
    
    
    /**
     * Balance Checker
     * Shows how much SMS you have left
     * @return integer number of SMSes left for the account
     */
    public function checkBalance() {
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<smskredi ka="'.$this->config['auth']['username'].'" pwd="'.$this->config['auth']['password'].'" />';
        
        $response = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtcrdtex');
        
        //Data will be like $1986.0, 
        //since 1st character is $, and it is float (srsly, why?) we will strip it and make it integer
        return intval(substr($response,1));
        
    }
    
    public function listOriginators() {
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
                '<smsorig ka="'.$this->config['auth']['username'].'" pwd="'.$this->config['auth']['password'].'" />';
        
        $response = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtorgex');
        
        return var_dump($response);
        
    
    }
    
    
    /**
     * Parse the output
     * @param $output string
     * return string status code
     */
    public function parseOutput($output) {
        //if error code is returned, api OR the app will return an integer error code
        if($this->isnum($output)) {
            
            switch($output) {
                
                case 20:
                    return $this->lang['reports']['20'];
                    break;
                
                case 21:
                    return $this->lang['reports']['21'];
                    break;
                
                case 22:
                    return $this->lang['reports']['22'];
                    break;
                
                case 23:
                    return $this->lang['reports']['23'];
                    break;
                
                case 24:
                    return $this->lang['reports']['24'];
                    break;
                
                case 25:
                    return $this->lang['reports']['25'];
                    break;
                
                
                //In-app messages:
                case 100:
                    return $this->lang['app'][0];
                    break;
                
                case 101:
                    return $this->lang['app'][1];
                    break;
                
                case 102:
                    return $this->lang['app'][2];
                    break;
                
                default:
                    return $this->lang['reports']['999'];
                    break;
                
            }
            
        //returns from Mutlucell
        //TODO BETTER REGEX
        } elseif(substr($output,0,1) == '&' && stristr($output, '#')) {
            
            //returned output is formatted like $ID#STATUS
            //E.g: $1234567#1.0
            $output = explode('#', $output);
                        
            $status = $output[1];
            if($status == '0.0') {
                return $this->lang['app']['101'];
            } else {
                return $this->lang['app']['100'];
            }
        
        //Unknown error
        } else {
            return $output;   
        }
        
        
    }
    
    /**
     * CURL XML post sending method
     * @param xml string formatted string
     * @return string
     *
     */
    private function postXML($xml, $url = 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex') {
        
     
        $ch = curl_init($url);
        //CURLOPT_MUTE is deprecated in new PHP versions, 
        //instead, we'll use CURLOPT_RETURNTRANSFER
        //http://stackoverflow.com/a/12497400/570763
        //curl_setopt($ch, CURLOPT_MUTE, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $output = curl_exec($ch);
        curl_close($ch);
        
        return $output;
    
    }
    
    
    /**
     * Checks whether the number is an integer or not with Regex
     * Taken from PHP-Fusion <http://php-fusion.co.uk>
     * @param string $value string to be checked
     * @return boolean
     */
    private function isnum($value) {
        if (!is_array($value)) {
          return (preg_match("/^[0-9]+$/", $value));
        } else {
          return false;
        }
    }
                            
    /**
     * Stripis unwanted HTML characters and cleans it up
     * @param string $text string to be trimmed
     * @return string
     */
    private function stripText($text) {
        if (!is_array($text)) {
            $text       = stripslashes(trim($text));
            $text       = preg_replace('/\s+/', ' ', $text); //replace multiple spaces into one
            $text       = preg_replace("/(&amp;)+(?=\#([0-9]{2,3});)/i", "&", $text);
            $search     = array("&", ">", "<");
            $replace    = array("", "", "");
            $text       = str_replace($search, $replace, $text);
        } else {
            $text = '';
        }
        return $text;
    }
    
}
