<?php

/**
 * @package  laravel/repositories
 *
 * @author Ahmed Saad <a7mad.sa3d.2014@gmail.com>
 * @license MIT MIT
 */

namespace Saad\Repositories;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use Illuminate\Support\Collection;
use League\Fractal\Pagination\IlluminatePaginatorAdapter;
use Saad\Fractal\Fractal;
use Saad\Fractal\Transformers\TransformerAbstract;
use Saad\QueryParser\RequestQueryParser;
use Saad\Repositories\Contracts\CreteriaContract;
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
	HasPaginationContract {

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
	
	public function __construct(Application $app, Collection $creteria)
	{
		$this->app = $app;
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
	 * @param  integer    $id      [description]
	 * @param  array|null $columns [description]
	 * @return [type]              [description]
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
	 */
	public function whereQuery(Closure $callback, $columns = ['*']) {
		call_user_func($callback, $this->builder);

		return $this->execute($columns);
	}

	/**
	 * Create New Record
	 * 
	 * @param  array $attributes
	 */
	public function create(array $attributes) {
		// Creating
		if (!$this->skip_mutator && $this->mutator) {
			$object = $this->mutator->create($attributes);
		} else {
			$object = $this->model->create($attributes);
		}

		// Created
		return $this->prepareObject($object);
	}

	/**
	 * Update Record
	 * 
	 * @param  array $attributes
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

		// Updated
		return $this->prepareObject($object);
	}

	/**
	 * Delete Record
	 * 
	 * @param  integer $id record id
	 */
	public function delete(int $id) {
		// Deleting
		return $this->model->destroy($id);

		// Deleted
	}

	/**
	 | ---------------------------------------------------------
	 | 				HasCreteria Implementation
	 | ---------------------------------------------------------
	 */
	
	/**
	 * Apply Creteria on Builder
	 * 
	 * @return Repository Repository
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
	 * @return Repository Repository
	 */
	public function skipCreteria(bool $status = true) :RepositoryContract {
		$this->skip_creteria = $status;
		return $this;
	}

	/**
	 * add Creteria to creteria collection
	 *
	 * @param Creteria $creteria Creteria to be added to query builder
	 * @return Repository Repository
	 */
	public function pushCreteria(CreteriaContract $creteria) :RepositoryContract {
		$this->creteria->push($creteria);
		return $this;
	}

	/**
	 * reset creteria collection
	 *
	 * @param Creteria $creteria Creteria to be added to query builder
	 * @return Repository Repository
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
	 * @param Creteria $creteria Creteria to be added to query builder
	 * @return Repository Repository
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
	 * @return Repository               Repository
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
	 * @return Repository Repository
	 */
	public function skipMutator(bool $status = true) :RepositoryContract {
		$this->skip_mutator = $status;
		return $this;
	}

	/**
	 * add Mutator to creteria collection
	 *
	 * @param Mutator $mutator Mutator to be added to query builder
	 * @return Repository Repository
	 */
	public function setMutator(MutatorContract $mutator) :RepositoryContract {
		$this->mutator = $mutator;
		return $this;
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

		return $this->export($data);
	}

	/**
	 * Output Data
	 * 
	 * @param  object|Collection $data data to output
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