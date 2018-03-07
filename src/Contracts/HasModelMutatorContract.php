<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\MutatorContract as Mutator;
use Saad\Repositories\Contracts\RepositoryContract as Repository;

interface HasModelMutatorContract {

	/**
	 * Skip Mutator collection
	 * 
	 * @return Repository Repository
	 */
	public function skipMutator(bool $status) :Repository;

	/**
	 * add Mutator to creteria collection
	 *
	 * @param Mutator $mutator Mutator to be added to query builder
	 * @return Repository Repository
	 */
	public function setMutator(Mutator $mutator) :Repository;
}