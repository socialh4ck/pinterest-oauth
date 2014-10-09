<?php namespace Socialh4ck\Pinterest\Facades;

use Illuminate\Support\Facades\Facade;

class PinterestFacade extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'pinterest'; }

}