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
 * @method static string|int send(string $receiver, string $message = '', string $date = '', string $sender = '')
 * @method static string|int sendBulk(array $recipients, string $message = '', string $date = '', string $sender = '')
 * @method static string sendMulti(array $messages, string $date = '', string $sender = '')
 * @method static string addBlacklist(array $phoneNumbers = [])
 * @method static string deleteBlacklist(array $phoneNumbers = [], bool $confirm = false)
 * @method static array getMessageReport(string|int $messageId)
 * @method static string|int parseOutput(string|int $output)
 * @method static bool getStatus(string|int $output)
 * @method static string|null getMessageId(string $output)
 */
class Mutlucell extends Facade
{

  /**
   * Get the registered name of the component.
   *
   * @return string
   */
  protected static function getFacadeAccessor(): string
  {
    return 'mutlucell';
  }
}
