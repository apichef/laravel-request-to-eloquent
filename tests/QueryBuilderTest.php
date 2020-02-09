<?php

namespace LaravelRequestToEloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use LaravelRequestToEloquent\Dummy\Comment;
use LaravelRequestToEloquent\Dummy\Post;
use LaravelRequestToEloquent\Dummy\Tag;
use LaravelRequestToEloquent\Queries\PostListQuery;

class QueryBuilderTest extends TestCase
{
    public function test_it_initialise_the_query()
    {
        $request = Request::create('/posts');
        $query = new PostListQuery($request);

        $this->assertEquals('select * from "posts"', $query->query()->toSql());
    }

    public function test_can_include()
    {
        factory(Comment::class)->create();

        $request = Request::create('/posts?include=comments');

        /** @var Post $post */
        $result = (new PostListQuery($request))
            ->parseAllowedIncludes(['comments'])
            ->first();

        $this->assertTrue($result->relationLoaded('comments'));
    }

    public function test_it_does_not_load_relations_when_not_allowed()
    {
        factory(Comment::class)->create();

        $request = Request::create('/posts?include=comments');

        /** @var Post $post */
        $result = (new PostListQuery($request))
            ->first();

        $this->assertFalse($result->relationLoaded('comments'));
    }

    public function test_can_include_by_alias_method()
    {
        /** @var Post $post */
        $post = factory(Post::class)->create();
        $post->tags()->attach(factory(Tag::class)->create()->id);

        $request = Request::create('/posts?include=subjects');

        /** @var Post $result */
        $result = (new PostListQuery($request))
            ->parseAllowedIncludes(['subjects'])
            ->first();

        $this->assertTrue($result->relationLoaded('tags'));
    }

    public function test_can_include_nested_relations()
    {
        factory(Comment::class)->create();

        $request = Request::create('/posts?include=comments.user');

        /** @var Post $post */
        $result = (new PostListQuery($request))
            ->parseAllowedIncludes(['comments.user'])
            ->first();

        $this->assertTrue($result->relationLoaded('comments'));
        $this->assertTrue($result->comments->first()->relationLoaded('user'));
    }

    public function test_can_include_nested_relations_by_alias_method()
    {
        factory(Comment::class)->create();

        $request = Request::create('/posts?include=feedback.submitted_by');

        /** @var Post $post */
        $result = (new PostListQuery($request))
            ->parseAllowedIncludes(['feedback.submitted_by'])
            ->first();

        $this->assertTrue($result->relationLoaded('comments'));
        $this->assertTrue($result->comments->first()->relationLoaded('user'));
    }

    public function test_parsed_nested_includes_allows_to_include_only_parent()
    {
        factory(Comment::class)->create();

        $request = Request::create('/posts?include=feedback');

        /** @var Post $post */
        $result = (new PostListQuery($request))
            ->parseAllowedIncludes(['feedback.submitted_by'])
            ->first();

        $this->assertTrue($result->relationLoaded('comments'));
    }

    public function test_can_filter()
    {
        factory(Post::class, 3)->create(['published_at' => null]);
        factory(Post::class, 2)->create(['published_at' => now()]);

        $request = Request::create("/posts?filter[draft]");

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertCount(3, $result);
    }

    public function test_can_filter_by_alias()
    {
        factory(Post::class, 4)->create(['published_at' => null]);
        factory(Post::class, 1)->create(['published_at' => now()]);

        $request = Request::create("/posts?filter[not_published]");

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertCount(4, $result);
    }

    public function test_can_filter_by_custom_method()
    {
        Carbon::setTestNow('2020-02-02');

        factory(Post::class, 2)->create(['published_at' => Carbon::parse('2020-01-02')]);
        factory(Post::class, 3)->create(['published_at' => Carbon::parse('2020-02-14')]);

        $request = Request::create("/posts?filter[published_before]=2020-02-02");

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertCount(2, $result);
    }
}
