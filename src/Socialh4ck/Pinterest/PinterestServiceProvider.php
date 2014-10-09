<?php namespace Socialh4ck\Pinterest;

use Illuminate\Support\ServiceProvider;

class PinterestServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('socialh4ck/pinterest');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['pinterest'] = $this->app->share(function($app){
			return new Pinterest($app['config']->get('pinterest::config'));
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('pinterest');
	}

}