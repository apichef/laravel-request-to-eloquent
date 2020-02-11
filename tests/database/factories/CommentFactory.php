<?php

use Faker\Generator as Faker;
use LaravelRequestToEloquent\Dummy\Comment;
use LaravelRequestToEloquent\Dummy\Post;
use LaravelRequestToEloquent\Dummy\User;

$factory->define(Comment::class, function (Faker $faker) {
    return [
        'body' => $faker->paragraphs(1, true),
        'created_at' => $faker->dateTimeBetween('-2 months'),
        'user_id' => function () {
            return factory(User::class)->create()->id;
        },
        'post_id' => function () {
            return factory(Post::class)->create()->id;
        },
    ];
});
