<?php
namespace Ardakilic\Mutlucell\Facades;

/**
 * Laravel 11 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

use Illuminate\Support\Facades\Facade;

/**
 * @method static string send(string $receiver, string $message = '', string $date = '', string $sender = '')
 * @method static string sendBulk(array $recipients, string $message = '', string $date = '', string $sender = '')
 * @method static string sendMulti(array $messages, string $date = '', string $sender = '')
 * @method static string addBlacklist(array|string $phoneNumbers = '')
 * @method static string deleteBlackList(array|string $phoneNumbers = '')
 * @method static array|string getMessageReport(string $messageId)
 * @method static string parseOutput(string $output)
 * @method static bool getStatus(string $output)
 * @method static string|null getMessageId(string $output)
 */
class Mutlucell extends Facade
{

  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor()
  {
    return 'mutlucell';
  }
}
