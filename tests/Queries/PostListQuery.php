<?php

namespace LaravelRequestToEloquent\Queries;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use LaravelRequestToEloquent\Dummy\Post;
use LaravelRequestToEloquent\QueryBuilderAbstract;

class PostListQuery extends QueryBuilderAbstract
{
    /**
     * @inheritDoc
     */
    protected function init()
    {
        return Post::query();
    }

    // includes

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

    protected function filtersMap(): array
    {
        return [
            'not_published' => 'draft',
        ];
    }

    public function filterPublishedBefore($query, $params)
    {
        $query->publishedBefore($params);
    }
}
