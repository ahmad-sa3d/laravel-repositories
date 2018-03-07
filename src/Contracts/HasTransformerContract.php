<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\RepositoryContract;
use Saad\Fractal\Transformers\TransformerAbstract;

interface HasTransformerContract {

	/**
	 * Define Default Transformer
	 * 
	 * @return string|TransformerAbstract transformer used to transform repository output
	 */
	public function transformer();

	/**
	 * Set Transformer
	 * 
	 * @param  TransformerAbstract $transformer Transformer to transform data with
	 * @return RepositoryContract                           repository
	 */
	public function transformWith(TransformerAbstract $transformer) :RepositoryContract;

	/**
	 * skip Transformer
	 * 
	 * @param  bool   $status true to skip, false to keep
	 * @return RepositoryContract         repository
	 */
	public function skipTransformer(bool $status) :RepositoryContract;

}