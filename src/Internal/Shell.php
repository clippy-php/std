<?php

namespace Clippy\Internal;

class Shell {

  /**
   * Escape a value for use as a shell argument.
   *
   * This is basically the same as `escapeshellarg()`, but quotation marks can be skipped for
   * some simple strings.
   *
   * @param string $value
   * @return string
   */
  public static function lazyEscape(string $value): string {
    if (preg_match('/^[a-zA-Z0-9_\.\-\/=]*$/', $value)) {
      return $value;
    }
    else {
      return escapeshellarg($value);
    }
  }

}
