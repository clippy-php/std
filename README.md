# Clippy (Standard Edition)

Clippy is a CLI framework for *scripting*  in PHP -- i.e. creating short, task-specific, standalone commands. It is heavily based on [mnapoli/silly](https://github.com/mnapoli/silly/).

*Scripting* is a different domain than, say, *full business applications*.

* In some ways, scripting is more modest: a full business application may have a wide variety of entities, screens, commands, and authors.  Dependencies and conventions among these various components must be reconciled.  By contrast, a script is generally focused on smaller tasks and has wider latitude to mix and match libraries and conventions.
* In other ways, scripting is more stringent: the naming/structure/metadata should be quite thin to allow quick improvisation, and it should be easy+safely to frequently call out to other CLI commands.  By contrast, a full business app has more value built-in -- so it needs more structure to differentiate its internal components, and it doesn't need to call-out as frequently.

Note: To simplify the workflows for dependency management, the examples use [pogo](http://github.com/totten/pogo).  `pogo` should be installed in the `PATH`.
Alternatively, you can rework the examples - instead, create a new `composer` package for each script and run `composer require <package>:<version>` has needed.

## Example

<!-- It's nice to have an example which uses an option and an argument... -->
```php
#!/usr/bin/env pogo
<?php
#!require clippy/std: ~0.2.0
namespace Clippy;

$c = clippy()->register(plugins());
$c['app']->main('yourName [--lang=]', function ($yourName, $lang, $io) {
  $messages = [
    'de' => "Hallo, <comment>$yourName</comment>!",
    'en' => "Hello, <comment>$yourName</comment>!",
    'es' => "!Hola <comment>$yourName</comment>!",
    'fr' => "Salut, <comment>$yourName</comment>!",
  ];
  $io->writeln($messages[$lang] ?? $messages['en']);
});
```

Which one would execute as

```
$ ./greeter.php world
Hello, world!
$ ./greeter.php Alice --lang=fr
Salut, Alice!
```

For more discussion and improvement of the example, see [docs/tutorial.md](/docs/tutorial.md).

## Documenation:

* [Technical synopsis](/docs/synopsis.md) - Summary of key libraries, services, structure
* [Tutorial](/docs/tutorial.md) - Walk through a few examples
* [Plugins](/docs/plugins.md) - Plugin mechanism
* [Reference](/docs/reference.md) - List of functions and services in the standard edition
