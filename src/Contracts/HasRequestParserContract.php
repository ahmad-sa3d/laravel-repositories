<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

interface HasRequestParserContract {

    /**
     * Prepare Given context
     *
     * @param $context
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