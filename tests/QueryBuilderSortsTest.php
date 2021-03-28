<?php

namespace ApiChef\RequestToEloquent;

use ApiChef\RequestToEloquent\Dummy\Comment;
use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\Queries\PostListQuery;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class QueryBuilderSortsTest extends TestCase
{
    public function test_can_sort()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        $request = Request::create('/posts?sort=published_at');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-published_at');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post3->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }

    public function test_can_not_sort_by_non_existing_field()
    {
        $request = Request::create('/posts?sort=colour');
        $this->instance(Request::class, $request);
        $this->expectException(\RuntimeException::class);

        (new PostListQuery())
            ->get();
    }

    public function test_can_sort_by_alias()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        $request = Request::create('/posts?sort=published_day');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-published_day');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
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
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post3->id);

        $request = Request::create('/posts?sort=-comments_count');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post3->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }

    public function test_can_sort_by_custom_method_with_aditional_params()
    {
        Carbon::setTestNow('2020-02-02');

        $post1 = factory(Post::class)->create(['published_at' => now()->addHours(2)]);
        $post2 = factory(Post::class)->create(['published_at' => now()]);
        $post3 = factory(Post::class)->create(['published_at' => now()->addHours(4)]);

        factory(Comment::class, 4)->create(['post_id' => $post1->id, 'created_at' => Carbon::parse('2020-02-03')]);
        factory(Comment::class, 2)->create(['post_id' => $post3->id, 'created_at' => Carbon::parse('2020-02-03')]);
        $outOfDateRange = Carbon::parse('2020-02-10');
        factory(Comment::class, 6)->create(['post_id' => $post2->id, 'created_at' => $outOfDateRange]);


        $request = Request::create('/posts?sort=comments_count:between(2020-02-02|2020-02-04)');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post2->id);
        $this->assertEquals($result->last()->id, $post1->id);

        $request = Request::create('/posts?sort=-comments_count:between(2020-02-02|2020-02-04)');
        $this->instance(Request::class, $request);

        /** @var Collection $post */
        $result = (new PostListQuery())
            ->get();

        $this->assertEquals($result->first()->id, $post1->id);
        $this->assertEquals($result->last()->id, $post2->id);
    }
}
