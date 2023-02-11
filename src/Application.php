<?php
namespace Clippy;

use LesserEvil\ShellVerbosityIsEvil;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends \Silly\Application {

  public function run(InputInterface $input = NULL, OutputInterface $output = NULL) {
    $input = $input ?? $this->getContainer()->get('input');
    $output = $output ?? $this->getContainer()->get('output');
    return parent::run($input, $output);
  }

  /**
   * Register and execute a single-function command.
   *
   * @param string $sig
   *   Ex: '[--force|-f] [--out|-o]'
   * @param mixed $callback
   * @return int
   */
  public function main($sig, $callback) {
    $this->command("main $sig", $callback);
    $this->setDefaultCommand('main', TRUE);
    return $this->run();
  }

  protected function configureIO(InputInterface $input, OutputInterface $output): void {
    ShellVerbosityIsEvil::doWithoutEvil(function() use ($input, $output) {
      parent::configureIO($input, $output);
    });
  }

}
