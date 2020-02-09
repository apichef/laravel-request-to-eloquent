<?php

declare(strict_types=1);

namespace LaravelRequestToEloquent\Dummy;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $fillable = [
        'name',
    ];

    public function posts()
    {
        return $this->belongsToMany(Post::class)->using(PostTag::class);
    }
}
