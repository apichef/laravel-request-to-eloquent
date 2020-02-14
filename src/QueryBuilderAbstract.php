<?php

declare(strict_types=1);

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestQueryHelper\Fields;
use ApiChef\RequestQueryHelper\QueryParamBag;
use ApiChef\RequestQueryHelper\SortField;
use ApiChef\RequestQueryHelper\Sorts;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

abstract class QueryBuilderAbstract
{
    /** @var Request $request */
    private $request;

    /** @var Fields $fields */
    protected $fields;

    /** @var QueryParamBag $includes */
    protected $includes;

    /** @var QueryParamBag $filters */
    protected $filters;

    /** @var Sorts $sorts */
    protected $sorts;

    /** @var EloquentBuilder|QueryBuilder $request */
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
        $this->query = $this->init();
    }

    /**
     * Initialise the query.
     *
     * @return EloquentBuilder|QueryBuilder
     */
    abstract protected function init();

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
        if (! empty($this->allowedIncludes) && $this->request->filled(config('request-query-helper.include'))) {
            $this->loadIncludes($this->includes);
        }

        if ($this->request->filled(config('request-query-helper.filter'))) {
            $this->applyFilters($this->filters);
        }

        if ($this->request->filled(config('request-query-helper.sort'))) {
            $this->applySorts($this->sorts);
        }

        return $this->query;
    }

    /**
     * Execute the query.
     *
     * @return Collection
     */
    public function get()
    {
        return $this->query()->get();
    }

    /**
     * Execute the query and get the first result.
     *
     * @return Model|object|null
     */
    public function first()
    {
        return $this->query()
            ->take(1)
            ->get()
            ->first();
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

        throw new \RuntimeException("Trying to include non existing relationship {$relation}");
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

        throw new \RuntimeException("Trying to filter by non existing filter {$scope}");
    }

    private function applySorts(Sorts $sorts)
    {
        $sorts->each(function (SortField $sortField) {
            $this->applySort($sortField->getField(), $sortField->getDirection());
        });
    }

    private function applySort(string $field, string $direction)
    {
        if ($sortAlias = Arr::get($this->sortsMap(), $field)) {
            $this->query->orderBy($sortAlias, $direction);

            return;
        }

        $methodName = 'sortBy'.Str::studly($field);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($this->query, $direction);

            return;
        }

        if (in_array($field, $this->availableSorts)) {
            $this->query->orderBy($field, $direction);

            return;
        }

        throw new \RuntimeException("Trying to sort by non existing field {$field}");
    }
}
