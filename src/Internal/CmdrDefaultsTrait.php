<?php

namespace Clippy\Internal;

trait CmdrDefaultsTrait {

  /**
   * @var array
   *   List of defaults to set on any newly constructed processes.
   */
  protected $defaults = [];

  /**
   * Set a list of default properties for newly constructed processes.
   *
   * @param array $defaults
   *   Ex: ['timeout' => 90, 'idleTimeout' => 30, 'workingDirectory' => '/tmp']
   * @return static
   * @see Process
   */
  public function setDefaults(array $defaults) {
    $aliases = [
      'cwd' => 'workingDirectory',
      'pwd' => 'workingDirectory',
    ];
    foreach ($aliases as $from => $to) {
      if (isset($defaults[$from])) {
        $defaults[$to] = $defaults[$from];
        unset($defaults[$from]);
      }
    }

    $this->defaults = $defaults;
    return $this;
  }

  /**
   * @return array
   *   Ex: ['timeout' => 90, 'idleTimeout' => 30, 'workingDirectory' => '/tmp']
   * @see Process
   */
  public function getDefaults(): array {
    return $this->defaults;
  }

  /**
   * Create a new instance with revised defaults.
   *
   * @param array $defaults
   * @return $this
   */
  public function withDefaults(array $defaults) {
    $result = clone $this;
    $result->setDefaults(array_merge($this->defaults, $defaults));
    return $result;
  }

}
