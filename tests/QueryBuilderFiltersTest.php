<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\Queries\PostListQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QueryBuilderFiltersTest extends TestCase
{
    public function test_can_filter()
    {
        factory(Post::class, 3)->create(['published_at' => null]);
        factory(Post::class, 2)->create(['published_at' => now()]);

        $request = Request::create('/posts?filter[draft]');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())->get();

        $this->assertCount(3, $result);
    }

    public function test_can_not_filter_by_non_existing_filter()
    {
        $request = Request::create('/posts?filter[colour]');
        $this->instance(Request::class, $request);
        $this->expectException(\RuntimeException::class);

        (new PostListQuery())->get();
    }

    public function test_can_filter_by_alias()
    {
        factory(Post::class, 4)->create(['published_at' => null]);
        factory(Post::class, 1)->create(['published_at' => now()]);

        $request = Request::create('/posts?filter[non_published]');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())->get();

        $this->assertCount(4, $result);
    }

    public function test_can_filter_by_custom_method()
    {
        Carbon::setTestNow('2020-02-02');

        factory(Post::class, 2)->create(['published_at' => Carbon::parse('2020-01-02')]);
        factory(Post::class, 3)->create(['published_at' => Carbon::parse('2020-02-14')]);

        $request = Request::create('/posts?filter[published_before]=2020-02-02');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())->get();

        $this->assertCount(2, $result);
    }
}
