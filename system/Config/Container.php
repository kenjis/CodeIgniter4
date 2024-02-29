<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace CodeIgniter\Config;

use Config\Modules;
use InvalidArgumentException;

/**
 * Service Container.
 */
final class Container
{
    /**
     * Factory method list.
     *
     * @var array<string, (callable(mixed ...$params): object)> [id => callable]
     */
    private array $factories = [];

    /**
     * Instance list.
     *
     * @var array<string, object> [id => instance]
     */
    private array $instances = [];

    /**
     * A cache of the names of services classes found.
     *
     * @var list<class-string>
     */
    private array $serviceClassnames = [];

    /**
     * Container instance.
     */
    private static Container $instance;

    public function __construct()
    {
        Container::$instance = $this;
    }

    public function loadServices()
    {
        $this->buildServicesCache();
    }

    /**
     * Gets the container instance.
     */
    public static function getContainer(): Container
    {
        return Container::$instance;
    }

    /**
     * Finds an entry of the container by its identifier and returns it.
     *
     * @param string $id Identifier of the entry to look for.
     *
     * @return mixed Entry.
     */
    public function get(string $id): mixed
    {
        if (! isset($this->instances[$id])) {
            if (isset($this->factories[$id])) {
                $this->instances[$id] = $this->factories[$id]($this);

                return $this->instances[$id];
            }

            // Fallbacks to BaseService.
            if ($this->serviceExists($id)) {
                $this->instances[$id] = $this->factories[$id]();

                return $this->instances[$id];
            }

            throw new InvalidArgumentException(
                'The Entry for "' . $id . '" is not found.'
            );
        }

        return $this->instances[$id];
    }

    /**
     * Sets an entry in the container.
     *
     * @param string $id    Identifier of the entry.
     * @param mixed  $value Normally an object.
     */
    public function set(string $id, mixed $value): void
    {
        if (isset($this->instances[$id])) {
            throw new InvalidArgumentException(
                'The Entry for "' . $id . '" is already set.'
            );
        }

        $this->instances[$id] = $value;
    }

    /**
     * Sets a factory in the container.
     *
     * @param string $id       Identifier of the entry.
     * @param mixed  $callable Callable to create the instance.
     */
    public function setFactory(string $id, callable $callable): void
    {
        if (isset($this->factories[$id])) {
            throw new InvalidArgumentException(
                'The Entry for "' . $id . '" is already set.'
            );
        }

        $this->factories[$id] = $callable;
    }

    /**
     * Overrides an entry in the container.
     *
     * @param string $id    Identifier of the entry.
     * @param mixed  $value Normally an object.
     */
    public function override(string $id, mixed $value): void
    {
        $this->instances[$id] = $value;
    }

    /**
     * Check if the requested service is defined and return the declaring
     * class. Return null if not found.
     */
    private function serviceExists(string $name): bool
    {
        $name = strtolower($name);

        foreach ($this->serviceClassnames as $service) {
            if (method_exists($service, $name)) {
                $this->factories[$name] = [$service, $name];

                return true;
            }
        }

        return false;
    }

    private function buildServicesCache(): void
    {
        if ((new Modules())->shouldDiscover('services')) {
            $locator = BaseService::locator();
            $files   = $locator->search('Config/Services');

            // Get instances of all service classes and cache them locally.
            foreach ($files as $file) {
                $classname = $locator->findQualifiedNameFromPath($file);

                if ($classname === false) {
                    continue;
                }

                if ($classname !== Services::class) {
                    $this->serviceClassnames[] = $classname;
                }
            }
        }

        $this->serviceClassnames[] = Services::class;
    }

    /**
     * Returns true if the container has the instance.
     * Returns false otherwise.
     *
     * @param string $id Identifier of the entry to look for.
     */
    public function hasInstance(string $id): bool
    {
        return isset($this->instances[$id]);
    }
}
