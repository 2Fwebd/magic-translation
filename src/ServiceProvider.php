<?php

namespace Alkalab\MagicTranslate;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

/**
 * Class ServiceProvider
 *
 * Register the command to Laravel
 *
 * @package Alkalab\MagicTranslate
 */
class ServiceProvider extends BaseServiceProvider
{

	/**
	 * Bootstrap any application services.
	 *
	 * @return void
	 */
	public function boot()
	{
		if ($this->app->runningInConsole()) {
			$this->commands([
				Translate::class,
			]);
		}
	}

}