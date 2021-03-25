<?php

namespace ApiChef\RequestToEloquent\Queries;

use ApiChef\RequestToEloquent\Dummy\Post;
use ApiChef\RequestToEloquent\QueryBuilderAbstract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PostListQuery extends QueryBuilderAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function init(Request $request)
    {
        return Post::query();
    }

    // includes

    protected $availableIncludes = [
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

    public function sortByCommentsCount(Builder $query, $direction, string $dateRange = null)
    {
        $query->withCount(['comments' => function (Builder $query) use ($dateRange) {
            return $query->when($dateRange !== null, function ($q) use ($dateRange) {
                return $q->whereBetween('created_at', explode('|', $dateRange));
            });
        }])
        ->orderBy('comments_count', $direction);
    }
}
