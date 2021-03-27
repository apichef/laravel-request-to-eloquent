<?php

namespace ApiChef\RequestToEloquent\Queries;

class PostListWithPageSizeQuery extends PostListQuery
{
    protected ?int $defaultPageSize = 10;
}
