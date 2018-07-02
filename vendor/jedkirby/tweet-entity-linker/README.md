Tweet Entity Linker
-------
[![Author](https://img.shields.io/badge/author-@jedkirby-blue.svg?style=flat-square)](https://twitter.com/jedkirby)
[![Build Status](https://img.shields.io/travis/jedkirby/tweet-entity-linker/master.svg?style=flat-square)](https://travis-ci.org/jedkirby/tweet-entity-linker)
[![Test Coverage](https://img.shields.io/coveralls/jedkirby/tweet-entity-linker/master.svg?style=flat-square)](https://coveralls.io/github/jedkirby/tweet-entity-linker)
[![StyleCI](https://styleci.io/repos/78195612/shield?branch=master)](https://styleci.io/repos/78195612)
[![Packagist](https://img.shields.io/packagist/v/jedkirby/tweet-entity-linker.svg?style=flat-square)](https://packagist.org/packages/jedkirby/tweet-entity-linker)
[![Packagist](https://img.shields.io/packagist/l/jedkirby/tweet-entity-linker.svg?style=flat-square)](https://github.com/jedkirby/tweet-entity-linker/blob/master/LICENSE)

Tweet Entity Linker is a very simple package and requires minimal setup. It's designed to simply _only_ convert a tweets text, along with it's entities, to a HTML rich string enabling linking of URLs, User Mentions and Hashtags.

Installation
-------

This package can be installed via [Composer]:

``` bash
$ composer require jedkirby/tweet-entity-linker
```

It requires **PHP >= 5.6.4**.

Usage
-------

The following guide assumes that you've imported the class `Jedkirby\TweetEntityLinker\Tweet` into your namespace.

The `Tweet` class requires that you pass it parameters so it's able to create the linkified text. Generally these parameters will be the responses from Twitter API endpoints, like [statuses/show/:id](https://dev.twitter.com/rest/reference/get/statuses/show/id), however, it's not required.

The following pseudo code should help explain what's needed when using the response from the API (Please see the [example response](https://dev.twitter.com/rest/reference/get/statuses/show/id#example-response)):

``` php
$request = Api::get('https://api.twitter.com/1.1/statuses/show/123456');

$tweet = Tweet::make(
  $request['text'],
  $request['entities']['urls'],
  $request['entities']['user_mentions'],
  $request['entities']['hashtags']
);
```

Now the `Tweet` class has been populated with the parameters it needs, you can call the `linkify()` method to return the text with URLs, User Mentions and Hashtags converted to their HTML entities:

``` php
$text = $tweet->linkify();
```

### Manually Creating Parameters

You're able to create the parametes manually, however, they require some specific properties in order for the `linkify()` method to function correctly, these are as follows:

#### Parameter 1: Text

This field is _always_ required, and if containing either a URL, User Mention or Hashtag, the corresponding parameter array's should be populated. The following example assumes we have all of those:

``` php
$text = 'This notifies @jedkirby, with the hashtag #Awesome, and the URL https://t.co/Ed4omjYz.';
```

#### Parameter 2: URLs

The URLs parameter is an array of array's, of which it must contain the `url` and `display_url` fields:

``` php
$urls = [
  [
    'url'         => 'https://t.co/Ed4omjYz',
    'display_url' => 'https://jedkirby.com'
  ]
];
```

#### Parameter 3: User Mentions

The User Mentions parameter is an array of array's, of which it must contain only a `screen_name` field:

``` php
$mentions = [
  [
    'screen_name' => 'jedkirby'
  ]
];
```

#### Parameter 4: Hashtags

The Hashtags parameter is an array of array's, of which it must contain only a `text` field:

``` php
$hashtags = [
  [
    'text' => 'Awesome'
  ]
];
```

#### Result

When putting all the above parameters together, you'd get the following:

``` php
$tweet = new Tweet(
  $text,
  $urls,
  $mentions,
  $hashtags
);

var_dump($tweet->linkify());
```

With the response being:

``` none
string(262) "This notifies @<a href="https://twitter.com/jedkirby" target="_blank">jedkirby</a>, with the hashtag #<a href="https://twitter.com/hashtag/Awesome" target="_blank">Awesome</a>, and the URL <a href="https://t.co/Ed4omjYz" target="_blank">https://jedkirby.com</a>."
```

Testing
-------

Unit tests can be run inside the package:

``` bash
$ ./vendor/bin/phpunit
```

Contributing
-------

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

License
-------

**jedkirby/tweet-entity-linker** is licensed under the MIT license.  See the [LICENSE](LICENSE) file for more details.

[Composer]: https://getcomposer.org/
