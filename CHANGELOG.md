# Changelog

## v0.4

* __PHP__: Raise minimum to v7.2.
* __Cmdr__: Add support for escaping array-data using multi-parameter-mode (array-mode). Ex: `ls -l {{FILES|@s}}`
* __Cmdr__: Omit extraneous quotation marks from some simple strings
* __Cmdr__: Improve support for `passthru()` when handling interactive/tty-dependent apps.
* __Cmdr__: Add some basic tests
* __Cmdr__: Move `CmdrProcessException` into `Clippy\Exception\` namespace. (Leave alias for old name.)
* __Taskr__: Add helper for interactively running tasks, with `--step` and `--dry-run` support.
* __AutoCleanup__: Add helper to for the 'on-destruct' cleanup pattern.
