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

use CodeIgniter\Debug\Timer;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @internal
 *
 * @group Others
 */
final class ContainerTest extends CIUnitTestCase
{
    private Container $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->container = new Container();
        $this->container->loadServices();
    }

    public function testInstantiation()
    {
        $this->assertInstanceOf(Container::class, $this->container);
    }

    public function testSetFactory()
    {
        $this->container->setFactory('timer', static fn ($container) => new Timer());

        $obj = $this->container->get('timer');
        $this->assertInstanceOf(Timer::class, $obj);
    }

    public function testSetInstance()
    {
        $timer = new Timer();
        $this->container->set('timer', $timer);

        $obj = $this->container->get('timer');

        $this->assertSame($timer, $obj);
    }

    public function testSetTwice()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Entry for "timer" is already set.');

        $timer = new Timer();
        $this->container->set('timer', $timer);

        $timer = new Timer();
        $this->container->set('timer', $timer);
    }

    public function testOverride()
    {
        $timer1 = new Timer();
        $this->container->set('timer', $timer1);

        $timer2 = new Timer();
        $this->container->override('timer', $timer2);

        $obj = $this->container->get('timer');

        $this->assertSame($timer2, $obj);
    }

    public function testGetFallbackToServices()
    {
        $obj = $this->container->get('incomingrequest');

        $this->assertInstanceOf(IncomingRequest::class, $obj);
    }

    public function testGetEntryNotFound()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The Entry for "notexistent" is not found.');

        $this->container->get('notexistent');
    }

    public function testHasInstanceReturnsTrue()
    {
        $timer = new Timer();
        $this->container->set('timer', $timer);

        $result = $this->container->hasInstance('timer');

        $this->assertTrue($result);
    }

    public function testHasInstanceReturnsFalse()
    {
        $result = $this->container->hasInstance('timer');

        $this->assertFalse($result);
    }

    public function testStaticGetContainer()
    {
        $container = Container::getContainer();

        $this->assertSame($this->container, $container);
    }
}
