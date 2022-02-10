<?php
namespace Ardakilic\Mutlucell\Facades;

/**
 * Laravel 9 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link https://arda.pw
 *
 */

use Illuminate\Support\Facades\Facade;

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
