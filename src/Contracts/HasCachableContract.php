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