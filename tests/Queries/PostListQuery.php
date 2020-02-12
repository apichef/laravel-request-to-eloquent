<?php

namespace ApiChef\RequestToEloquent\Queries;

use ApiChef\RequestToEloquent\QueryBuilderAbstract;
use Illuminate\Database\Eloquent\Builder;
use ApiChef\RequestToEloquent\Dummy\Post;

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

    protected $availableIncludes = [
        'comments',
        'comments.user',
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

    protected $availableFilters = [
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

    protected $availableSorts = [
        'published_at',
    ];

    protected function sortsMap(): array
    {
        return [
            'published_day' => 'published_at',
        ];
    }

    public function sortByCommentsCount(Builder $query, $direction)
    {
        $query->withCount('comments')->orderBy('comments_count', $direction);
    }
}
