<?php
namespace Clippy;

use Clippy\Exception\UserQuitException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;

/**
 * The `Taskr` ("tasker") is a small utility for writing adhoc scripts that run
 * a series of commands. It is similar to `Cmdr`, but it supports runtime options
 * that allow the user to monitor the tasks.
 *
 * For example:
 *
 * $c['app']->command('do-stuff [--step] [--dry-run]', function(Taskr $taskr) {
 *   $taskr->passthru('rm -f abc.txt');
 *   $taskr->passthru('touch def.txt');
 * });
 *
 * By default, the 'do-stuff' command will remove 'abc.txt' and create 'def.txt'.
 * If you call `do-stuff --step`, then it will prompt before executing each command.
 * If you call 'do-stuff --dry-run', then it will print a message about each command (but will not execute).
 */
class Taskr {

  /**
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   * @param \Symfony\Component\Console\Input\InputInterface $input
   * @param \Symfony\Component\Console\Output\OutputInterface $output
   * @param \Clippy\Cmdr $cmdr
   */
  public function __construct(StyleInterface $io, InputInterface $input, OutputInterface $output, Cmdr $cmdr) {
    $this->io = $io;
    $this->input = $input;
    $this->output = $output;
    $this->cmdr = $cmdr;
  }

  public static function register(Container $c): Container {
    $c->set('taskr', function (StyleInterface $io, InputInterface $input, OutputInterface $output, Cmdr $cmdr) {
      return new Taskr($io, $input, $output, $cmdr);
    });
    return $c;
  }

  /**
   * @var \Symfony\Component\Console\Style\StyleInterface
   */
  protected $io;

  /**
   * @var \Symfony\Component\Console\Input\InputInterface
   */
  protected $input;

  /**
   * @var \Symfony\Component\Console\Output\OutputInterface
   */
  protected $output;

  /**
   * @var Cmdr
   */
  protected $cmdr;

  protected $isChecked = FALSE;

  public function assertConfigured(): void {
    if ($this->isChecked) {
      return;
    }

    $missingOptions = [];
    if (!$this->input->hasOption('step')) {
      $missingOptions[] = '--step';
    }
    if (!$this->input->hasOption('dry-run')) {
      $missingOptions[] = '--dry-run';
    }
    if (!empty($missingOptions)) {
      trigger_error("Command uses 'Taskr', but the expected options (" . implode(' ', $missingOptions) . ") have not been pre-defined.", E_USER_WARNING);
    }

    $this->isChecked = TRUE;
  }

  public function passthru(string $cmd, array $params = []) {
    $this->assertConfigured();

    $io = $this->io;
    $extraVerbosity = 0;
    $cmdDesc = '<comment>$</comment> ' . $this->cmdr->escape($cmd, $params) . ' <comment>[[in ' . getcwd() . ']]</comment>';

    if ($this->input->hasOption('step') && $this->input->getOption('step')) {
      $extraVerbosity = OutputInterface::VERBOSITY_VERBOSE - $io->getVerbosity();
      $io->writeln('<comment>COMMAND</comment>' . $cmdDesc);
      $confirmation = ($io->ask('<info>Execute this command?</info> (<comment>[Y]</comment>es, <comment>[s]</comment>kip, <comment>[q]</comment>uit)', NULL, function ($value) {
        $value = ($value === NULL) ? 'y' : mb_strtolower($value);
        if (!in_array($value, ['y', 's', 'q'])) {
          throw new InvalidArgumentException("Invalid choice ($value)");
        }
        return $value;
      }));
      switch ($confirmation) {
        case 's':
          return;

        case 'y':
        case NULL:
          break;

        case 'q':
        default:
          throw new UserQuitException();
      }
    }

    if ($this->input->hasOption('dry-run') && $this->input->getOption('dry-run')) {
      $io->writeln('<comment>DRY-RUN</comment>' . $cmdDesc);
      return;
    }

    try {
      $io->setVerbosity($io->getVerbosity() + $extraVerbosity);
      $this->cmdr->passthru($cmd, $params);
    } finally {
      $io->setVerbosity($io->getVerbosity() - $extraVerbosity);
    }
  }

}
