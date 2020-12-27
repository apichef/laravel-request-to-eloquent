<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\Queries\PostListQuery;
use ApiChef\RequestToEloquent\Queries\PostListWithPageSizeQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class QueryBuilderPaginationTest extends TestCase
{
    public function test_can_paginate()
    {
        factory(Post::class, 6)->create();

        $request = Request::create('/posts?page[number]=1&page[size]=4');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_it_picks_up_number_and_size()
    {
        factory(Post::class, 20)->create();

        $request = Request::create('/posts?page[number]=2&page[size]=4');

        /** @var LengthAwarePaginator $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals(2, $result->currentPage());
        $this->assertEquals(5, $result->lastPage());
        $this->assertEquals(4, $result->perPage());
    }

    public function test_it_uses_default_psge_size()
    {
        factory(Post::class, 20)->create();

        $request = Request::create('/posts');

        /** @var LengthAwarePaginator $post */
        $result = (new PostListWithPageSizeQuery($request))
            ->get();

        $this->assertCount(10, $result->items());
    }
}
