<?php
namespace Clippy;

use PHPUnit\Framework\TestCase;

class ClosureObjectTest extends TestCase {

  public function testClosureObject() {
    $obj = new ClosureObject(function($self, $method, ...$args) use (&$tracker) {
      $self->log[] = sprintf('called %s with %d arg(s)', $method, count($args));
      static $i = 0;
      return ++$i;
    });

    $this->assertEquals(1, $obj->foo());
    $this->assertEquals(2, $obj->bar(100));
    $this->assertEquals(3, $obj->whiz(100, 200));

    $expectLog = [
      'called foo with 0 arg(s)',
      'called bar with 1 arg(s)',
      'called whiz with 2 arg(s)',
    ];

    $this->assertEquals($expectLog, $obj->log);
  }

}
