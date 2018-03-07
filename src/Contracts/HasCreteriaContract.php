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

interface HasCreteriaContract {
	
	/**
	 * Apply Creteria on Builder
	 * 
	 * @return Repository Repository
	 */
	public function applyCreteria() :RepositoryContract;

	/**
	 * Skip Creteria collection
	 * 
	 * @return Repository Repository
	 */
	public function skipCreteria() :RepositoryContract;

	/**
	 * add Creteria to creteria collection
	 *
	 * @param Creteria $creteria Creteria to be added to query builder
	 * @return Repository Repository
	 */
	public function pushCreteria(Creteria $creteria) :RepositoryContract;

	/**
	 * apply creteria directly to query builder
	 *
	 * skipCreteria has no effect
	 *
	 * @param Creteria $creteria Creteria to be added to query builder
	 * @return Repository Repository
	 */
	public function getByCreteria(Creteria $creteria) :RepositoryContract;
}