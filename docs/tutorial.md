# Tutorial

Let's begin with a hello-world script. Using basic PHP, you might write `greeter.php` as:

```php
#!/usr/bin/env php
<?php
printf("Hello, %s!", $argv[1]);
```

One would execute this as:

```
$ chmod +x greeter.php
$ ./greeter.php world
Hello, world!
```

However, this as a bit spartan -- there's no help screen, there's no parsing
or valiadtion of `$argv`, there's no console interaction or styling.

The [symfony/console](https://symfony.com/doc/3.4/components/console.html) library provides a bunch of great classes to support those things - `InputInterface $input`, `OutputInterface $output`, `SymfonyStyle $io`, and so on. To use these conventionally, you have to create a set of classes (`Command`s and `Application`s). Clippy provides the same classes in a different packaging - so you can prototype an application with less boilerplate.

With Clippy, a similar `greeter.php` would look like:

```php
#!/usr/bin/env pogo
<?php
#!require clippy/std: 0.2.0
namespace Clippy;

$c = clippy()->register(plugins());
$c['app']->main('yourName', function ($yourName, $io) {
  $io->writeln("Hello, <comment>$yourName</comment>!");
});
```

Again, one would execute:

```
$ ./greeter.php world
Hello, world!
```

If you run it yourself, you should see some color coding of the output to
make it more readable. Additionally, there's an auto-generated help screen:

```
$ ./greeter.php --help
Usage:
  main <yourName>

Arguments:
  yourName

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

There are a few things to observe in the Clippy variant:

* The notations `#!/usr/bin/env pogo` and `#!require <package>: <version>` allow you to load PHP packages in the script without creating a dedicated project for the script. You don't need to separately run `composer install`.
* The script is written within the `Clippy` namespace. This makes it easier to use helper functions.
* The notation `$c = clippy()->register(plugins());` instantiates the system. It autoloads any *plugins* that have been pre-defined by other PHP packages.
* The variable `$c` is a *service-container*. You may access services with the notation `$c['myService']`, and you can define services with `$c['myService'] = function(...){...}`.
* The following services are built-in by default:
    * `$c['input']` (`\Symfony\Component\Console\Input\InputInterface`)
    * `$c['output']` (`\Symfony\Component\Console\\Output\OutputInterface`)
    * `$c['io']` (`\Symfony\Component\Console\\Style\SymfonyStyle`)
    * `$c['app']` (`\Clippy\Application` aka `\Silly\Application` aka `\Symfony\Component\Console\Application`)
    * `$c['container']` (`\Clippy\Container` aka `\Pimple\Container`; the full container)
* The method `main($signature, $callback)`  is shorthand to defining a single-purpose script. 
    * The `$signature` defines CLI options accepted by this command. The signature is used for (a) checking required inputs, (b) parsing inputs, and (c) generating in-line help screens (`myscript --help`).
    * The `$callback` defines the logic of the command. Parameters are matched by name - by looking (first) at the list of CLI inputs and (second) at the list of services in `$c`. In this case, the command accepts one mandatory input, `yourName`.
    * If the script should have multiple subcommands, then use more fine-grained methods, `$c['app']->register($signature, $callback)` and `$c['app']->run()`.

Let's extend the example just a little bit - allowing the user to optionally direct output to an alternative file. CLI commands often accept an option like `-o <file>` or `--out=<file>`.

```bash
$ ./greeter.php world -o /tmp/greeting.txt
$ ./greeter.php --out=/tmp/greeting.txt world
```

Revised example:

```php
#!/usr/bin/env pogo
<?php
#!require clippy/std: 0.2.0
namespace Clippy;
use Symfony\Component\Console\Style\SymfonyStyle;

$c = clippy()->register(plugins());
$c['app']->main('[-o|--out=] yourName', function ($out, $yourName, SymfonyStyle $io) {
  if ($out) {
    file_put_contents($file, "Hello, $yourName!\n");
  }
  else {
    $io->writeln("Hello, <comment>$yourName</comment>!");
  }
});
```

Things to note:

* The command signature changed:
    * Was: `$c['app']->main('yourName', function($yourName...))`
    * Now: `$c['app']->main('[-o|--out=] yourName', function($out, $yourName...))`
* For `$io`, we've added a type-hint to indicate that it is an instance of `SymfonyStyle`.

Let's make another revision to address one more issue.  The use of `file_put_contents()` will overwrite any pre-existing files.  You might want a gentler version of `file_put_contents()`
which prompts the user before overwriting, like in this hypothetical function:

```php
function writeFile($file, $content) {
  if (file_exists($file)) {
    $io->warning("The file $file already exists!");
    if (!$io->confirm("Would you like to overwrite it?")) {
      exit(1);
    }
  }
  file_put_contents($file, $content);
}
```

But that won't work - because `$io` is not in scope.  Moreover, `$io` (and its sibilings `$input`/`$output`) can be used frequently - as the app evolves, you can imagine many functions
needing access to them.  Rather than pass those objects explicitly from every caller to every callee, we can use the container.

In this case, we'll implement `writeFile()` as a *service-method*.  A service-method is like a service -- it is stored in the container (`$c`), and you can inject services (like `$io`) and
use type-hinting (`SymfonyStyle $io`).  However, it is also like a function -- you can pass in runtime data to taste (`$file`, `$content`).

Our final revision:

```php
#!/usr/bin/env pogo
<?php
#!require clippy/std: 0.2.0
namespace Clippy;
use Symfony\Component\Console\Style\SymfonyStyle;

$c = clippy()->register(plugins());
$c['writeFile()'] = function($file, $content, SymfonyStyle $io) {
  if (file_exists($file)) {
    $io->warning("The file $file already exists!");
    if (!$io->confirm("Would you like to overwrite it?")) {
      exit(1);
    }
  }
  file_put_contents($file, $content);
};
$c['app']->main('[-o|--out=] yourName', function ($out, $yourName, SymfonyStyle $io, $writeFile) {
  if ($out) {
    $writeFile($out, "Hello, $yourName!\n");
  }
  else {
    $io->writeln("Hello, <comment>$yourName</comment>!");
  }
});
```
