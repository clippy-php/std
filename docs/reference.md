# Clippy Reference

Unless otherwise indicated, all functions and classes described here are in the `\Clippy` namespace.

## Utility Functions

The *utility functions* are small helpers.  They should generally be *true* functions, in they sense that they are idempotent, depend only upon their input, and have no obscure side-effects.

* `assertThat(mixed $bool, $msg = NULL)`: Ensure that `$bool` is `TRUE`-ish; otherwise, throw an exception
* `assertVal(mixed $val, $msg = NULL)`: Enuser that `$val` has a value (i.e. not-`NULL` and not-empty-string); otherwise, throw an exception
* `index(string|string[] $keys, mixed[] $records) : array`: Build an index over the `$records` which is keyed by `$keys`.
* `joinPath(...$parts)`: Concatentate the parts of a local file path
* `joinUrl(...$parts)`: Concatenate the parts of a URL
* `toJSON(mixed $mixed)`: Alias for `json_encode()`. Generates prettier output by default.
* `fromJSON(mixed $mixed)`: Alias for `json_decode()`. Generates array-trees by default. Accepts a few object types (e.g. HTTP ResponseInterface) which can be converted to string.

## Framework Functions

These are more opinionated functions. They are primarily entry-points for initializing the framework.

* `clippy() : \Clippy\Container`: Instantiates a new container with the basic services.
* `plugins(string[] $names = NULL) : callback[]`: Returns a list of available plugins. Optionally filter by a whitelist of names.

## Basic Services

The basic services are always registered in the container.

* `input` (Symfony [InputInterface](https://github.com/symfony/symfony/blob/3.4/src/Symfony/Component/Console/Input/InputInterface.php)): The active console input.
* `output` (Symfony [OutputInterface](https://github.com/symfony/symfony/blob/3.4/src/Symfony/Component/Console/Output/OutputInterface.php)): The active console output.
* `io` (Symfony [SymfonyStyle](https://github.com/symfony/symfony/blob/3.4/src/Symfony/Component/Console/Style/SymfonyStyle.php)): A richer input/output helper.
* `container` (Clippy [Container](https://github.com/clippy-php/container)): A self-reference to the service container.

## Plugin: `app` (`ConsoleApp.php`)

An *application* specifies the CLI interface. It is a repository for defining/locating/invoking CLI `Command`s.

Example: Register and run a simple, single-operation command:

```php
$c['app']->main('[--foo-option]', function($fooOption){...});
```

Example: Register multiple subcommands - and run whichever the user wants:

```php
$c['app']->command('foo [--foo-option]', function($fooOption){ ... });
$c['app']->command('bar [--bar-option]', function($barOption){ ... });
$c['app']->run();
```

See: https://github.com/mnapoli/silly/

## Plugin: `cmdr` (`Cmdr.php`)

A helper for constructing and executing shell commands. It provides helpers to escape variables in a command line expression.

Examples:

```php
// Run a command, assert success, and return the output.
$output = $cmdr->run('ls {{FILE|s}}', ['FILE' => '/home/foo bar/whiz&bang']);

// As above, but provide an array with multiple parameters
$output = $cmdr->run('ls -l {{FILES|@s}}', ['FILES' => ['1.txt', '2.txt', '3.txt']]);

// Run a command, pass output through to console, and assert success.
$cmdr->passthru('ls {{FILE|s}}', ['FILE' => '/home/foo bar/whiz&bang']);

// Prepare a Symfony "Process" object based on this command.
// It's up to you to handle execution, error-checking, etc.
$process = $cmdr->process('echo {{MESSAGE|js}} | json_pp', ['MESSAGE' => 'The stuff.']);

// Evaluate the command string
$fullCmdStr = $cmdr->escape('ls {{FILE|s}}', ['FILE' => '/home/foo bar/whiz&bang']);
```

## Plugin: `cred` (`Credentials.php`)

A helper for loading credentials in CLI. Generally, credentials come from one of the following (in order of decreasing precedence):

* An environment variable (e.g. `PRIVATE_TOKEN`)
* A configuration file (e.g. `~/.config/clippy-cred/localhost.json`)
* An interactive prompt (e.g. `Enter PRIVATE_TOKEN for localhost:`)

Example:

```php
$c['openIssue()'] = function(Credentials $cred) {
  $token = $cred->get('PRIVATE_TOKEN', 'gitlab.example.com');
  ...
};
```

## Plugin: `guzzle` (`Guzzle.php`)

(WIP)
