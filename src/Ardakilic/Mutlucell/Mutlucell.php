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
use Illuminate\Support\Facades\Queue;

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

  private const string SMS_ID_PATTERN = '/(\$[0-9]+\#[0-9]+\.[0-9]+)/i';

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
   * @throws \InvalidArgumentException if auth parameter or originator is not set
   */
  public function setConfig(array $config): static
  {
    // The user may have called setConfig() manually,
    // and the array may have missing arguments.
    // So, we're checking whether they are set, and filling them if not set
    // Critical ones will throw exceptions, non-critical ones will set default values
    if (!isset($config['auth'])) {
      throw new \InvalidArgumentException($this->lang['exceptions']['0']);
    }
    if (!isset($config['auth']['username']) || !isset($config['auth']['password'])) {
      throw new \InvalidArgumentException($this->lang['exceptions']['1']);
    }
    if (!isset($config['default_sender'])) {
      throw new \InvalidArgumentException($this->lang['exceptions']['2']);
    }
    $this->config = $config;
    $this->sender = $config['default_sender'];
    $this->config['queue'] ??= false;
    $this->config['charset'] ??= 'default';
    $this->config['append_unsubscribe_link'] ??= false;
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
   * Fall back to the configured default sender when none is given.
   */
  private function resolveSender(string $sender): string
  {
    return trim($sender) === '' ? $this->config['default_sender'] : $sender;
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
    if ($date !== '') {
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
   * @param array $recipients recipient list
   * @param string $message message to be sent
   * @param string $date when will the message be sent?
   * @param string $sender originator/sender id (may be a text or number)
   * @return string|int status API response (102 when no recipients)
   */
  public function sendBulk(array $recipients, string $message = '', string $date = '', string $sender = ''): string|int
  {
    // No recipients, no send (same 102 as send() with an empty receiver)
    if ($recipients === []) {
      return 102;
    }

    // Checks the $message and $sender, and initializes it
    $this->initSenderAndMessage($message, $sender);

    $messages = [
      [
        'text' => $this->message,
        'nums' => implode(', ', $recipients),
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
   * @return string|int status API response (102 when receiver is empty)
   */
  public function send(string $receiver, string $message = '', string $date = '', string $sender = ''): string|int
  {
    // Ensure that the receiver is not empty
    if (trim($receiver) === '') {
      // no receiver
      return 102;
    }

    // A single send is a one-recipient bulk send
    return $this->sendBulk(recipients: [$receiver], message: $message, date: $date, sender: $sender);
  }


  /**
   * Sends multiple SMSes to various people with various content (number => message pairs)
   * Call with PHP 8 named arguments, e.g. sendMulti(messages: ['905551112233' => 'Hi']).
   * @param array $messages recipients and message
   * @param string $date delivery date
   * @param string $sender originator/sender id (may be a text or number)
   * @return string status API response
   */
  public function sendMulti(array $messages, string $date = '', string $sender = ''): string
  {
    // initSenderAndMessage cannot be used, because messages is an array
    $this->sender = $this->resolveSender($sender);

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
  /**
   * Adds phone number(s) to the blacklist
   * Call with PHP 8 named arguments, e.g. addBlacklist(phoneNumbers: ['905551112233']).
   * @param array $phoneNumbers The phone numbers
   * @return string status API response
   */
  public function addBlacklist(array $phoneNumbers = []): string
  {
    return $this->postBlacklist($phoneNumbers, 'addblacklist', 'https://smsgw.mutlucell.com/smsgw-ws/addblklst');
  }

  /**
   * Deletes phone number(s) from the blacklist.
   * With no numbers the API wipes the entire blacklist, so that requires confirm: true.
   * Call with PHP 8 named arguments, e.g. deleteBlacklist(phoneNumbers: [...]) or deleteBlacklist(confirm: true).
   * @param array $phoneNumbers The phone numbers
   * @param bool $confirm Required when $phoneNumbers is empty (delete all)
   * @return string
   * @throws \InvalidArgumentException when $phoneNumbers is empty without $confirm
   */
  public function deleteBlacklist(array $phoneNumbers = [], bool $confirm = false): string
  {
    if ($phoneNumbers === [] && !$confirm) {
      throw new \InvalidArgumentException($this->lang['exceptions']['3']);
    }

    return $this->postBlacklist($phoneNumbers, 'dltblacklist', 'https://smsgw.mutlucell.com/smsgw-ws/dltblklst');
  }

  /**
   * Shared blacklist XML post helper.
   * @param array $phoneNumbers The phone numbers (empty = whole blacklist for delete)
   * @param string $root XML root element
   * @param string $url API endpoint URL
   * @return string API Status
   */
  private function postBlacklist(array $phoneNumbers, string $root, string $url): string
  {
    $blXMLElement = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><' . $root . '/>');
    $blXMLElement->addAttribute('ka', $this->config['auth']['username']);
    $blXMLElement->addAttribute('pwd', $this->config['auth']['password']);
    $blXMLElement->addChild('nums', implode(', ', $phoneNumbers));
    $xml = $blXMLElement->asXML();

    return $this->postXML($xml, $url);
  }

  /**
   * Balance Checker
   * Shows how much SMS you have left
   * @return integer number of SMSes left for the account
   */
  public function checkBalance(): int
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
  public function listOriginators(): string
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
   * @return array
   */
  public function getMessageReport(string $messageId): array
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

      $knownErrors = [
        20 => $this->lang['reports']['20'],
        23 => $this->lang['reports']['23'],
        30 => $this->lang['reports']['30'],
      ];

      $code = (int) $result;
      if (isset($knownErrors[$code])) {
        $report['error'] = [
          'error_code' => $code,
          'error_message' => $knownErrors[$code],
        ];
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
   * @param string|int $output API's response (send() may return int error codes, e.g. 102)
   * @return string|int status code
   */
  public function parseOutput(string|int $output): string|int
  {
    // if error code is returned, api OR the app will return an integer error code
    if ($this->isNum($output)) {
      $code = (int) $output;
      // In-app messages live in lang.app keyed 0..2 for codes 100..102
      if ($code >= 100 && $code <= 102) {
        return $this->lang['app'][$code - 100];
      }
      // Vendor codes (20..30) resolve straight from lang.reports; anything else is unknown
      return $this->lang['reports'][(string) $code] ?? $this->lang['reports']['999'];
    } elseif (preg_match(self::SMS_ID_PATTERN, $output)) {
      // Returned output is formatted like $ID#STATUS
      // E.g: $1234567#1.0
      $output = explode('#', $output);

      $status = $output[1];
      if ($status === '0.0') {
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
   * @param string|int $output API's response (send() may return int error codes, e.g. 102)
   * @return boolean
   */
  public function getStatus(string|int $output): bool
  {
    // If error code is returned, API will return an integer error code
    if ($this->isNum($output)) {
      return false;

      // returns from Mutlucell
    } elseif (preg_match(self::SMS_ID_PATTERN, $output)) {

      // Returned output is formatted like $ID#STATUS
      // E.g: $1234567#1.0
      $output = explode('#', $output);

      $status = $output[1];
      if ($status === '0.0') {
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
  public function getMessageId(string $output): string|null
  {
    if (preg_match(self::SMS_ID_PATTERN, $output)) {
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
  protected function initSenderAndMessage(string $message, string $sender): void
  {
    $this->sender = $this->resolveSender($sender);

    if (trim($message) === '') {
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
  private function postXML(string $xml, string $url): string
  {
    if ($this->config['queue']) {
      // @codeCoverageIgnoreStart
      // The closure below runs inside the queue worker process, never under PHPUnit (Queue::fake() intercepts the push).
      Queue::push(function () use ($xml, $url) {
        $client = new Client();
        $request = new Request('POST', $url, ['Content-Type' => 'text/xml; charset=UTF8'], $xml);
        $response = $client->send($request);
        return $response->getBody()->getContents();
      });
      // @codeCoverageIgnoreEnd
      return 'queued';
    }

    $client = $this->httpClient();
    $request = new Request('POST', $url, ['Content-Type' => 'text/xml; charset=UTF8'], $xml);
    $response = $client->send($request);
    return $response->getBody()->getContents();
  }


  /**
   * Checks whether the value is an integer or not (digits only, quotes allowed).
   * @param mixed $value value to be checked
   * @return bool
   */
  private function isNum(mixed $value): bool
  {
    if (is_array($value)) {
      return false;
    }

    // (string) cast keeps int inputs (e.g. 20) equivalent to their digit-string form
    return ctype_digit((string) $value);
  }
}
