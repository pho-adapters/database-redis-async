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
 * Redis adapter as a database.
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
   * @var Predis\Client
   */
  private $client;

  /**
   * Stores a list of RedisList objects.
   *
   * @var array
   */
  private $lists;

  public function __construct(Kernel $kernel, string $uri = "") {
    $loop = new React\EventLoop\StreamSelectLoop();
    $this->client = new \Predis\Client($uri, $loop);
    $this->kernel = $kernel;
  }

  public function __call(string $method, array $arguments) {
    return $this->client->$method(...$arguments);
  }

  public function set(string $key, $value): void
  {
    $this->client->set($key, $value);
  }

  public function get(string $key)
  {
    // returns null automatically if the given key does not exist.
    return $this->client->get($key);
  }

  /**
   * {@inheritdoc}
   */
  public function del(string $key): void
  {
    $this->client->del($key);
  }



  /**
   * {@inheritdoc}
   */
  public function expire(string $key, int $timeout): void
  {
    $this->kernel["logger"]->info(
      sprintf("Expiring %s in %s", $key, (string) $timeout)
    );
    $this->client->expire($key, $timeout);
  }

  /**
   * {@inheritdoc}
   */
  public function ttl(string $key): int
  {
    return $this->client->ttl($key);
  }

}
