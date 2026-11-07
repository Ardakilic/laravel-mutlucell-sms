<?php

namespace Ardakilic\Mutlucell;

/**
 * Laravel 11 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Request;
use Queue;

use SimpleXMLElement;

class Mutlucell
{
  protected $app;
  protected $config;
  protected $lang;
  protected $code;

  protected $sender;
  protected $message;

  protected ?ClientInterface $httpClient = null;

  /**
   * Create a new Mutlucell instance.
   *
   * @param mixed $app Laravel application/container
   * @param \GuzzleHttp\ClientInterface|null $httpClient injectable HTTP client (mainly for testing)
   */
  public function __construct($app, ?ClientInterface $httpClient = null)
  {
    $this->app = $app;
    $this->httpClient = $httpClient;
    $locale = $app['config']['app.locale'];
    $this->lang = $app['translator']->get("mutlucell::{$locale}");
    $this->setConfig($app['config']['mutlucell']);
  }

  /**
   * This method allows user to change configuration on-the-fly
   *
   * @param array $config
   * @return $this
   * @throws \Exception if auth parameter or originator is not set
   */
  public function setConfig(array $config)
  {
    $this->config = $config;
    $this->sender = $this->config['default_sender'];

    // The user may have called setConfig() manually,
    // and the array may have missing arguments.
    // So, we're checking whether they are set, and filling them if not set
    // Critical ones will throw exceptions, non-critical ones will set default values
    // TODO: Refactor this method
    if (!isset($this->config['auth'])) {
      throw new \Exception($this->lang['exceptions']['0']);
    } else {
      if (!isset($this->config['auth']['username']) || !isset($this->config['auth']['password'])) {
        throw new \Exception($this->lang['exceptions']['1']);
      }
    }
    if (!isset($this->config['default_sender'])) {
      throw new \Exception($this->lang['exceptions']['2']);
    }
    if (!isset($this->config['queue'])) {
      $this->config['queue'] = false;
    }
    if (!isset($this->config['charset'])) {
      $this->config['charset'] = 'default';
    }
    if (!isset($this->config['append_unsubscribe_link'])) {
      $this->config['append_unsubscribe_link'] = false;
    }
    return $this;
  }

  /**
   * Inject a custom HTTP client (e.g. a Guzzle MockHandler stack in tests).
   *
   * @param \GuzzleHttp\ClientInterface $client
   * @return $this
   */
  public function setHttpClient(ClientInterface $client): static
  {
    $this->httpClient = $client;
    return $this;
  }

  /**
   * Resolve the HTTP client, lazily creating a default Guzzle client.
   *
   * @return \GuzzleHttp\ClientInterface
   */
  protected function httpClient(): ClientInterface
  {
    return $this->httpClient ??= new Client();
  }

  /**
   * The method that creates a XML string to send to Mutlucell API
   * @param string $date the date of the sms to be sent
   * @param array $messages the messages array to be dispatched.
   * @return string the XML output
   */
  private function generateXMLStringForSending(string $date = '', array $messages = [])
  {
    $smsXMLElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><smspack/>');
    $smsXMLElement->addAttribute('ka', $this->config['auth']['username']);
    $smsXMLElement->addAttribute('pwd', $this->config['auth']['password']);
    if (strlen($date)) {
      $smsXMLElement->addAttribute('tarih', $date);
    }
    $smsXMLElement->addAttribute('org', $this->sender);
    $smsXMLElement->addAttribute('charset', $this->config['charset']);
    if ($this->config['append_unsubscribe_link'] === true) {
      $smsXMLElement->addAttribute('addLinkToEnd', 'true');
    }

    foreach ($messages as $eachMessage) {
      $message = $smsXMLElement->addChild('mesaj');
      $message->addChild('metin', $eachMessage['text']);
      $message->addChild('nums', $eachMessage['nums']);
    }

    return $smsXMLElement->asXML();
  }

  /**
   * Send same bulk message to many people
   * Call with PHP 8 named arguments, e.g. send(receiver: ..., message: ...).
   * @param array|string $recipients recipients
   * @param string $message message to be sent
   * @param string $date when will the message be sent?
   * @param string $sender originator/sender id (may be a text or number)
   * @return string status API response
   */
  public function sendBulk(array|string $recipients, string $message = '', string $date = '', string $sender = '')
  {
    // Checks the $message and $sender, and initializes it
    $this->initSenderAndMessage($message, $sender);

    if (is_array($recipients)) {
      $recipients = implode(', ', $recipients);
    }

    $messages = [
      [
        'text' => $this->message,
        'nums' => $recipients,
      ]
    ];

    $xml = $this->generateXMLStringForSending($date, $messages);

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
  }

  /**
   * Sends a single SMS to a single person
   * Call with PHP 8 named arguments, e.g. send(receiver: ..., message: ...).
   * @param string $receiver receiver number
   * @param string $message message to be sent
   * @param string $date delivery date
   * @param string $sender originator/sender id (may be a text or number)
   * @return string status API response
   */
  public function send(string $receiver, string $message = '', string $date = '', string $sender = '')
  {
    // Checks the $message and $sender, and initializes it
    $this->initSenderAndMessage($message, $sender);

    // Ensure that the receiver is not empty
    if ($receiver == null || !strlen(trim($receiver))) {
      // no receiver
      return 102;
    }

    $messages = [
      [
        'text' => $this->message,
        'nums' => $receiver,
      ]
    ];

    $xml = $this->generateXMLStringForSending($date, $messages);

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
  }


  /**
   * Sends multiple SMSes to various people with various content
   * Call with PHP 8 named arguments, e.g. send(receiver: ..., message: ...).
   * @param array $messages recipients and message
   * @param string $date delivery date
   * @param string $sender originator/sender id (may be a text or number)
   * @return string status API response
   */
  public function sendMulti(array $messages, string $date = '', string $sender = '')
  {
    // initSenderAndMessage cannot be used, because messages is an array
    if ($sender == null || !strlen(trim($sender))) {
      $this->sender = $this->config['default_sender'];
    } else {
      $this->sender = $sender;
    }

    $preparedMessages = [];
    foreach ($messages as $eachMessageBlock) {
      $preparedMessages[] = [
        'text' => $eachMessageBlock[1], // Message
        'nums' => $eachMessageBlock[0], // Number(s)
      ];
    }

    $xml = $this->generateXMLStringForSending($date, $preparedMessages);

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
  }


  /**
   * Sends multiple SMSes to various people with various content with key and value pair
   * Call with PHP 8 named arguments, e.g. send(receiver: ..., message: ...).
   * @param array $messages recipients and message
   * @param string $date delivery date
   * @param string $sender originator/sender id (may be a text or number)
   * @return string status API response
   */
  public function sendMulti2(array $messages, string $date = '', string $sender = '')
  {
    // initSenderAndMessage cannot be used, because messages is an array
    if ($sender == null || !strlen(trim($sender))) {
      $this->sender = $this->config['default_sender'];
    } else {
      $this->sender = $sender;
    }

    $preparedMessages = [];
    foreach ($messages as $number => $message) {
      $preparedMessages[] = [
        'text' => $message,
        'nums' => $number,
      ];
    }

    $xml = $this->generateXMLStringForSending($date, $preparedMessages);

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/sndblkex');
  }

  /**
   * Adds phone number(s) to the blacklist
   * @param array|string $phoneNumbers The phone numbers
   * @return string status API response
   */
  public function addBlacklist(array|string $phoneNumbers = '')
  {

    if (is_array($phoneNumbers)) {
      $phoneNumbers = implode(', ', $phoneNumbers);
    }

    $blXMLElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><addblacklist/>');
    $blXMLElement->addAttribute('ka', $this->config['auth']['username']);
    $blXMLElement->addAttribute('pwd', $this->config['auth']['password']);
    $blXMLElement->addChild('nums', $phoneNumbers);
    $xml = $blXMLElement->asXML();

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/addblklst');
  }

  /**
   * Deletes phone number(s) from the blacklist
   * @param array|string $phoneNumbers The phone numbers
   * @return string
   */
  public function deleteBlackList(array|string $phoneNumbers = '')
  {
    // If the <nums> parameter is blank, all users are removed from blacklist as Mutlucell Api says

    if (is_array($phoneNumbers)) {
      $phoneNumbers = implode(', ', $phoneNumbers);
    }

    $blXMLElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><dltblacklist/>');
    $blXMLElement->addAttribute('ka', $this->config['auth']['username']);
    $blXMLElement->addAttribute('pwd', $this->config['auth']['password']);
    $blXMLElement->addChild('nums', $phoneNumbers);
    $xml = $blXMLElement->asXML();

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/dltblklst');
  }

  /**
   * Balance Checker
   * Shows how much SMS you have left
   * @return integer number of SMSes left for the account
   */
  public function checkBalance()
  {
    $creditXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><smskredi/>');
    $creditXML->addAttribute('ka', $this->config['auth']['username']);
    $creditXML->addAttribute('pwd', $this->config['auth']['password']);
    $xml = $creditXML->asXML();

    $response = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtcrdtex');

    // Data will be like $1986.0,
    // Since 1st character is $, and it is float (srsly, why?) we will strip it and make it integer
    return intval(substr($response, 1));
  }

  /**
   * Lists the originators associated for the account
   * @return string list of originators
   */
  public function listOriginators()
  {
    $originatorsXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><smsorig/>');
    $originatorsXML->addAttribute('ka', $this->config['auth']['username']);
    $originatorsXML->addAttribute('pwd', $this->config['auth']['password']);
    $xml = $originatorsXML->asXML();

    return $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtorgex');
  }

  /**
   * @param string $messageId String the id of the dispatched message
   *
   * @return array|string
   */
  public function getMessageReport(string $messageId)
  {
    $originatorsXML = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><smsrapor/>');
    $originatorsXML->addAttribute('ka', $this->config['auth']['username']);
    $originatorsXML->addAttribute('pwd', $this->config['auth']['password']);
    $originatorsXML->addAttribute('id', $messageId);
    $xml = $originatorsXML->asXML();

    $result = $this->postXML($xml, 'https://smsgw.mutlucell.com/smsgw-ws/gtblkrprtex');
    if ($this->isNum($result)) {
      $report = [
        'success' => false,
      ];

      switch ($result) {
        case 20:
          $report[] = [
            'error_code' => 20,
            'error_message' => $this->lang['reports']['20']
          ];

          break;
        case 23:
          $report[] = [
            'error_code' => 23,
            'error_message' => $this->lang['reports']['23']
          ];

          break;
        case 30:
          $report[] = [
            'error_code' => 30,
            'error_message' => $this->lang['reports']['30']
          ];

          break;
      }
    } else {
      $messages = array_filter(explode("\n", $result));
      $report = [
        'success' => true,
      ];

      foreach ($messages as $message) {
        $message = explode(' ', $message);
        $number = $message[0];

        $report['numbers'][$number] = [
          'success' => true,
          'number' => $number,
          'result' => $message[1],
          'result_text' => $this->lang['sms'][$message[1]] ?? 'Unknown',
        ];
      }
    }

    return $report;
  }

  /**
   * Parse the output
   * @param string $output API's response
   * @return string status code
   */
  public function parseOutput(string $output)
  {
    // if error code is returned, api OR the app will return an integer error code
    if ($this->isNum($output)) {
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


        // In-app messages:
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

      // returns from Mutlucell
    } elseif (preg_match('/(\$[0-9]+\#[0-9]+\.[0-9]+)/i', $output)) {
      // Returned output is formatted like $ID#STATUS
      // E.g: $1234567#1.0
      $output = explode('#', $output);

      $status = $output[1];
      if ($status == '0.0') {
        return $this->lang['app']['101'];
      } else {
        return $this->lang['app']['100'];
      }

      // Unknown error
    } else {
      return $output;
    }
  }

  /**
   * Gets the SMS's status
   * @param string $output API's response
   * @return boolean
   */
  public function getStatus(string $output)
  {
    // If error code is returned, API will return an integer error code
    if ($this->isNum($output)) {
      return false;

      // returns from Mutlucell
    } elseif (preg_match('/(\$[0-9]+\#[0-9]+\.[0-9]+)/i', $output)) {

      // Returned output is formatted like $ID#STATUS
      // E.g: $1234567#1.0
      $output = explode('#', $output);

      $status = $output[1];
      if ($status == '0.0') {
        return false;
      } else {
        return true;
      }

      // Unknown error
    } else {
      return false;
    }
  }

  /**
   * @param string $output
   *
   * @return string|null
   */
  public function getMessageId(string $output)
  {
    if (preg_match('/(\$[0-9]+\#[0-9]+\.[0-9]+)/i', $output)) {
      // Returned output is formatted like $ID#STATUS
      // E.g: $1234567#1.0
      $output = explode('#', $output);

      return str_replace('$', '', $output[0]);
    }

    return null;
  }

  /**
   * Initialize Sender ID and message
   * The method also prevents duplicate usage
   * @param string $message message to be sent
   * @param string $sender originator ID
   */
  protected function initSenderAndMessage(string $message, string $sender)
  {
    // TODO a better method for this
    // Pre-checks act1
    if ($sender == null || !strlen(trim($sender))) {
      $this->sender = $this->config['default_sender'];
    } else {
      $this->sender = $sender;
    }

    // Pre-checks act2
    if ($message == null || !strlen(trim($message))) {
      $this->message = '&'; // Error character for sms
    } else {
      $this->message = $message;
    }
  }

  /**
   * XML post sending method
   * @param string $xml formatted string
   * @param string $url API endpoint URL
   * @return string API Status
   *
   */
  private function postXML(string $xml, string $url)
  {
    if ($this->config['queue']) {
      Queue::push(function () use ($xml, $url) {
        $client = new Client();
        $request = new Request('POST', $url, ['Content-Type' => 'text/xml; charset=UTF8'], $xml);
        $response = $client->send($request);
        return $response->getBody()->getContents();
      });
      return 'queued';
    }

    $client = $this->httpClient();
    $request = new Request('POST', $url, ['Content-Type' => 'text/xml; charset=UTF8'], $xml);
    $response = $client->send($request);
    return $response->getBody()->getContents();
  }


  /**
   * Checks whether the number is an integer or not with Regex
   * !I'm not using is_int() because people may add numbers in quotes!
   * Taken from PHP-Fusion <http://php-fusion.co.uk>
   * @param string $value string to be checked
   * @return boolean|int
   */
  private function isNum($value)
  {
    if (!is_array($value)) {
      return preg_match("/^[0-9]+$/", $value);
    }
    return false;
  }
}
