<?php

/*
 * This file is part of the Pho package.
 *
 * (c) Emre Sokullu <emre@phonetworks.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Pho\Kernel\Services\Database\Adapters;

use Pho\Kernel\Kernel;
use Pho\Kernel\Exceptions\KernelNotRunningException;
use Pho\Kernel\Services\ServiceInterface;
use Pho\Kernel\Services\Database\DatabaseInterface;
use Pho\Kernel\Services\Database\DatabaseListInterface;
use Pho\Kernel\Services\Exceptions\MissingAdapterExtensionException;

/**
 * Async Redis adapter as a database.
 *
 * This is the default database of Pho. Works with Predis
 * (https://github.com/nrk/predis)
 *
 * In production, make sure you have the PHP PECL extension
 * phpiredis (https://github.com/nrk/phpiredis) installed for
 * faster performance.
 *
 * @author Emre Sokullu
 */
class Redis implements DatabaseInterface, ServiceInterface {

  /**
   * @var \Pimple
   */
   private $kernel;

  /**
   * @var  \Swoole\Async\RedisClient
   */
  private $client;

  /**
   * Stores a list of RedisList objects.
   *
   * @var array
   */
  private $lists;

  public function __construct(Kernel $kernel, string $uri = "") {
    $this->kernel = $kernel;
    $this->client = new \Swoole\Async\RedisClient(self::urlCleanup($uri));
  }

  private static function urlCleanup(string $uri): string
  {
    if(substr($uri,0, strlen("tcp"))==="tcp")
      return "redis".substr($uri, strlen("tcp"));
    return $uri;
  }

  // async
  public function __call(string $method, array $arguments) {
    //return $this->client->$method(...$arguments);
    $val;
    $this->client_promise->done(
      function (Client $client) use ($method, $arguments, &$val) {
          $val = $client->$method(...$arguments);
      }
    );
    return $val;
  }

  public function set(string $key, $value): void
  {
    $this->client_promise->then(
      function (Client $client) use ($key, $value) {
          $client->set($key, $value);
      }
    );
  }

  public function get(string $key)
  {
    $val;
    $this->client_promise->done(
      function (Client $client) use ($key, &$val) {
          $val = $client->get($key);
      }
    );
    return $val;
  }

  /**
   * {@inheritdoc}
   */
  public function del(string $key): void
  {
    $this->client_promise->then(
      function (Client $client) use ($key) {
          $client->del($key);
      }
    );
  }



  /**
   * {@inheritdoc}
   */
  public function expire(string $key, int $timeout): void
  {
    $this->kernel["logger"]->info(
      sprintf("Expiring %s in %s", $key, (string) $timeout)
    );
    $this->client_promise->then(
      function (Client $client) use ($key, $timeout) {
          $client->expire($key, $timeout);
      }
    );
  }

  /**
   * {@inheritdoc}
   */
  public function ttl(string $key): int
  {
    $val;
    $this->client_promise->done(
      function (Client $client) use ($key, &$val) {
          $val = $client->ttl($key);
      }
    );
    return $val;
  }

}
