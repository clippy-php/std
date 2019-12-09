<?php
namespace Clippy;
use Symfony\Component\Console\Style\SymfonyStyle;

class Credentials {

  /**
   * @var SymfonyStyle
   */
  protected $io;

  protected $CLIPPY_CRED;

  public static function register(Container $c) {
    // Path to the credentials file
    $c->set('CLIPPY_CRED', function () {
      return getenv('CLIPPY_CRED')
        ? getenv('CLIPPY_CRED')
        : joinPath(getenv('HOME'), '.config', 'clippy-cred');
    });

    // The service object
    $c->set('cred', function (SymfonyStyle $io, $CLIPPY_CRED) {
      $cred = new Credentials();
      $cred->io = $io;
      $cred->CLIPPY_CRED = $CLIPPY_CRED;
      return $cred;
    });
    return $c;
  }

  /**
   * @param string $name
   *   A name for this credential.
   *
   *   Credentials may be read from the container, the environment, or
   *   a config file.
   *
   *   The naming should be all-caps in deference to env-var naming.
   * @param string $context
   * @return null|string
   */
  public function get($name, $context = 'default') {
    $io = $this->io;

    if (isset($container[$name])) {
      return $container[$name];
    }
    if (getenv($name)) {
      return getenv($name);
    }

    $storage = joinPath($this->CLIPPY_CRED, urlencode($context) . '.json');
    if (file_exists($storage)) {
      $data = fromJSON(file_get_contents($storage));
      if (isset($data[$name])) {
        return $data[$name];
      }
    }
    if ($io) {
      if ($storage) {
        $io->note("Credential $name not found in environment");
        $io->note("Credential $name not found in $storage");
      }
      if ($context === 'default') {
        $pass = $io->askHidden(sprintf('Please enter credential %s for %s', $name, $context));
      }
      else {
        $pass = $io->askHidden(sprintf('Please enter credential %s', $name));
      }
      // TODO save
      return $pass;
    }
    return NULL;
  }

}
