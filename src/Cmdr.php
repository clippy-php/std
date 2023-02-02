<?php
namespace Clippy;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

/**
 * The Cmdr ("commander") is a smaller utility which makes it easier to
 * construct+execute shell commands with safely escaped data.
 *
 * It relies on a small expression format in which:
 * - Variables are marked with `{{MY_VAR}}`
 * - Variables may zero or more modifiers. Valid modifiers are:
 *     - 'u': URL escaping
 *     - 'h': HTML escaping
 *     - '6': Base64 escaping
 *     - 'j': JSON escaping
 *     - 's': Shell escaping
 *   If multiple escape-modifiers are listed, they will be applied in the
 *   order given.
 *
 * When there are multiple modifiers, there applied iteratively (in order).
 *
 * Examples:
 *   $output = $cmdr->run('ls {{FILE|s}}', ['FILE' => '/home/foo bar/whiz&bang']);
 *   $process = $cmdr->process('echo {{MESSAGE|js}} | json_pp', ['MESSAGE' => 'The stuff.']);
 */
class Cmdr {

  public static function register(Container $c) {
    $c->set('cmdr', function (SymfonyStyle $io) {
      $cmdr = new Cmdr();
      $cmdr->io = $io;
      $cmdr->defaults = [
        'timeout' => NULL,
      ];
      return $cmdr;
    });
    return $c;
  }

  /**
   * @var \Symfony\Component\Console\Style\SymfonyStyle
   */
  protected $io;

  /**
   * @var array
   *   List of defaults to set on any newly contructed processes.
   */
  protected $defaults = [];

  /**
   * Run a command, assert the outcome is OK, and return all output.
   *
   * TIP: If you need more precise control over error-conditions, pipes, etc,
   * then use `process()` and the Symfony Process API.
   *
   * @param string|Process $cmd
   * @param array|null $vars
   * @return string
   *   The console output of the command.
   * @throws CmdrProcessException
   */
  public function run($cmd, $vars = NULL) {
    $process = $this->process($cmd, $vars);
    $this->io->writeln("<comment>\$</comment> " . $process->getCommandLine() . " <comment>[[in " . $process->getWorkingDirectory() . "]]</comment>", OutputInterface::VERBOSITY_VERBOSE);
    $process->run();

    if (!$process->isSuccessful()) {
      $this->io->writeln("<error>Command failed:</error> " . $process->getCommandLine() . " <comment>[[in " . $process->getWorkingDirectory() . "]]</comment>", OutputInterface::VERBOSITY_VERBOSE);
      throw new CmdrProcessException($process);
    }

    return $process->getOutput();
  }

  /**
   * Run a command, passing output through to the console, and asserting that
   * the outcome is OK.
   *
   * TIP: If you need more precise control over error-conditions, pipes, etc,
   * then use `process()` and the Symfony Process API.
   *
   * @param string|Process $cmd
   * @param array|null $vars
   * @return \Symfony\Component\Process\Process
   */
  public function passthru($cmd, $vars = NULL) {
    $process = $this->process($cmd, $vars);
    $this->io->writeln("<comment>\$</comment> " . $process->getCommandLine() . " <comment>[[in " . $process->getWorkingDirectory() . "]]</comment>", OutputInterface::VERBOSITY_VERBOSE);
    if (function_exists('posix_isatty')) {
      $process->setTty(\posix_isatty(STDOUT));
    }
    $process->run(function($type, $buffer) {
      if (Process::ERR === $type) {
        fwrite(STDERR, $buffer);
      }
      else {
        $this->io->write($buffer);
      }
    });

    if (!$process->isSuccessful()) {
      $this->io->writeln("<error>Command failed:</error> " . $process->getCommandLine() . " <comment>[[in " . $process->getWorkingDirectory() . "]]</comment>", OutputInterface::VERBOSITY_VERBOSE);
      throw new CmdrProcessException($process);
    }

    return $process;
  }

  /**
   * Create a "Process" object for the given command.
   *
   * TIP: If you just want to run the command without tracking the
   * errors/outputs carefully, then use run().
   *
   * @param string|Process $cmd
   * @param array|NULL $vars
   * @return \Symfony\Component\Process\Process
   */
  public function process($cmd, $vars = NULL) {
    if ($cmd instanceof Process) {
      return $cmd;
    }
    else {
      $p = new Process($this->escape($cmd, $vars ?: []));
      foreach ($this->defaults as $key => $value) {
        $method = 'set' . strtoupper($key[0]) . substr($key, 1);
        if (is_callable([$p, $method])) {
          $p->$method($value);
        }
        else {
          throw new \RuntimeException("Cannot apply default for Process::\$" . $key);
        }
      }
      return $p;
    }
  }

  /**
   * Evaluate variable expression in a command string, with any escaping
   * rules applied to variable inputs.
   *
   * @param string $cmd
   *   Ex: 'ls {{TGT_PATH|s}} | wc'
   *
   *   Note: Variables may be expressed `{{FOO}}`.
   *   To add escaping to a variable, you may append one or more modifiers.
   *     - 'u': URL escaping
   *     - 'h': HTML escaping
   *     - '6': Base64 escaping
   *     - 'j': JSON escaping
   *     - 's': Shell escaping
   *   If multiple escape-modifiers are listed, they will be applied in the
   *   order given.
   *
   *   Ex: '{{TGT_PATH|6js}}' would be equivalient to `escapeshellarg(json_encode(base64_encode($TGT_PATH)))`
   * @param array|NULL $vars
   *   List of variable values that may be interpolated.
   * @return string
   *   The content of $cmd with $vars interpolated.
   */
  public function escape($cmd, $vars = []) {
    return preg_replace_callback('/\{\{([A-Za-z0-9_]+)(\|\w+)?\}\}/', function ($m) use ($vars) {
      $var = $m[1];
      $val = $vars[$var] ?? '';
      $modifier = $m[2] ?? NULL;
      $modifier = ltrim($modifier ?? '', '|');

      for ($i = 0; $i < strlen($modifier); $i++) {
        switch ($modifier[$i]) {
          case 's':
            $val = escapeshellarg($val);
            break;

          case 'u':
            $val = urlencode($val);
            break;

          case 'h':
            $val = htmlentities($val);
            break;

          case '6':
            $val = base64_encode($val);
            break;

          case 'j':
            $val = json_encode($val);
            break;

          default:
            //printf("FIXME: handle modifier [%s]\n", $modifier{$i});
        }
      }
      return $val;
    }, $cmd);
  }

  /**
   * Set a list of default properties for newly constructed processes.
   *
   * @param array $defaults
   *   Ex: ['timeout' => 90, 'idleTimeout' => 30]
   * @return static
   * @see Process
   */
  public function setDefaults($defaults) {
    $this->defaults = $defaults;
    return $this;
  }

  /**
   * @return array
   *   Ex: ['timeout' => 90, 'idleTimeout' => 30]
   * @see Process
   */
  public function getDefaults() {
    return $defaults;
  }

}
