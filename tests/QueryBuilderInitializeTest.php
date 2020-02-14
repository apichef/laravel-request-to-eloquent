<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Queries\PostListQuery;
use Illuminate\Http\Request;

class QueryBuilderInitializeTest extends TestCase
{
    public function test_it_initialise_the_query()
    {
        $request = Request::create('/posts');
        $query = new PostListQuery($request);

        $this->assertEquals('select * from "posts"', $query->query()->toSql());
    }
}
