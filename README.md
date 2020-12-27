# laravel-request-to-eloquent

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-ci]][link-ci]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

# Easily translate request query string to Eloquent query.

## Install

Via Composer

``` bash
$ composer require apichef/laravel-request-to-eloquent
```

We use `apichef/laravel-request-query-helper` package as a dependency. You can publish it's the config file with:

```bash
$ php artisan vendor:publish --provider="ApiChef\RequestQueryHelper\RequestQueryHelperServiceProvider"
```

## Basic usage
Model class:
```php
namespace App;

use App\Comment;
use App\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $dates = [
        'published_at',
    ];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function scopeDraft(Builder $builder)
    {
        return $builder->whereNull('published_at');
    }
}
```
Request query class:
```php
namespace App\Queries;

use App\Post;
use ApiChef\RequestToEloquent\QueryBuilderAbstract;
use Illuminate\Http\Request;

class PostListQuery extends QueryBuilderAbstract
{
    protected function init(Request $request)
    {
        return Post::query();
    }

    protected $availableIncludes = [
        'comments',
        'tags',
    ];

    protected $availableFilters = [
        'draft',
    ];

    protected $availableSorts = [
        'published_at',
    ];
}
```
Controller:
```php
namespace App\Http\Controllers;

use App\User;
use App\Queries\PostListQuery;

class DashboardController extends Controller
{
    public function index(PostListQuery $postListQuery)
    {
        return $postListQuery
            ->parseAllowedIncludes([
                'comments',
                'tags',
            ])
            ->get()
            ->toArray();
    }
}
```
Http request:
```shell script
GET /posts?include=comments,tags&filter[draft]&sort=-published_at
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email milroy@outlook.com instead of using the issue tracker.

## Credits

- [Milroy E. Fraser][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/apichef/laravel-request-to-eloquent.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-ci]: https://github.com/apichef/laravel-request-to-eloquent/workflows/CI/badge.svg
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/apichef/laravel-request-to-eloquent.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/apichef/laravel-request-to-eloquent.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/apichef/laravel-request-to-eloquent.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/apichef/laravel-request-to-eloquent
[link-ci]: https://github.com/apichef/laravel-request-to-eloquent/actions
[link-scrutinizer]: https://scrutinizer-ci.com/g/apichef/laravel-request-to-eloquent/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/apichef/laravel-request-to-eloquent
[link-downloads]: https://packagist.org/packages/apichef/laravel-request-to-eloquent
[link-author]: https://github.com/milroyfraser
[link-contributors]: ../../contributors
