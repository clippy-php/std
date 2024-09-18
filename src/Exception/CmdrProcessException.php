<?php
namespace Clippy\Exception;

class CmdrProcessException extends \RuntimeException {

  /**
   * @var \Symfony\Component\Process\Process
   */
  private $process;

  /**
   * @var bool
   *   TRUE if the process has already passed-through its STDOUT/STDERR
   */
  private $passthru;

  public function __construct(\Symfony\Component\Process\Process $process, $message = "", $code = 0, Exception $previous = NULL, bool $passthru = FALSE) {
    $this->process = $process;
    $this->passthru = $passthru;
    if (empty($message)) {
      $message = $this->createReport($process);
    }
    parent::__construct($message, $code, $previous);
  }

  /**
   * @param \Symfony\Component\Process\Process $process
   */
  public function setProcess($process) {
    $this->process = $process;
  }

  /**
   * @return \Symfony\Component\Process\Process
   */
  public function getProcess() {
    return $this->process;
  }

  public function createReport($process) {
    $buf []= "Process failed:";
    $buf []= "[[ COMMAND: {$process->getCommandLine()} ]]";
    $buf []= "[[ CWD: {$process->getWorkingDirectory()} ]]";
    $buf []= "[[ EXIT CODE: {$process->getExitCode()} ]]";
    if (!$this->passthru) {
      $buf []= "[[ STDOUT ]]";
      $buf []= "{$process->getOutput()}";
      $buf []= "[[ STDERR ]]";
      $buf []= "{$process->getErrorOutput()}";
    }
    $buf []= "";
    return implode("\n", $buf);
  }

}
