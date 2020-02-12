<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Dummy\Comment;
use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\Dummy\Tag;
use ApiChef\RequestToEloquent\Queries\PostListQuery;
use Illuminate\Http\Request;

class QueryBuilderIncludesTest extends TestCase
{
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

    public function test_can_not_include_non_existing_relationship()
    {
        $request = Request::create('/posts?include=colour');
        $this->expectException(\RuntimeException::class);

        (new PostListQuery($request))
            ->parseAllowedIncludes(['colour'])
            ->get();
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
}
