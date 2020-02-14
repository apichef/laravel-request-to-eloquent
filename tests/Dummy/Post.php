<?php

declare(strict_types=1);

namespace ApiChef\RequestToEloquent\Dummy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'body',
        'published_at',
    ];

    protected $dates = [
        'published_at',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class)->using(PostTag::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id', 'id');
    }

    public function scopeDraft(Builder $builder)
    {
        return $builder->whereNull('published_at');
    }

    public function scopePublishedBefore(Builder $builder, $date)
    {
        return $builder
            ->where('published_at', '<', $date);
    }
}
