<?php
namespace Clippy;

use PHPUnit\Framework\TestCase;

class AutoCleanupTest extends TestCase {

  /**
   * @var array|null
   */
  private $valueLog;

  protected function setUp(): void {
    $this->valueLog = [];
  }

  protected function addValue($value): void {
    $this->valueLog[] = $value;
  }

  public function testAutoCleanup() {
    $this->addValue('new');
    $func = function() use (&$state, &$stateLog) {
      $this->addValue('started');
      $ac = new AutoCleanup(function() {
        $this->addValue('cleaned');
      });
      $this->addValue('updated');

      $this->assertEquals(['new', 'started', 'updated'], $this->valueLog);
    };

    $this->assertEquals(['new'], $this->valueLog);
    $func();
    $this->assertEquals(['new', 'started', 'updated', 'cleaned'], $this->valueLog);
  }

}
