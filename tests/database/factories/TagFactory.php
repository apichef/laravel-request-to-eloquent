<?php

use Faker\Generator as Faker;
use LaravelRequestToEloquent\Dummy\Tag;

$factory->define(Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
