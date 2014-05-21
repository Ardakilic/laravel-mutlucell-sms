<?php namespace Ardakilic\Mutlucell\Facades;

/**
 * Laravel 4 Mutlucell SMS
 * @author Arda Kılıçdağı <ardakilicdagi@gmail.com>
 * @web http://arda.pw
 *
*/

use Illuminate\Support\Facades\Facade;

class Mutlucell extends Facade {

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
