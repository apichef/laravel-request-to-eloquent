<?php

namespace ApiChef\RequestToEloquent\Queries;

class PostListWithPageSizeQuery extends PostListQuery
{
    protected $defaultPageSize = 10;
}
