# Clippy

Clippy is a [silly](https://github.com/mnapoli/silly/) CLI framework that's
a little bit too eager with how much mistaken advice it gives, but it's
trying to be friendly.

```php
<?php
#!require totten/silly: 0.1.0
$c = clippy()->register(plugins());

$c['app']->main('name', function ($name, $io) {
  $io->writeln("Hello, <comment>$name</comment>!");
});
```