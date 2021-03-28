<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestQueryHelper\Fields;
use ApiChef\RequestQueryHelper\PaginationParams;
use ApiChef\RequestQueryHelper\QueryParamBag;
use ApiChef\RequestQueryHelper\Sorts;

trait InteractWithQueryString
{
    private ?QueryStringParams $queryStringParams = null;

    private function getQueryStringParams(): QueryStringParams
    {
        if ($this->queryStringParams === null) {
            $this->queryStringParams = resolve(QueryStringParams::class);
        }

        return $this->queryStringParams;
    }

    private function paginationParams(): PaginationParams
    {
        return $this->getQueryStringParams()->paginationParams;
    }

    private function includes(): QueryParamBag
    {
        return $this->getQueryStringParams()->includes;
    }

    private function filters(): QueryParamBag
    {
        return $this->getQueryStringParams()->filters;
    }

    private function sorts(): Sorts
    {
        return $this->getQueryStringParams()->sorts;
    }

    private function fields(): Fields
    {
        return $this->getQueryStringParams()->fields;
    }
}
