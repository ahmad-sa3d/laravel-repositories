<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Illuminate\Database\Eloquent\Model;
use Closure;

interface RepositoryContract {
	/**
	 * Find By Id
	 * 
	 * @param  integer $id Record Id
	 * @param array $columns columns to get
	 */
	public function find(int $id, array $columns);

	/**
	 * Find By Attribute
	 * 
	 * @param  string $attribute attribute
	 * @param  mix $value Attribute Value
	 * @param array $columns columns to get
	 */
	public function findBy(string $attribute, $value, array $columns);

	/**
	 * Get All Records
	 * @param array $columns columns to get
	 */
	public function all(array $columns);

	/**
	 * Search By Custom Field
	 * 
	 * @param  integer $id key of where clause
	 * @param  mix $value_or_operator value of where clause or operator of where
	 * @param  mix $value value of where clause
	 * @param array $columns columns to get
	 */
	public function where(string $attribute, $value_or_operator, $value, array $columns);

	/**
	 * Find By Custom Query
	 * 
	 * @param  Closure $callback Callback to run custom query
	 */
	public function whereQuery(Closure $callback);

	/**
	 * Create New Record
	 * 
	 * @param  array $attributes
	 */
	public function create(array $attributes);

	/**
	 * Update Record
	 * 
	 * @param  integer $id record id
	 * @param  array $attributes
	 */
	public function update(int $id, array $attributes);

	/**
	 * Delete Record
	 * 
	 * @param  integer $id record id
	 */
	public function delete(int $id);

	/**
	 * Reset Model Scope and start new query
	 */
	public function resetBuilder() :RepositoryContract;

}