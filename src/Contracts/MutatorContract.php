<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;

interface MutatorContract {

	/**
	 * Create Object
	 * 
	 * @return Model Eloquent Model
	 */
	public function create(array $attributes) :Model;

	/**
	 * Update Object
	 * 
	 * @return Model Eloquent Model
	 */
	public function update(Model $object, array $attributes) :bool;

}