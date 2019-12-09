# Plugins

Clippy defines a *plugin* construct.  Plugins may register new services in the [container](https://github.com/clippy-php/container).  A plugin is simply a package which declares
itself in `$GLOBALS['plugins']`.  Plugins are autoloaded via convention but they may (per perference) be loaded incrementally or piecemeal.

## Usage

It's expected that typical usage would like:

```php
#!require foo/barplugin: 1.2.3
$c = clippy()->register(plugins());
```

By default, the `plugins()` function will a list of all plugins defined in `$GLOBALS['plugins']`.

If you need to pick and choose plugins (e.g.  to simulate different configurations in a testing environment), then simply pass in the list of desired plugins:

```php
#!require foo/barplugin: 1.2.3
$c = clippy()->register(plugins(['bar', ...]));
```

## Definition

A plugin is a `composer` package with a PHP file (e.g. `plugin.php`) which defines some services. In
brief, the package needs a file which says:

```php
$GLOBALS['plugins']['myplugin'] = function($container) {
  $container['myservice'] = function() {
    return new MyService();
  };
};
```

For a fuller consideration, you will need to create standard boilerplate:

```
mkdir myplugin
git init
composer init
vi composer.json
```

In the `composer.json`, be sure to depend on `clippy/std` and
define an `autoload` section:

```json
{
    "name": "me/myplugin",
    "require": {
        "clippy/std": ">=0.2.0",
    },
    "autoload": {
        "files": ["plugin.php"],
        "psr-4": {"Clippy\\": "src/"}
    }
}
```

Use the `plugin.php` to define services, and use `src/**.php` to define new classes.

Then post the code to Github/Packagist/etc and tag a release.
