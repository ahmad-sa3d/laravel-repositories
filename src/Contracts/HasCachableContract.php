<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\CreteriaContract as Creteria;
use Saad\Repositories\Contracts\RepositoryContract as Repository;

interface HasCachableContract {

	/**
	 * Disable cache if one of the given inputs exists on request
	 * 
	 * @param  array  $inputs request inputs
	 * @return RepositoryContract         Repo instance
	 */
	public function donotCacheWhenInputs(array $inputs = []) :RepositoryContract;

	/**
	 * Use Temp cache if one of the given inputs exists on request
	 * 
	 * @param  array  $inputs request inputs
	 * @return RepositoryContract         Repo instance
	 */
	public function cacheByTTLWhenInputs(array $inputs = [], int $ttl = null) :RepositoryContract;
	
	/**
	 * Use Cache
	 * 
	 * @return Repository Repository
	 */
	public function cachable($key = null, array $tags = [], int $ttl = null) :RepositoryContract;

	/**
	 * Flush Cache
	 * 
	 * @return void
	 */
	public function flushCache();
}