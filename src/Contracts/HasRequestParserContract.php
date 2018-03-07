<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\RepositoryContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

interface HasRequestParserContract {

	/**
	 * Prepare Given context
	 * 
	 * @param Builder|Model $contect given context should be either Eloquent Model or Eloquent Builder
	 * @return object output object
	 */
	public function prepare($context);

	/**
	 * skip Request parser
	 * 
	 * @param  bool   $status true to skip, false to keep
	 * @return RepositoryContract         repository
	 */
	public function skipPreparer(bool $status) :RepositoryContract;

}