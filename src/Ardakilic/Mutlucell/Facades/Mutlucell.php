<?php
namespace Ardakilic\Mutlucell\Facades;

/**
 * Laravel 5 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <arda@kilicdagi.com>
 * @link http://arda.pw
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