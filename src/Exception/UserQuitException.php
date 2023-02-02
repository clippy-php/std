<?php

namespace Clippy\Exception;

use Throwable;

class UserQuitException extends \RuntimeException {

  public function __construct($message = 'User quit application', $code = 0, Throwable $previous = NULL) {
    parent::__construct($message, $code, $previous);
  }

}
