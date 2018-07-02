Changelog
-------

Here's a log of all the changes that have happened to this package.

Latest
------

Nothing.

1.2.4 - 2017-01-10
-------

#### Added
- Contribution guide
- Complete changelog

1.2.3 - 2017-01-10
------

#### Fixed
- Updating the readme to use the `Tweet::make` as apposed to the private `new Tweet` ([@Brunty](https://twitter.com/Brunty))

1.2.1 - 2017-01-10
------

#### Fixed
- Updating the URL for the StyleCI badge in the homepage to target `master`

1.2.0 - 2017-01-09
------

#### Added
- Entity searches now include keys for specific entities, i.e. Hashtags: # & User Mentions: @
- Method to return a regular expression string used for the searching of an entity

#### Fixed
- Changed the use of `str_replace` to use `preg_replace` to enable limiting the replacements

1.1.3 - 2017-01-08
------

#### Added
- Added the `.styleci.yml` configuration

1.1.2 - 2017-01-07
------

#### Fixed
- Updated the `.php_cs` configuration to use composer's autoloading

1.1.1 - 2017-01-07
------

#### Added
- `jedkirby/php-cs` package and configuration

#### Fixed
- Code styling for all PHP files within the `tests` and `src` directories

1.1.0 - 2017-01-06
------

#### Added
- Entity validation & required properties
- Readme content

#### Remove
- Support for `stdClass`

1.0.0 - 2017-01-06
------

First stable release of `jedkirby/twitter-entity-linker`
