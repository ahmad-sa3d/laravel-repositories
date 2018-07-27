<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Saad\Fractal\Fractal;
use Saad\Fractal\Transformers\TransformerAbstract;
use Saad\QueryParser\RequestQueryParser;
use Saad\Repositories\Contracts\CreteriaContract;
use Saad\Repositories\Contracts\HasCachableContract;
use Saad\Repositories\Contracts\HasCreteriaContract;
use Saad\Repositories\Contracts\HasModelMutatorContract;
use Saad\Repositories\Contracts\HasPaginationContract;
use Saad\Repositories\Contracts\HasRequestParserContract;
use Saad\Repositories\Contracts\HasTransformerContract;
use Saad\Repositories\Contracts\MutatorContract;
use Saad\Repositories\Contracts\RepositoryContract;

abstract class BaseRepository implements
	RepositoryContract,
	HasCreteriaContract,
	HasTransformerContract,
	HasRequestParserContract,
	HasModelMutatorContract,
	HasPaginationContract,
	HasCachableContract {

	/**
	 * Eloquent Model Instance
	 * 
	 * @var Model
	 */
	protected $model;

	/**
	 * Model Query Builder
	 * 
	 * @var Model
	 */
	protected $builder;

	/**
	 * Creteria Collection
	 * 
	 * @var Collection
	 */
	protected $creteria;

	/**
	 * Model Mutator
	 * 
	 * @var Mutator
	 */
	protected $mutator;

	/**
	 * Cache Key
	 * 
	 * @var string
	 */
	protected $cache_key;

	/**
	 * Cache Tags
	 * 
	 * @var string
	 */
	protected $cache_tags;

	/**
	 * Cache TTL
	 * 
	 * @var Carbon
	 */
	protected $cache_ttl;

	/**
	 * skip creteria or not
	 * 
	 * @var bool
	 */
	protected $skip_creteria = false;

	/**
	 * Skip Transformer
	 * 
	 * @var boolean
	 */
	protected $skip_transformer = false;

	/**
	 * Skip Preparer
	 * 
	 * @var boolean
	 */
	protected $skip_preparer = false;

	/**
	 * Skip Model Mutator
	 * 
	 * @var boolean
	 */
	protected $skip_mutator = false;

	/**
	 * call back to run after paginate
	 * 
	 * @var \Closure
	 */
	protected $afterPaginationCallback;
	

	/**
	 * Application Instance
	 * 
	 * @var Application
	 */
	protected $app;
	
	public function __construct(Collection $creteria)
	{
		$this->app = app();
		$this->creteria = $creteria;
		$this->makeModel();
		$this->makeTransformer($this->transformer());
	}

	/**
	 * Function to return Concrete Model Class Name
	 * 
	 * @return string Model Name
	 */
	abstract public function model();


	/**
	 | --------------------------------
	 | RepositoryContract Implementation
	 | ---------------------------------
	 */
	
	/**
	 * Reset Query Scope
	 * 
	 * @return BaseRepository instance
	 */
	public function resetBuilder() :RepositoryContract
	{
		$this->builder = $this->model->newQuery();
		return $this;
	}

	/**
	 * Find By Id
	 * @param  integer $id [description]
	 * @param  array|null $columns [description]
	 * @return Collection|object|Fractal [type]              [description]
	 */
	public function find(int $id, array $columns = ['*'])
	{
		$this->builder->whereId($id);
		return $this->execute($columns, true);
	}

	/**
	 * Find By Attribute
	 *
	 * @param  string $attribute attribute
	 * @param  mix $value Attribute Value
	 * @param array $columns columns to get
	 * @return Collection|object|Fractal
	 */
	public function findBy(string $attribute, $value, array $columns = ['*'])
	{
		$this->builder->where($attribute, $value);
		return $this->execute($columns, true);
	}


	/**
	 * Find All Records
	 */
	public function all(array $columns = ['*'])
	{
		return $this->execute($columns);
	}

	/**
	 * Search By Custom Field
	 *
	 * @param  integer $id key of where clause
	 * @param  mix $value_or_operator value of where clause or operator of where
	 * @param  mix $value value of where clause
	 * @param array $columns columns to get
	 * @return Collection|object|Fractal
	 */
	public function where(string $attribute, $value_or_operator, $value = null, array $columns = ['*'])
	{
		if (!$value) {
			$value = $value_or_operator;
			$operator = '=';
		} else {
			$operator = $value_or_operator;
		}

		$this->builder->where($attribute, $operator, $value);

		return $this->execute($columns);
	}

	/**
	 * Find By Custom Query
	 *
	 * @param  Closure $callback Callback to run custom query
	 * @return Collection|object|Fractal
	 */
	public function whereQuery(Closure $callback, $columns = ['*']) {
		call_user_func($callback, $this->builder);

		return $this->execute($columns);
	}

	/**
	 * Create New Record
	 *
	 * @param  array $attributes
	 * @return object
	 */
	public function create(array $attributes) {
		// Creating
		if (!$this->skip_mutator && $this->mutator) {
			$object = $this->mutator->create($attributes);
		} else {
			$object = $this->model->create($attributes);
		}

		// Flush Cache
		$this->flushCache();

		// Created
		return $this->prepareObject($object);
	}

	/**
	 * Update Record
	 *
	 * @param  array $attributes
	 * @return object
	 */
	public function update($id_or_object, array $attributes) {
		if (is_int($id_or_object) || ctype_digit((string) $id_or_object)) {
			// ID
			$object = $this->model->findOrFail($id_or_object);
		} else if (!($id_or_object instanceof $this->model)) {
			throw new \InvalidArgumentException(__METHOD__ . ' requires the first attribute to be avalid model of type ' . get_class($this->model) . ' or id');
		} else {
			$object = $id_or_object;
		}

		// Updating
		if (!$this->skip_mutator && $this->mutator) {
			$result = $this->mutator->update($object, $attributes);
		} else {
			$object->update($attributes);
		}

		// Flush Cache
		$this->flushCache();

		// Updated
		return $this->prepareObject($object);
	}

	/**
	 * Delete Record
	 *
	 * @param  integer $id record id
	 * @return int
	 */
	public function delete(int $id) {
		// Deleting
		$deleted = $this->model->destroy($id);

		if ($deleted) {
			// Flush Cache
			$this->flushCache();
		}

		return $deleted;
	}

	/**
	 | ---------------------------------------------------------
	 | 				HasCreteria Implementation
	 | ---------------------------------------------------------
	 */

	/**
	 * Apply Creteria on Builder
	 *
	 * @return RepositoryContract Repository
	 */
	public function applyCreteria() :RepositoryContract {
		if ($this->skip_creteria) {
			return $this;
		}

		$this->creteria->each(function ($creteria) {
			if (! $creteria instanceof CreteriaContract) {
				return;
			}

			$this->getByCreteria($creteria);
		});

		return $this;
	}

	/**
	 * Skip Creteria collection
	 *
	 * @param bool $status
	 * @return RepositoryContract Repository
	 */
	public function skipCreteria(bool $status = true) :RepositoryContract {
		$this->skip_creteria = $status;
		return $this;
	}

	/**
	 * add Creteria to creteria collection
	 *
	 * @param CreteriaContract $creteria Creteria to be added to query builder
	 * @return RepositoryContract Repository
	 */
	public function pushCreteria(CreteriaContract $creteria) :RepositoryContract {
		$this->creteria->push($creteria);
		return $this;
	}

	/**
	 * reset creteria collection
	 *
	 * @return RepositoryContract Repository
	 */
	public function resetCreteria() :RepositoryContract {
		$this->creteria = collect();
		return $this;
	}

	/**
	 * apply creteria directly to query builder
	 *
	 * skipCreteria has no effect
	 *
	 * @param CreteriaContract $creteria Creteria to be added to query builder
	 * @return RepositoryContract Repository
	 */
	public function getByCreteria(CreteriaContract $creteria) :RepositoryContract {
		$creteria->apply($this->builder, $this);
		return $this;
	}

	/**
	 | ---------------------------------------------------------
	 | 			HasTransformerContract Implementation
	 | ---------------------------------------------------------
	 */

	/**
	 * Function to return Transformer Model Class Name
	 * 
	 * @return string Model Name
	 */
	public function transformer() {
		return null;
	}

	/**
	 * Function to return Transformer Model Class Name
	 * 
	 * @return string Model Name
	 */
	public function transformWith(TransformerAbstract $transformer) :RepositoryContract{
		$this->makeTransformer($transformer);
		return $this;
	}

	/**
	 * Skip Defined Transformer
	 * 
	 * @param  bool 				$status  	true to skip false to keep		
	 * @return RepositoryContract               Repository
	 */
	public function skipTransformer(bool $status = true) :RepositoryContract{
		$this->skip_transformer = $status;
		return $this;
	}

	/**
	 | ---------------------------------------------------------
	 | 			HasRequestParserContract Implementation
	 | ---------------------------------------------------------
	 */

	/**
	 * prepare from given context builder Or Object
	 * 
	 * @param  Builder $context EloqiuentBuilder Or Model Object
	 * @return object          Output Result
	 */
	public function prepare($context) {
		// Setup
		if ($context instanceof Builder) {
			$context_model = $context->getModel();
			$builder = true;
		} else {
			$context_model = $context;
			$builder = false;
		}

		// Validate Context
		if (! is_a($context_model, $this->model())) {
			throw new \RuntimeException(__METHOD__ . " the given context must be Eloquent Builder or eloquent Model for {$this->model()}");
		}

		// Prepare and get output
		if ($builder) {
			$this->builder = $context;
			return $this->execute();
		} else {
			return $this->prepareObject($context);
		}
	}


	/**
	 * skip praparer
	 *
	 * @param  bool|boolean $status status
	 * @return RepositoryContract Repository
	 */
	public function skipPreparer(bool $status = true) :RepositoryContract
	{
		$this->skip_preparer = $status;
		return $this;
	}

		/**
	 | ---------------------------------------------------------
	 | 			HasPaginationContract Implementation
	 | ---------------------------------------------------------
	 */

	/**
	 * Find All Records Paginated
	 */
	public function paginate(int $per_page = 10, array $columns = ['*'])
	{
		return $this->execute($columns, false, $per_page);
	}

	/**
	 * callback will be passed paginated collection
	 * to add more flexibility like appends() method
	 *
	 * @param \Closure $callback callback that returns
	 * @return RepositoryContract
	 */
	public function afterPaginated(\Closure $callback) :RepositoryContract
	{
		$this->afterPaginationCallback = $callback;
		return $this;
	}

	/**
	 | ---------------------------------------------------------
	 | 			HasModelMutatorContract Implementation
	 | ---------------------------------------------------------
	 */

	/**
	 * Skip Mutator collection
	 *
	 * @param bool $status
	 * @return RepositoryContract Repository
	 */
	public function skipMutator(bool $status = true) :RepositoryContract {
		$this->skip_mutator = $status;
		return $this;
	}

	/**
	 * add Mutator to creteria collection
	 *
	 * @param MutatorContract $mutator Mutator to be added to query builder
	 * @return RepositoryContract Repository
	 */
	public function setMutator(MutatorContract $mutator) :RepositoryContract {
		$this->mutator = $mutator;
		return $this;
	}

	/**
	 | ---------------------------------------------------------
	 | 				HasCachable Implementation
	 | ---------------------------------------------------------
	 */
	
	/**
	 * Disable cache if one of the given inputs exists on request
	 * 
	 * @param  array  $inputs request inputs
	 * @return RepositoryContract         Repo instance
	 */
	public function donotCacheWhenInputs(array $inputs = []) :RepositoryContract {
		foreach($inputs as $input) {
			if (Request::input($input)) {
				$this->cache_key = false;
				break;
			}
		}

		return $this;
	}


	/**
	 * Use Temp cache if one of the given inputs exists on request
	 * 
	 * @param  array  $inputs request inputs
	 * @return RepositoryContract         Repo instance
	 */
	public function cacheByTTLWhenInputs(array $inputs = [], int $ttl = null) :RepositoryContract {
		foreach($inputs as $input) {
			if (Request::input($input)) {
				$this->cache_ttl = $ttl ?? env('REPO_CACHE_TTL', 60);
				break;
			}
		}

		return $this;
	}
	
	/**
	 * Cache Result
	 * 
	 * @param  string|null   $key  static cache key or dynamic if null
	 * @param  array    $tags cache extra tags
	 * @param  int|null $ttl  cache TTL or forever if null
	 * @return RepositoryContract         Repo instance
	 */
	public function cachable($key = null, array $tags = [], int $ttl = null) :RepositoryContract {
		if (false === $this->cache_key) {
			return $this;
		}

		$this->cache_key = $key ? $key : $this->getRequestCacheKey();

		if ($ttl && !$this->cache_ttl) {
			$this->cache_ttl = Carbon::now()->addMinutes($ttl);
		}

		$this->cache_tags = array_merge([
			class_basename($this->model()),
		], $tags);

		return $this;
	}

	/**
	 * Flush Cache'
	 * 
	 * @return void
	 */
	public function flushCache() {
		Cache::tags([
			class_basename($this->model()),
		])->flush();
	}


	/**
	 * Generate Cache Key from current request
	 * 
	 * @return [type] [description]
	 */
	private function getRequestCacheKey() {
		$request_signature = collect(request()->all())->filter(function($item, $key) {
			return $item && $item != '0';
		})
		->sortKeys()
		->transform(function($item, $key) {
			return "{$key}:{$item}";
		})
		->reduce(function($carry, $item) {
			return $carry ? $carry .';'. $item : $item;
		});

		return md5(request()->path() .';'. $request_signature);
	}

	/**
	 | ---------------------------------------------------------
	 | 			Protected or Private Methods
	 | ---------------------------------------------------------
	 */

	/**
	 * Create Model Instance
	 */
	protected function makeModel()
	{
		$this->model = $this->app->make($this->model());
		if (! $this->model instanceof Model) {
			throw new \RuntimeException(__METHOD__ . " Class {$this->model()} must be instance of Model");
		}

		// Crreate New Model Query Builder
		$this->resetBuilder();
	}

	/**
	 * Create Transformer Model Instance
	 */
	protected function makeTransformer($transformer)
	{
		if (is_null($transformer)) {
			$this->transformer = null;
			return $this;
		}

		if (! is_a($transformer, TransformerAbstract::class, true)) {
			throw new \RuntimeException(__METHOD__ . " {$transformer} must be class of type TransformerAbstract");
		}

		$this->transformer = is_object($transformer) ? $transformer : new $transformer;
	}

	/**
	 * Execute Query
	 * 
	 * @param  array|null $columns      array of columns to select
	 * @param  boolean    $sinle_object get only the first object or all collection
	 * @return object|Fractal|Collection                   output result
	 */
	protected function execute(array $columns = ['*'], $sinle_object = false, int $per_page = null) {
		// Check Cache
		if ($this->cache_key) {
			if (Cache::tags($this->cache_tags)->has($this->cache_key)) {
				Log::info('Get From Repo Cache', ['key' => $this->cache_key, 'tags' => $this->cache_tags]);
				return Cache::tags($this->cache_tags)->get($this->cache_key);
			}
		}

		// Prepare First because we might add selections by creteria
		$this->applyPreparer();

		// Apply Creteria
		$this->applyCreteria();

		// Execute Query
		if ($per_page) {
			// Paginated
			$data = $this->builder->paginate($per_page, $columns);
			if (is_callable($this->afterPaginationCallback)) {
				call_user_func($this->afterPaginationCallback, $data);
			}
		} else {
			// get|first
			$method = $sinle_object ? 'first' : 'get';
			$data = $this->builder->$method($columns);
		}

		// Reset Scope
		$this->resetBuilder();

		$data = $this->export($data);

		// Put to Cache
		if ($this->cache_key) {
			if ($this->cache_ttl) {
				Log::info('Put To Repo Cache Temp', ['key' => $this->cache_key, 'tags' => $this->cache_tags]);
				Cache::tags($this->cache_tags)->put($this->cache_key, $data, $this->cache_ttl);
			} else {
				Log::info('Put To Repo Cache Forever', ['key' => $this->cache_key, 'tags' => $this->cache_tags]);
				Cache::tags($this->cache_tags)->forever($this->cache_key, $data);
			}
		}

		return $data;
	}

	/**
	 * Output Data
	 *
	 * @param  object|Collection $data data to output
	 * @return Collection|object|\Spatie\Fractalistic\Fractal
	 */
	protected function export($data)
	{
		if ($this->transformer && !$this->skip_transformer) {
			// Use Transformer
			return $this->exportWithTransformer($data);
		} else {
			return $data;
		}
	}

	/**
	 * Export output by transformer
	 * @param  object|Collection $data output data
	 * @return \Spatie\Fractalistic\Fractal
	 */
	protected function exportWithTransformer($data)
	{
		$output = Fractal::create($data, $this->transformer);

		if ($data instanceof LengthAwarePaginator) {
			$output = $output->paginateWith(new IlluminatePaginatorAdapter($data));
		}

		return $output;
	}

	/**
	 * Prepare given object
	 *
	 * @param  Model $object Eloquent Model
	 * @return object         output
	 */
	protected function prepareObject($object) {
		// Prepare Relations from request
		if (! $this->skip_preparer && method_exists($this, 'canHave')) {
			$models_to_load = $this->canHave();
			if (!is_array($models_to_load)) {
				throw new \RuntimeException("canHave method should return array!");
			}

			foreach($models_to_load as $key => $model) {
				if (is_array($model)) {
					foreach ($model as $context_key) {
						RequestQueryParser::loadOnContext($key, $object, null, $context_key);
						
						// For Count
						RequestQueryParser::loadOnContext($key, $object, null, $context_key, true);
					}
				} else {
					RequestQueryParser::loadOnContext($model, $object, null);

					// For Count
					RequestQueryParser::loadOnContext($model, $object, null, null, true);
				}
			}
		}

		return $this->export($object);
	}

	/**
	 * Apply Request Query Parser
	 * 
	 * @return RepositoryContract               Repository
	 */
	protected function applyPreparer() {
		if ($this->skip_preparer) {
			return $this;
		}

		RequestQueryParser::prepare($this->model(), $this->builder);

		return $this;
	}
	
}