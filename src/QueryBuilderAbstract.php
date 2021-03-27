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
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

abstract class QueryBuilderAbstract
{
    private Request $request;
    protected Fields $fields;
    protected QueryParamBag $includes;
    protected QueryParamBag $filters;
    protected Sorts $sorts;
    protected PaginationParams $paginationParams;
    protected ?int $defaultPageSize = null;
    private array $allowedIncludes = [];
    private ?array $allPossibleAvailableIncludes = null;
    protected array $availableIncludes = [];
    protected array $availableFilters = [];
    protected array $availableSorts = [];

    /** @var EloquentBuilder|QueryBuilder */
    private $query;

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

    private function getPossibleRelationshipCombinations(array $relations): array
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

    public function get(array $columns = ['*'])
    {
        if ($this->shouldPaginate()) {
            return $this->paginate($columns);
        }

        return $this->query()->get($columns);
    }

    public function paginateWithTotal(array $columns = ['*']): LengthAwarePaginator
    {
        return $this->paginate($columns, true);
    }

    private function paginate(array $columns, bool $withTotalCount = false)
    {
        $paginate = $withTotalCount ? 'paginate' : 'simplePaginate';

        return $this->query()->{$paginate}(
            $this->paginationParams->perPage($this->defaultPageSize),
            $columns,
            $this->paginationParams->pageName(),
            $this->paginationParams->page(1)
        );
    }

    private function shouldPaginate(): bool
    {
        return $this->defaultPageSize !== null || $this->paginationParams->filled();
    }

    public function first(array $columns = ['*'])
    {
        return $this->query()->first($columns);
    }

    private function loadIncludes(QueryParamBag $includes): void
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

    private function applyFilters(QueryParamBag $filters): void
    {
        $filters->each(function ($params, $scope) {
            $this->applyFilter($scope, $params);
        });
    }

    private function applyFilter($scope, $params): void
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

    private function applySorts(Sorts $sorts): void
    {
        $sorts->each(function (SortField $sortField) {
            $this->applySort($sortField->getField(), $sortField->getDirection(), $sortField->getParams());
        });
    }

    private function applySort(string $field, string $direction, string $param = null): void
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
