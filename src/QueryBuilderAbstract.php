<?php

declare(strict_types=1);

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestQueryHelper\QueryParamBag;
use ApiChef\RequestQueryHelper\SortField;
use ApiChef\RequestQueryHelper\Sorts;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

abstract class QueryBuilderAbstract
{
    use InteractWithQueryString;

    protected ?int $defaultPageSize = null;
    protected array $availableIncludes = [];
    protected array $availableFilters = [];
    protected array $availableSorts = [];
    private array $allowedIncludes = [];
    private ?array $allPossibleAvailableIncludes = null;

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

    /**
     * @return EloquentBuilder|QueryBuilder
     */
    private function query()
    {
        $query = $this->init();

        if (! empty($this->allowedIncludes) && $this->includes()->filled()) {
            $this->loadIncludes($query, $this->includes());
        }

        if ($this->filters()->filled()) {
            $this->applyFilters($query, $this->filters());
        }

        if ($this->sorts()->getFields()->isNotEmpty()) {
            $this->applySorts($query, $this->sorts());
        }

        return $query;
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
            $this->paginationParams()->perPage($this->defaultPageSize),
            $columns,
            $this->paginationParams()->pageName(),
            $this->paginationParams()->page(1)
        )->withQueryString();
    }

    private function shouldPaginate(): bool
    {
        return $this->defaultPageSize !== null || $this->paginationParams()->filled();
    }

    public function first(array $columns = ['*'])
    {
        return $this->query()->first($columns);
    }

    private function loadIncludes($query, QueryParamBag $includes): void
    {
        $includes->each(function ($params, $relation) use ($query) {
            if ($this->isAllowedToInclude($relation)) {
                $this->loadRelation($query, $relation, $params);
            }
        });
    }

    private function isAllowedToInclude($relation): bool
    {
        return in_array($relation, $this->allowedIncludes);
    }

    private function loadRelation($query, $relation, $params): void
    {
        if ($relationAlias = Arr::get($this->includesMap(), $relation)) {
            $query->with($relationAlias);

            return;
        }

        $methodName = 'include'.Str::studly(str_replace('.', 'With', $relation));
        if (method_exists($this, $methodName)) {
            $this->{$methodName}($query, $params);

            return;
        }

        if (is_null($this->allPossibleAvailableIncludes)) {
            $this->allPossibleAvailableIncludes = $this->getPossibleRelationshipCombinations($this->availableIncludes);
        }

        if (in_array($relation, $this->allPossibleAvailableIncludes)) {
            $query->with($relation);

            return;
        }

        throw new RuntimeException("Trying to include non existing relationship {$relation}");
    }

    private function applyFilters($query, QueryParamBag $filters): void
    {
        $filters->each(function ($params, $scope) use ($query) {
            $this->applyFilter($query, $scope, $params);
        });
    }

    private function applyFilter($query, $scope, $params): void
    {
        if ($filterAlias = Arr::get($this->filtersMap(), $scope)) {
            $query->{$filterAlias}($params);

            return;
        }

        $methodName = 'filterBy'.Str::studly($scope);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($query, $params);

            return;
        }

        if (in_array($scope, $this->availableFilters)) {
            $query->{$scope}($params);

            return;
        }

        throw new RuntimeException("Trying to filter by non existing filter {$scope}");
    }

    private function applySorts($query, Sorts $sorts): void
    {
        $sorts->getFields()->each(function (SortField $sortField) use ($query) {
            $this->applySort($query, $sortField->getField(), $sortField->getDirection(), $sortField->getParams());
        });
    }

    private function applySort($query, string $field, string $direction, array $param): void
    {
        if ($sortAlias = Arr::get($this->sortsMap(), $field)) {
            $query->orderBy($sortAlias, $direction);

            return;
        }

        $methodName = 'sortBy'.Str::studly($field);

        if (method_exists($this, $methodName)) {
            $this->{$methodName}($query, $direction, $param);

            return;
        }

        if (in_array($field, $this->availableSorts)) {
            $query->orderBy($field, $direction);

            return;
        }

        throw new RuntimeException("Trying to sort by non existing field {$field}");
    }
}
