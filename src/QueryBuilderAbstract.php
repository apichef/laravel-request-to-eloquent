<?php

declare(strict_types=1);

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestQueryHelper\Fields;
use ApiChef\RequestQueryHelper\PaginationParams;
use ApiChef\RequestQueryHelper\QueryParamBag;
use ApiChef\RequestQueryHelper\SortField;
use ApiChef\RequestQueryHelper\Sorts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

abstract class QueryBuilderAbstract
{
    /** @var Request */
    private $request;

    /** @var Fields */
    protected $fields;

    /** @var QueryParamBag */
    protected $includes;

    /** @var QueryParamBag */
    protected $filters;

    /** @var Sorts */
    protected $sorts;

    /** @var PaginationParams */
    protected $paginationParams;

    protected $defaultPageSize = null;

    /** @var EloquentBuilder|QueryBuilder */
    private $query;

    private $allowedIncludes = [];

    private $allPossibleAvailableIncludes = null;

    protected $availableIncludes = [];

    protected $availableFilters = [];

    protected $availableSorts = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->fields = $request->fields();
        $this->includes = $request->includes();
        $this->filters = $request->filters();
        $this->sorts = $request->sorts();
        $this->paginationParams = $request->paginationParams();
        $this->query = $this->init($request);
    }

    /**
     * Initialise the query.
     *
     * @param Request $request
     * @return EloquentBuilder|QueryBuilder
     */
    abstract protected function init(Request $request);

    protected function includesMap(): array
    {
        return [];
    }

    protected function filtersMap(): array
    {
        return [];
    }

    protected function sortsMap(): array
    {
        return [];
    }

    public function parseAllowedIncludes(array $allowedIncludes): self
    {
        $this->allowedIncludes = $this->getPossibleRelationshipCombinations($allowedIncludes);

        return $this;
    }

    private function getPossibleRelationshipCombinations(array $relations)
    {
        $combinations = [];

        foreach ($relations as $relation) {
            $combination = null;
            foreach (explode('.', $relation) as $part) {
                $combination .= is_null($combination) ? $part : ".{$part}";
                if (! in_array($combination, $combinations)) {
                    array_push($combinations, $combination);
                }
            }
        }

        return $combinations;
    }

    /**
     * Build and get query.
     *
     * @return EloquentBuilder|QueryBuilder
     */
    public function query()
    {
        if (! empty($this->allowedIncludes) && $this->includes->filled()) {
            $this->loadIncludes($this->includes);
        }

        if ($this->filters->filled()) {
            $this->applyFilters($this->filters);
        }

        if ($this->sorts->filled()) {
            $this->applySorts($this->sorts);
        }

        return $this->query;
    }

    /**
     * Execute the query.
     *
     * @param array $columns
     * @return Collection|LengthAwarePaginator
     */
    public function get($columns = ['*'])
    {
        if ($this->paginationParams->filled()) {
            return $this->query()->paginate(
                $this->paginationParams->perPage(),
                $columns,
                $this->paginationParams->pageName(),
                $this->paginationParams->page()
            );
        }

        if ($this->defaultPageSize !== null) {
            return $this->query()->paginate(
                $this->defaultPageSize,
                $columns,
                $this->paginationParams->pageName(),
                1
            );
        }

        return $this->query()->get($columns);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param array $columns
     * @return Model|object|null
     */
    public function first($columns = ['*'])
    {
        return $this->query()->first($columns);
    }

    private function loadIncludes(QueryParamBag $includes)
    {
        $includes->each(function ($params, $relation) {
            if ($this->isAllowedToInclude($relation)) {
                $this->loadRelation($relation, $params);
            }
        });
    }

    private function isAllowedToInclude($relation): bool
    {
        return in_array($relation, $this->allowedIncludes);
    }

    private function loadRelation($relation, $params): void
    {
        if ($relationAlias = Arr::get($this->includesMap(), $relation)) {
            $this->query->with($relationAlias);

            return;
        }

        $methodName = 'include'.Str::studly(str_replace('.', 'With', $relation));
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($this->query, $params);

            return;
        }

        if (is_null($this->allPossibleAvailableIncludes)) {
            $this->allPossibleAvailableIncludes = $this->getPossibleRelationshipCombinations($this->availableIncludes);
        }

        if (in_array($relation, $this->allPossibleAvailableIncludes)) {
            $this->query->with($relation);

            return;
        }

        throw new RuntimeException("Trying to include non existing relationship {$relation}");
    }

    private function applyFilters(QueryParamBag $filters)
    {
        $filters->each(function ($params, $scope) {
            $this->applyFilter($scope, $params);
        });
    }

    private function applyFilter($scope, $params)
    {
        if ($filterAlias = Arr::get($this->filtersMap(), $scope)) {
            $this->query->{$filterAlias}($params);

            return;
        }

        $methodName = 'filterBy'.Str::studly($scope);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($this->query, $params);

            return;
        }

        if (in_array($scope, $this->availableFilters)) {
            $this->query->{$scope}($params);

            return;
        }

        throw new RuntimeException("Trying to filter by non existing filter {$scope}");
    }

    private function applySorts(Sorts $sorts)
    {
        $sorts->each(function (SortField $sortField) {
            $this->applySort($sortField->getField(), $sortField->getDirection(), $sortField->getParams());
        });
    }

    private function applySort(string $field, string $direction, string $param = null)
    {
        if ($sortAlias = Arr::get($this->sortsMap(), $field)) {
            $this->query->orderBy($sortAlias, $direction);

            return;
        }

        $methodName = 'sortBy'.Str::studly($field);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($this->query, $direction, $param);

            return;
        }

        if (in_array($field, $this->availableSorts)) {
            $this->query->orderBy($field, $direction);

            return;
        }

        throw new RuntimeException("Trying to sort by non existing field {$field}");
    }
}
