<?php
namespace Ardakilic\Mutlucell\Facades;

/**
 * Laravel 4 Mutlucell SMS
 * @license MIT License
 * @author Arda Kılıçdağı <ardakilicdagi@gmail.com>
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
