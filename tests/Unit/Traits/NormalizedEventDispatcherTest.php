<?php

/**
 * Copyright © OXID eSales AG. All rights reserved.
 * See LICENSE file for license details.
 */

declare(strict_types=1);

namespace OxidSolutionCatalysts\PayPal\Tests\Unit\Traits;

use OxidEsales\TestingLibrary\UnitTestCase;
use OxidSolutionCatalysts\PayPal\Traits\NormalizedEventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Test class for NormalizedEventDispatcher trait
 *
 * @covers \OxidSolutionCatalysts\PayPal\Traits\NormalizedEventDispatcher
 */
final class NormalizedEventDispatcherTest extends UnitTestCase
{
    /** @var object */
    private $traitUser;

    /** @var EventDispatcherInterface */
    private $mockDispatcher;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an anonymous class that uses the trait
        $this->traitUser = new class {
            use NormalizedEventDispatcher;
        };

        // Create a mock dispatcher
        $this->mockDispatcher = $this->createMock(EventDispatcher::class);

        // Set the mock dispatcher in the serviceArray for testing
        $reflection = new \ReflectionProperty(get_class($this->traitUser), 'serviceArray');
        $reflection->setAccessible(true);
        $reflection->setValue($this->traitUser, ['event_dispatcher' => $this->mockDispatcher]);

        $r=1;
    }

    /**
     * Test dispatchNormalized with event object and event name (Symfony 5+ style)
     */
    public function testDispatchNormalizedWithEventAndNameSymfony5Plus()
    {
        $event = new TestEvent();
        $eventName = 'test.event';

        // Mock the dispatcher to accept Symfony 5+ signature: dispatch($event, $eventName)
        $this->mockDispatcher
            ->method('dispatch')
            ->with($event, $eventName)
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event, $eventName);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatchNormalized with event name and event object (reversed order)
     */
    public function testDispatchNormalizedWithReversedArgumentsSymfony5Plus()
    {
        $event = new TestEvent();
        $eventName = 'test.event';

        // The trait should normalize the order regardless of how arguments are passed
        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($event, $eventName)
            ->willReturn($event);

        // Pass arguments in reversed order
        $result = $this->traitUser->dispatchNormalized($eventName, $event);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatchNormalized with only event object (no event name)
     */
    public function testDispatchNormalizedWithOnlyEventObject()
    {
        $event = new TestEvent();
        $expectedEventName = get_class($event);

        // Should use the event class name as the event name
        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($event, $expectedEventName)
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatchNormalized throws exception when no event object is provided
     */
    public function testDispatchNormalizedThrowsExceptionWithoutEvent()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Event object is required');

        $this->traitUser->dispatchNormalized('just.a.string');
    }

    /**
     * Test dispatchNormalized with custom event object (not extending Symfony Event)
     */
    public function testDispatchNormalizedWithCustomEventObject()
    {
        $event = new CustomTestEvent();
        $eventName = 'custom.event';

        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($event, $eventName)
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event, $eventName);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatchNormalized detects Symfony 3/4 signature (first param has no type)
     */
    public function testDispatchNormalizedWithSymfony3And4Signature()
    {
        $event = new TestEvent();
        $eventName = 'test.event';

        // Create a mock dispatcher with Symfony 3/4 signature
        $legacyDispatcher = new Symfony3And4CompatibleDispatcher();

        // Set the legacy dispatcher
        $reflection = new \ReflectionProperty(get_class($this->traitUser), 'serviceArray');
        $reflection->setAccessible(true);
        $reflection->setValue($this->traitUser, ['event_dispatcher' => $legacyDispatcher]);

        $result = $this->traitUser->dispatchNormalized($event, $eventName);

        // Verify the dispatcher was called with the correct signature
        $this->assertSame($event, $result);
        $this->assertEquals($eventName, $legacyDispatcher->lastEventName);
        $this->assertSame($event, $legacyDispatcher->lastEvent);
    }

    /**
     * Test dispatchNormalized with multiple string arguments (should use first one)
     */
    public function testDispatchNormalizedWithMultipleStringsUsesFirst()
    {
        $event = new TestEvent();
        $firstEventName = 'first.event';
        $secondEventName = 'second.event';

        // Should use the first string as event name
        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($event, $firstEventName)
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event, $firstEventName, $secondEventName);

        $this->assertSame($event, $result);
    }

    /**
     * Test dispatchNormalized with multiple objects (should use first one)
     */
    public function testDispatchNormalizedWithMultipleObjectsUsesFirst()
    {
        $event = new TestEvent();
        $anotherObject = new \stdClass();
        $eventName = 'test.event';

        // Should use the first object as event
        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->with($event, $eventName)
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event, $anotherObject, $eventName);

        $this->assertSame($event, $result);
    }

    /**
     * Test that dispatchNormalized uses OXID_PHP_UNIT constant to fetch mock service
     */
    public function testDispatchNormalizedUsesPhpUnitConstant()
    {
        if (!defined('OXID_PHP_UNIT')) {
            define('OXID_PHP_UNIT', true);
        }

        $event = new TestEvent();

        $this->mockDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturn($event);

        $result = $this->traitUser->dispatchNormalized($event);

        $this->assertSame($event, $result);
    }
}

/**
 * Test event class extending Symfony Event
 */
class TestEvent extends Event
{
    /** @var string */
    private $data = 'test';

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }
}

/**
 * Custom event class that doesn't extend Symfony Event
 */
class CustomTestEvent
{
    /** @var string */
    private $customData = 'custom';

    /**
     * @return string
     */
    public function getCustomData()
    {
        return $this->customData;
    }
}

/**
 * Mock dispatcher with Symfony 3/4 compatible signature
 */
class Symfony3And4CompatibleDispatcher implements EventDispatcherInterface
{
    /** @var string|null */
    public $lastEventName = null;

    /** @var object|null */
    public $lastEvent = null;

    /**
     * Symfony 3/4 signature: dispatch($eventName, $event = null)
     *
     * @param string|object $eventName
     * @param object|null $event
     * @return object
     */
    public function dispatch($eventName, $event = null)
    {
        $this->lastEventName = $eventName;
        $this->lastEvent = $event;
        return $event;
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @param int $priority
     */
    public function addListener($eventName, $listener, $priority = 0)
    {
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
     */
    public function addSubscriber($subscriber)
    {
    }

    /**
     * @param string $eventName
     * @param callable $listener
     */
    public function removeListener($eventName, $listener)
    {
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventSubscriberInterface $subscriber
     */
    public function removeSubscriber($subscriber)
    {
    }

    /**
     * @param string|null $eventName
     * @return array
     */
    public function getListeners($eventName = null)
    {
        return [];
    }

    /**
     * @param string $eventName
     * @param callable $listener
     * @return int|null
     */
    public function getListenerPriority($eventName, $listener)
    {
        return null;
    }

    /**
     * @param string|null $eventName
     * @return bool
     */
    public function hasListeners($eventName = null)
    {
        return false;
    }
}
