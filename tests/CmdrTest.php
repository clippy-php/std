<?php
namespace Clippy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

class CmdrTest extends TestCase {

  protected function createCmdr(): Cmdr {
    $io = new SymfonyStyle(new StringInput(''), new NullOutput());
    return new Cmdr($io, []);
  }

  public function testEscape() {
    $cmdr = $this->createCmdr();

    $this->assertEquals('echo hello', $cmdr->escape('echo {{0|s}}', ['hello']));
    $this->assertEquals('echo \'hello world!\'', $cmdr->escape('echo {{0|s}}', ['hello world!']));
    $this->assertEquals('echo \'hello 🦆!\'', $cmdr->escape('echo {{0|s}}', ['hello 🦆!']));
    $this->assertEquals('echo \'aGVsbG8g8J+mhg==\'', $cmdr->escape('echo {{0|6s}}', ['hello 🦆']));
    $this->assertEquals('echo ImhlbGxvIFx1ZDgzZVx1ZGQ4NiEi', $cmdr->escape('echo {{0|j6s}}', ['hello 🦆!']));
    $this->assertEquals('echo \'"hello \ud83e\udd86!"\'', $cmdr->escape('echo {{0|js}}', ['hello 🦆!']));
    $this->assertEquals('echo --name world', $cmdr->escape('echo {{0}}', ['--name world']));
    $this->assertEquals('echo \'--name world\'', $cmdr->escape('echo {{0|s}}', ['--name world']));
  }

  public function testRun() {
    $cmdr = $this->createCmdr();
    $original = 'hello "🦆 & 🐎" {animals\' favorite text*}';

    $result = trim($cmdr->run('echo {{0|s}}', [$original]));
    $this->assertEquals($original, $result);

    $result = json_decode($cmdr->run('echo {{0|js}}', [$original]));
    $this->assertEquals($original, $result);

    $result = base64_decode($cmdr->run('echo {{0|6s}}', [$original]));
    $this->assertEquals($original, $result);
  }

}
