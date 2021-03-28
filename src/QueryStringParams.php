<?php

declare(strict_types=1);

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestQueryHelper\Fields;
use ApiChef\RequestQueryHelper\PaginationParams;
use ApiChef\RequestQueryHelper\QueryParamBag;
use ApiChef\RequestQueryHelper\Sorts;
use Illuminate\Http\Request;

class QueryStringParams
{
    public Fields $fields;
    public QueryParamBag $includes;
    public QueryParamBag $filters;
    public Sorts $sorts;
    public PaginationParams $paginationParams;

    public function __construct(Request $request)
    {
        $this->fields = $request->fields();
        $this->includes = $request->includes();
        $this->filters = $request->filters();
        $this->sorts = $request->sorts();
        $this->paginationParams = $request->paginationParams();
    }
}
