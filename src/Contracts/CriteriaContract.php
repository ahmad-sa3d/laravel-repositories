<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\RepositoryContract as Repository;
use Illuminate\Database\Eloquent\Builder;

interface CriteriaContract {
	
	/**
	 * Apply Criteria on the given query
	 * @param  Builder            $query Eloquent Query Builder
	 * @param  RepositoryContract $repo  Repository that uses criteria
	 * @return Builder                   
	 */
	public function apply(Builder $query, Repository $repo);

}