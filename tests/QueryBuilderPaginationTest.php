<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\Queries\PostListQuery;
use ApiChef\RequestToEloquent\Queries\PostListWithPageSizeQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\Request;

class QueryBuilderPaginationTest extends TestCase
{
    public function test_can_paginate()
    {
        factory(Post::class, 20)->create();
        $request = Request::create('/posts?page[number]=2&page[size]=4');

        /** @var Paginator $result */
        $result = (new PostListQuery($request))->get();

        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertEquals(2, $result->currentPage());
        $this->assertEquals(4, $result->perPage());
    }

    public function test_can_paginate_with_total_count()
    {
        factory(Post::class, 20)->create();
        $request = Request::create('/posts?page[number]=2&page[size]=4');

        $result = (new PostListQuery($request))->paginateWithTotal();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->currentPage());
        $this->assertEquals(5, $result->lastPage());
        $this->assertEquals(4, $result->perPage());
    }

    public function test_it_uses_default_psge_size()
    {
        factory(Post::class, 20)->create();
        $request = Request::create('/posts');

        /** @var Paginator $result */
        $result = (new PostListWithPageSizeQuery($request))->get();

        $this->assertCount(10, $result->items());
    }
}
