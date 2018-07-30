<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories\Contracts;

use Saad\Repositories\Contracts\RepositoryContract;
use Closure;

interface HasPaginationContract {

    /**
     * Get All Paginated
     * @param integer $per_page how many records per page
     * @param array $columns
     */
	public function paginate(int $per_page, array $columns);

    /**
     * callback will be passed paginated collection
     * to add more flexibility like appends() method
     *
     * @param \Closure $callback callback that returns
     * @return \Saad\Repositories\Contracts\RepositoryContract
     */
	public function afterPaginated(Closure $callback) :RepositoryContract;

}