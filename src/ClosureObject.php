<?php

namespace Clippy;

/**
 * Create an object where all methods are handled by the same closure.
 *
 * $obj = new ClosureObject(function($self, $method, ...$args){...});
 * $obj->method(...$args);
 */
class ClosureObject extends \stdClass {

  private $callable;

  public function __construct($callable) {
    $this->callable = $callable;
  }

  public function __call($name, $args) {
    array_unshift($args, $this, $name);
    return call_user_func_array($this->callable, $args);
  }

}
