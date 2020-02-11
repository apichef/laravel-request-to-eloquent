<?php

namespace LaravelRequestToEloquent;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use LaravelRequestToEloquent\Dummy\Comment;
use LaravelRequestToEloquent\Dummy\Post;
use LaravelRequestToEloquent\Queries\PostListQuery;

class QueryBuilderSortsTest extends TestCase
{
    public function test_can_sort()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        $request = Request::create('/posts?sort=published_at');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-published_at');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post3->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }

    public function test_can_not_sort_by_non_existing_field()
    {
        $request = Request::create('/posts?sort=colour');
        $this->expectException(\RuntimeException::class);

        (new PostListQuery($request))
            ->get();
    }

    public function test_can_sort_by_alias()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        $request = Request::create('/posts?sort=published_day');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-published_day');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post3->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }

    public function test_can_sort_by_custom_method()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        factory(Comment::class, 2)->create(['post_id' => $post1->id]);
        factory(Comment::class, 4)->create(['post_id' => $post3->id]);

        $request = Request::create('/posts?sort=comments_count');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-comments_count');

        /** @var Collection $post */
        $result = (new PostListQuery($request))
            ->get();

        $this->assertEquals($result->first()->id, $post3->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }
}
