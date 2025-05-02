<?php
namespace Clippy;

// -----------------------------------------------------------------------------
// Assertions

/**
 * Assert that $bool is true.
 *
 * @param bool $bool
 * @param string $msg
 * @throws \Exception
 */
function assertThat($bool, $msg = '') {
  if (!$bool) {
    throw new \Exception($msg ? $msg : 'Assertion failed');
  }
}

/**
 * Assert that $value has an actual value (not null or empty-string)
 *
 * @param mixed $value
 * @param string $msg
 * @return mixed
 *   The approved value
 * @throws \Exception
 */
function assertVal($value, $msg) {
  if ($value === NULL || $value === '') {
    throw new \Exception($msg ? $msg : 'Missing expected value');
  }
  return $value;
}

function fail($msg) {
  throw new \Exception($msg ? $msg : 'Assertion failed');
}

// -----------------------------------------------------------------------------
// IO utilities

/**
 * Combine all elements of part, in order, to form a string - using path delimiters.
 * Duplicate delimiters are trimmed.
 *
 * @param array $parts
 *   A list of strings and/or arrays.
 * @return string
 */
function joinPath(...$parts) {
  $path = [];
  foreach ($parts as $part) {
    if (is_array($part)) {
      $path = array_merge($path, $part);
    }
    else {
      $path[] = $part;
    }
  }
  $result = implode(DIRECTORY_SEPARATOR, $parts);
  $both = "[\\/]";
  return preg_replace(";{$both}{$both}+;", '/', $result);
}

/**
 * Combine all elements of parh, in order, to form a string - using URL delimiters.
 * Duplicate delimiters are trimmed.
 *
 * @param array $parts
 *   A list of strings and/or arrays.
 * @return string
 */
function joinUrl(...$parts) {
  $path = [];
  foreach ($parts as $part) {
    if (is_array($part)) {
      $path = array_merge($path, $part);
    }
    else {
      $path[] = $part;
    }
  }
  $combined = implode('/', $parts);
  if (preg_match(';[^:]//;', $combined)) {
    $uri = new \GuzzleHttp\Psr7\Uri($combined);
    $combined = (string) $uri->withPath(preg_replace(';//+;', '/', $uri->getPath()));
  }
  return $combined;
}

function toJSON($data) {
  return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}

function fromJSON($data) {
  #!require psr/http-message: *
  if ($data instanceof \Psr\Http\Message\ResponseInterface) {
    $data = $data->getBody()->getContents();
  }
  assertThat(is_string($data));
  $result = json_decode($data, 1);
  assertThat($result !== NULL || $data === 'null', sprintf("JSON parse error:\n----\n%s\n----\n", $data));
  return $result;
}

// -----------------------------------------------------------------------------
// Array utilities

/**
 * Builds an array-tree which indexes the records in an array.
 *
 * Ex:
 *   $records = [ ['name'=>'Alice','id'=>100], ['name'=>'Bob', 'id'=>200] ];
 *   $byId = index('id', $records);
 *   assertThat($byId[200]['name'] === 'Bob');
 *
 * @param string|string[] $keys
 *   Properties by which to index.
 *   If more than one key is given, then result will be a multi-dimensional array.
 * @param array $records
 *   Each record may be an object or array.
 *
 * @return array
 *   Multi-dimensional array, with one layer for each key.
 */
function index($keys, $records) {
  $keys = (array) $keys;
  $final_key = array_pop($keys);

  $result = [];
  foreach ($records as $record) {
    $node = &$result;
    foreach ($keys as $key) {
      if (is_array($record)) {
        $keyvalue = isset($record[$key]) ? $record[$key] : NULL;
      }
      else {
        $keyvalue = isset($record->{$key}) ? $record->{$key} : NULL;
      }
      if (isset($node[$keyvalue]) && !is_array($node[$keyvalue])) {
        $node[$keyvalue] = [];
      }
      $node = &$node[$keyvalue];
    }
    if (is_array($record)) {
      $node[$record[$final_key]] = $record;
    }
    else {
      $node[$record->{$final_key}] = $record;
    }
  }
  return $result;
}

// -----------------------------------------------------------------------------
// High level services

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @return Container
 */
function clippy(?InputInterface $input = NULL, ?OutputInterface $output = NULL) {
  $container = new Container();
  $container->set('container', $container);
  $container->set('input', $input ?? new \Symfony\Component\Console\Input\ArgvInput());
  $container->set('output', $output ?? new \Symfony\Component\Console\Output\ConsoleOutput());
  $container->set('io', new \Symfony\Component\Console\Style\SymfonyStyle($container['input'], $container['output']));
  return $container;
}

/**
 * Define the list of available plugins.
 *
 * @param array|null $names
 *   - If NULL, then returning all values.
 *   - If an array, then of the named items.
 * @return array
 */
function plugins($names = NULL) {
  if (is_array($names)) {
    return array_intersect_key($GLOBALS['plugins'], array_fill_keys($names, 1));
  }
  else {
    return $GLOBALS['plugins'];
  }
}
