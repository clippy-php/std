<?php

namespace Clippy;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use GuzzleHttp\Psr7\Request;

class Guzzle {

  public static function register(Container $c) {
    $c['guzzleHandler'] = $c->factory(function ($guzzleLogger) {
      $stack = \GuzzleHttp\HandlerStack::create();
      $stack->push($guzzleLogger);
      return $stack;
    });

    $c['guzzleLogger'] = function (SymfonyStyle $io) {
      return \GuzzleHttp\Middleware::tap(
        function (Request $request, $options) use ($io) {
          $io->writeln(sprintf('<info>Guzzle:</info> %s %s', $request->getMethod(), $request->getUri()), OutputInterface::VERBOSITY_VERBOSE);
          if ($io->isVeryVerbose()) {
            foreach ($request->getHeaders() as $k => $v) {
              $io->write(sprintf('* <comment>%s</comment>: ', $k));
              $io->writeln(implode(' ', $v), OutputInterface::OUTPUT_RAW);
            }
            //$io->writeln($request->getBody()->getContents(),
            //  OutputInterface::OUTPUT_RAW);
            //$request->getBody()->rewind();
          }
        }
      );
    };
  }

}
