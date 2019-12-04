<?php
namespace Clippy;

use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleApp {

  public static function register(Container $container) {
    $container->set('log', function(OutputInterface $output){
      return new ConsoleLogger($output);
    });
    $container->set('app', function ($container) {
      $app = new Application('UNKNOWN', 'UNKNOWN');
      $app->useContainer($container, FALSE, TRUE);
      return $app;
    });
  }

}