## Technical Synopsis

Clippy is built on:

* [Symfony Console](https://symfony.com/doc/current/components/console.html): Defines console *commands* which accept *inputs*, generate *outputs*, and have builtin *help* screens.
* [silly](https://github.com/mnapoli/silly/): Extends Symfony Console with pithy command-signatures and auto-injection.
* [php-di/invoker](https://github.com/PHP-DI/Invoker): Defines *auto-injection* - when executing a function, it can automatically pass in parameters from the container.
* [pimple](https://pimple.symfony.com/): A service container.

Clippy also adds/extends/changes a few things:

* Silly allows you to inject services as *function-parameters* -- e.g. `function($input) {...}`. But this only works with `Command` objects. Clippy extends this to *all services and factories*.
* Silly allows you to inject common CLI services `InputInterface $input`, `OutputInterface $output`, `SymfonyStyle $io`. But these are only available to `Command` functions. Clippy extends them to be available to *all services and factories*.
* Silly works with any PSR-11 container. Clippy specifically uses `\Clippy\Container` (https://github.com/clippy-php/container).
* Clippy's `Container` is closely modeled on Pimple's `Container`, but it differs in that it uses *auto-wiring of services as function-parameters* to allow progressive type-hinting.
* Clippy's `Container` supports *service-methods*. These are functions which support *both* service-injection and runtime data-passing. In the following example, the first parameter to `getPassword` is passed at runtime (`$domain` e.g. `example.com`); the second parameter is injected automatically.
   ```php
   $c['getPassword()'] = function ($domain, SymfonyStyle $io) {
     if (getenv('PASSWORD')) return getenv('PASSWORD');
     return $io->askHidden("What is the password for <comment>$domain</comment>?");
   }
   $c['app']->main('', function($getPassword) {
     $pass = $getPassword('example.com');
   });
   ```
* Clippy defines a *plugin* construct. Plugins may register new services. A plugin is simply a package which declares itself in `$GLOBALS['plugins']`. Plugins are autoloaded via convention `$c = clippy()->register(plugins())`, but they may (per perference) be loaded incrementally or piecemeal.
  See [docs/plugins.md](/docs/plugins.md).

