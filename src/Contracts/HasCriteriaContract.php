<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\CriteriaContract as Criteria;
use Saad\Repositories\Contracts\RepositoryContract as Repository;

interface HasCriteriaContract {
	
	/**
	 * Apply Criteria on Builder
	 * 
	 * @return Repository Repository
	 */
	public function applyCriteria() :RepositoryContract;

	/**
	 * Skip Criteria collection
	 * 
	 * @return Repository Repository
	 */
	public function skipCriteria() :RepositoryContract;

	/**
	 * Reset Criteria collection
	 * 
	 * @return Repository Repository
	 */
	public function resetCriteria() :RepositoryContract;

	/**
	 * add Criteria to criteria collection
	 *
	 * @param Criteria $criteria Criteria to be added to query builder
	 * @return Repository Repository
	 */
	public function pushCriteria(Criteria $criteria) :RepositoryContract;

	/**
	 * apply criteria directly to query builder
	 *
	 * skipCriteria has no effect
	 *
	 * @param Criteria $criteria Criteria to be added to query builder
	 * @return Repository Repository
	 */
	public function getByCriteria(Criteria $criteria) :RepositoryContract;
}