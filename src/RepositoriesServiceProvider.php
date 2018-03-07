<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories;

use Illuminate\Support\ServiceProvider;
use Saad\Repositories\Commands\MakeCreteria;
use Saad\Repositories\Commands\MakeMutator;
use Saad\Repositories\Commands\MakeRepository;

class RepositoriesServiceProvider extends ServiceProvider {

	public function boot()
	{
		// Register Generator Command
		if ($this->app->runningInConsole()) {
	        $this->commands([
	            MakeRepository::class,
	            MakeMutator::class,
	            MakeCreteria::class,
	        ]);
	    }
	}

	public function register()
	{
		# code...
	}
}