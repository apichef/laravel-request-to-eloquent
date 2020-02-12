<?php

use Faker\Generator as Faker;
use ApiChef\RequestToEloquent\Dummy\Tag;

$factory->define(Tag::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
    ];
});
