<?php

namespace ApiChef\RequestToEloquent\Queries;

use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\QueryBuilderAbstract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class PostListQuery extends QueryBuilderAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function init()
    {
        return Post::query();
    }

    // includes

    protected array $availableIncludes = [
        'comments.user',
        'author.posts.tags',
    ];

    protected function includesMap(): array
    {
        return [
            'subjects' => 'tags',
        ];
    }

    public function includeFeedback($query)
    {
        $query->with('comments');
    }

    public function includeFeedbackWithSubmittedBy($query)
    {
        $query->with('comments.user');
    }

    // filters

    protected array $availableFilters = [
        'draft',
    ];

    protected function filtersMap(): array
    {
        return [
            'non_published' => 'draft',
        ];
    }

    public function filterByPublishedBefore($query, $params)
    {
        $query->publishedBefore($params);
    }

    // sorts

    protected array $availableSorts = [
        'published_at',
    ];

    protected function sortsMap(): array
    {
        return [
            'published_day' => 'published_at',
        ];
    }

    public function sortByCommentsCount(Builder $query, $direction, array $params)
    {
        $query->withCount(['comments' => function (Builder $query) use ($params) {
            return $query->when(Arr::has($params, 'between'), function ($q) use ($params) {
                return $q->whereBetween('created_at', Arr::get($params, 'between'));
            });
        }])
        ->orderBy('comments_count', $direction);
    }
}
