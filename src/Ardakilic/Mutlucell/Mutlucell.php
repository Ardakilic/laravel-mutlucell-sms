<?php
namespace Ardakilic\Mutlucell;

/**
 * Laravel 4 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

use Queue;

class Mutlucell
{
    protected $app;
    protected $config;
    protected $lang;
    protected $code;

    protected $senderID;
    protected $message;

    public function __construct($app)
    {
        $this->app = $app;
        $locale = $app['config']['app.locale'];
        $this->lang = $app['translator']->get("mutlucell::{$locale}");
        $this->config = $app['config']['mutlucell::config'];
        $this->senderID = $this->config['default_sender'];

        // To prevent missing configuration files to throw exception.
        // Will be removed in future versions
        if (!isset($this->config['charset'])) {
            $this->config['charset'] = 'default';
        }
        if (!isset($this->config['append_unsubscribe_link'])) {
            $this->config['append_unsubscribe_link'] = false;
        }
    }


    /**
     * Send same bulk message to many people
     * @param $recipents array recipents
     * @param $message string message to be sent
     * @param $date string the date when the sms will be sent
     * @param $senderID string originator/sender id (may be a text or number)
     * @return string status API response
     */
    public function sendBulk($recipents, $message = '', $date = '', $senderID = '')
    {
        //Checks the $message and $senderID, and initializes it
        $this->preChecks($message, $senderID);
        //Sending for future date
        $dateStr = '';
        if (strlen($date)) {
            $dateStr = ' tarih="' . $date . '"';
        }
        //Recipents check + XML initialise
        if (is_array($recipents)) {
            $recipents = implode(', ', $recipents);
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smspack ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '"' . $dateStr . ' org="' . $this->senderID . '" charset="'.$this->config['charset'].'"'.($this->config['append_unsubscribe_link']?' addLinkToEnd="true"':'').'>';
        $xml .= '<mesaj>' . '<metin>' . $this->message . '</metin>' . '<nums>' . $recipents . '</nums>' . '</mesaj>';
        $xml .= '</smspack>';
        return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
    }

    /**
     * Sends a single SMS to a single person
     * @param string $receiver receiver number
     * @param string $message message to be sent
     * @param string $date delivery date
     * @param string $senderID originator/sender id (may be a text or number)
     * @return string status API response
     */
    public function send($receiver, $message = '', $date = '', $senderID = '')
    {
        //Checks the $message and $senderID, and initializes it
        $this->preChecks($message, $senderID);

        //Pre-checks act3
        if ($receiver == null || !strlen(trim($receiver))) {
            //no receiver
            return 102;
        }
        //Sending for future date
        $dateStr = '';
        if (strlen($date)) {
            $dateStr = ' tarih="' . $date . '"';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smspack ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '"' . $dateStr . ' org="' . $this->senderID . '" charset="'.$this->config['charset'].'"'.($this->config['append_unsubscribe_link']?' addLinkToEnd="true"':'').'>';

        $xml .= '<mesaj>' . '<metin>' . $this->message . '</metin>' . '<nums>' . $receiver . '</nums>' . '</mesaj>';
        $xml .= '</smspack>';
        return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
    }


    /**
     * Sends multiple SMSes to various people with various content
     * @param array $receiversMessage recipents and message
     * @param string $date delivery date
     * @param string $senderID originator/sender id (may be a text or number)
     * @return string status API response
     */
    public function sendMulti($receiversMessage, $date = '', $senderID = '')
    {

        //Pre-checks act1
        if ($senderID == null || !strlen(trim($senderID))) {
            $senderID = $this->config['default_sender'];
        }

        //Sending for future date
        $dateStr = '';
        if (strlen($date)) {
            $dateStr = ' tarih="' . $date . '"';
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smspack ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '"' . $dateStr . ' org="' . $senderID . '" charset="'.$this->config['charset'].'"'.($this->config['append_unsubscribe_link']?' addLinkToEnd="true"':'').'>';

        foreach ($receiversMessage as $eachMessageBlock) {
            $number = $eachMessageBlock[0];
            $message = $eachMessageBlock[1];
            $xml .= '<mesaj>' . '<metin>' . $message . '</metin>' . '<nums>' . $number . '</nums>' . '</mesaj>';
        }

        $xml .= '</smspack>';

        return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
    }


    /**
     * Sends multiple SMSes to various people with various content with key and value pair
     * @param array $receiversMessage recipents and message
     * @param string $date delivery date
     * @param string $senderID originator/sender id (may be a text or number)
     * @return string status API response
     */
    public function sendMulti2($receiversMessage, $date = '', $senderID = '')
    {
        //Pre-checks act1
        if ($senderID == null || !strlen(trim($senderID))) {
            $senderID = $this->config['default_sender'];
        }
        //Sending for future date
        $dateStr = '';
        if (strlen($date)) {
            $dateStr = ' tarih="' . $date . '"';
        }
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smspack ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '"' . $dateStr . ' org="' . $senderID . '" charset="'.$this->config['charset'].'"'.($this->config['append_unsubscribe_link']?' addLinkToEnd="true"':'').'>';
        foreach ($receiversMessage as $number => $message) {
            $xml .= '<mesaj>' . '<metin>' . $message . '</metin>' . '<nums>' . $number . '</nums>' . '</mesaj>';
        }
        $xml .= '</smspack>';
        return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
    }

    /**
     * Deletes phone number(s) from the blacklist
     * @param $phoneNumbers array|string The phone numbers
     * @return string
     */
    public function deleteBlackList($phoneNumbers = null)
    {
        //If the <nums> parameter is blank, all users are removed from blacklist as Mutlucell Api says
        if ($phoneNumbers === null) {
            $phoneNumbers = '';
        }

        if (is_array($phoneNumbers)) {
            $phoneNumbers = implode(', ', $phoneNumbers);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<dltblacklist ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '">';
        $xml .= '<nums>' . $phoneNumbers . '</nums>';
        $xml .= '</dltblacklist>';

        return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/dltblklst');
    }


    /**
     * Balance Checker
     * Shows how much SMS you have left
     * @return integer number of SMSes left for the account
     */
    public function checkBalance()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smskredi ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '" />';
        $response = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtcrdtex');
        //Data will be like $1986.0,
        //since 1st character is $, and it is float (srsly, why?) we will strip it and make it integer
        return intval(substr($response, 1));
    }

    /**
     * Lists the originators associated for the account
     * @return string list of originators
     */
    public function listOriginators()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . '<smsorig ka="' . $this->config['auth']['username'] . '" pwd="' . $this->config['auth']['password'] . '" />';
        $response = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtorgex');
        return $response;
    }


    /**
     * Parse the output
     * @param string $output API's response
     * @return string status code
     */
    public function parseOutput($output)
    {
        //if error code is returned, api OR the app will return an integer error code
        if ($this->isnum($output)) {
            switch ($output) {

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

                case 30:
                    return $this->lang['reports']['30'];
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
        } elseif (preg_match('/(\$[0-9]+\#[0-9]+\.[0-9]+)/i', $output)) {
            //returned output is formatted like $ID#STATUS
            //E.g: $1234567#1.0
            $output = explode('#', $output);

            $status = $output[1];
            if ($status == '0.0') {
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
     * Gets the SMS's status
     * @param string $output API's response
     * @return boolean
     */
    public function getStatus($output)
    {
        //if error code is returned, API will return an integer error code
        if ($this->isnum($output)) {
            return false;

            //returns from Mutlucell
            //TODO A GOOD REGEX
            //} elseif(substr($output,0,1) == '&' && stristr($output, '#')) {
        } elseif (preg_match('/(\$[0-9]+\#[0-9]+\.[0-9]+)/i', $output)) {

            //returned output is formatted like $ID#STATUS
            //E.g: $1234567#1.0
            $output = explode('#', $output);

            $status = $output[1];
            if ($status == '0.0') {
                return false;
            } else {
                return true;
            }
        } else {
            //Unknown error
            return false;
        }
    }

    /**
     * Prechecks to prevent multiple usage
     * @param string $message message to be sent
     * @param string $senderID originator ID
     */
    protected function preChecks($message, $senderID)
    {
        //TODO a better method for this
        //Pre-checks act1
        if ($senderID == null || !strlen(trim($senderID))) {
            $this->senderID = $this->config['default_sender'];
        } else {
            $this->senderID = $senderID;
        }
        //Pre-checks act2
        if ($message == null || !strlen(trim($message))) {
            $this->message = '&'; //Error character for sms
        } else {
            $this->message = $message;
        }
    }

    /**
     * CURL XML post sending method
     * @param string $xml formatted string
     * @return string API Status
     *
     */
    private function postXML($xml, $url)
    {
        if ($this->config['queue']) {
            Queue::push(function () use ($xml, $url) {
                $ch = curl_init($url);
                //CURLOPT_MUTE is deprecated in new PHP versions,
                //instead, we'll use CURLOPT_RETURNTRANSFER
                //http://stackoverflow.com/a/12497400/570763
                //curl_setopt($ch, CURLOPT_MUTE, 1);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: text/xml'
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

                $output = curl_exec($ch);
                curl_close($ch);

                return $output;
            });
        } else {
            $ch = curl_init($url);
            //CURLOPT_MUTE is deprecated in new PHP versions,
            //instead, we'll use CURLOPT_RETURNTRANSFER
            //http://stackoverflow.com/a/12497400/570763
            //curl_setopt($ch, CURLOPT_MUTE, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: text/xml'
            ));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

            $output = curl_exec($ch);
            curl_close($ch);

            return $output;
        }
    }

    /**
     * Checks whether the number is an integer or not with Regex
     * !I'm not using is_int() because people may add numbers in quotes!
     * Taken from PHP-Fusion <http://php-fusion.co.uk>
     * @param string $value string to be checked
     * @return boolean
     */
    private function isnum($value)
    {
        if (!is_array($value)) {
            return preg_match("/^[0-9]+$/", $value);
        } else {
            return false;
        }
    }
}
