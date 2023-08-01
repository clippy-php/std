<?php
namespace Clippy;

use Clippy\Internal\CmdrDefaultsTrait;
use Clippy\Internal\Shell;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\StyleInterface;
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

  public static function register(Container $c): Container {
    $c->set('cmdr', function (StyleInterface $io) {
      return new Cmdr($io, [
        'timeout' => NULL,
      ]);
    });
    return $c;
  }

  /**
   * @var \Symfony\Component\Console\Style\StyleInterface
   */
  protected $io;

  use CmdrDefaultsTrait;

  /**
   * @param \Symfony\Component\Console\Style\StyleInterface $io
   * @param array $defaults
   */
  public function __construct(StyleInterface $io, array $defaults = []) {
    $this->io = $io;
    $this->setDefaults($defaults);
  }

  /**
   * Run a command, assert the outcome is OK, and return all output.
   *
   * TIP: If you need more precise control over error-conditions, pipes, etc,
   * then use `process()` and the Symfony Process API.
   *
   * @param string|\Symfony\Component\Process\Process $cmd
   * @param array|null $vars
   * @return string
   *   The console output of the command.
   * @throws CmdrProcessException
   */
  public function run($cmd, ?array $vars = NULL): string {
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
   * @param string|\Symfony\Component\Process\Process $cmd
   * @param array|null $vars
   * @return \Symfony\Component\Process\Process
   */
  public function passthru($cmd, ?array $vars = NULL): \Symfony\Component\Process\Process {
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
   * @param string|\Symfony\Component\Process\Process $cmd
   * @param array|NULL $vars
   * @return \Symfony\Component\Process\Process
   */
  public function process($cmd, ?array $vars = NULL): \Symfony\Component\Process\Process {
    if ($cmd instanceof Process) {
      return $cmd;
    }
    else {
      $p = new Process($this->escape($cmd, $vars ?: []));
      foreach ($this->getDefaults() as $key => $value) {
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
   *   Variables are marked with curly braces, as in `{{FOO}}`.
   *
   *   To add escaping to a variable, you may append one or more modifiers.
   *     - 'u': URL escaping
   *     - 'h': HTML escaping
   *     - '6': Base64 escaping
   *     - 'j': JSON escaping
   *     - 's': Shell escaping
   *
   *   Ex: '{{TGT_PATH|s}}' would be equivalent to `escapeshellarg($TGT_PATH)`
   *
   *   If multiple escape-modifiers are listed, they will be applied in the order given.
   *
   *   Ex: '{{TGT_PATH|6js}}' would be equivalent to calling base64_encode() + json_encode() + escapeshellarg(),
   *       as in `escapeshellarg(json_encode(base64_encode($TGT_PATH)))`
   *
   *   Additionally, the modifier '@' indicates multi-parameter-mode (array-mode). In this mode, you may give an array
   *   of inputs, and each will be escaped separately.
   *
   *   Ex: 'ls {{TGT_PATHS|@s}}' would be equivalent to calling `escapeshellarg` and joining with ' ',
   *       as in 'implode(' ', array_map('escapeshellarg', $TGT_PATHS))`
   *
   *   The special variable `...` can be used to combine all variables (when numerically-indexed).
   *
   *   Ex: escape('ls -l {{...|@s}}', ['file 1.txt, 'file 2.txt', 'file 3.txt'])
   *
   * @param array|NULL $vars
   *   List of variables that may be interpolated.
   *   Variables may be keyed by names (for better readability) or numbers (for more concision).
   * @return string
   *   The content of $cmd with $vars interpolated.
   */
  public function escape(string $cmd, ?array $vars = []): string {
    return preg_replace_callback('/\{\{(\.\.\.|[A-Za-z0-9_]+)(\|[@\w]+)?\}\}/', function ($m) use ($cmd, $vars) {
      if ($m[1] === '...') {
        $val = array_filter($vars, 'is_numeric', ARRAY_FILTER_USE_KEY);
      }
      else {
        $val = $vars[$m[1]] ?? '';
      }
      $modifier = $m[2] ?? NULL;
      $modifier = ltrim($modifier ?? '', '|');
      $isMultiParamMode = FALSE;

      $apply = function($func, $value) use (&$isMultiParamMode) {
        return $isMultiParamMode && is_array($value) ? array_map($func, $value) : $func($value);
      };

      for ($i = 0; $i < strlen($modifier); $i++) {
        switch ($modifier[$i]) {
          case '@':
            $isMultiParamMode = TRUE;
            break;

          case 's':
            $val = $apply([Shell::class, 'lazyEscape'], $val);
            break;

          case 'u':
            $val = $apply('urlencode', $val);
            break;

          case 'h':
            $val = $apply('htmlentities', $val);
            break;

          case '6':
            $val = $apply('base64_encode', $val);
            break;

          case 'j':
            $val = $apply('json_encode', $val);
            break;

          default:
            //printf("FIXME: handle modifier [%s]\n", $modifier{$i});
        }
      }
      if (is_array($val)) {
        if ($isMultiParamMode) {
          $val = implode(' ', (array) $val);
        }
        else {
          trigger_error(sprintf('In expression \"%s\", item \"%s\" resolved to an array', $cmd, $m[0]), E_USER_WARNING);
        }
      }
      return $val;
    }, $cmd);
  }

}
