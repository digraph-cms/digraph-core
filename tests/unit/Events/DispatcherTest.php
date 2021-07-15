<?php

namespace Events;

use DigraphCMS\Events\Dispatcher;

class DispatcherTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        Dispatcher::reset();
    }

    protected function _after()
    {
    }

    public function testEventListeners()
    {
        $ran = false;
        $id = Dispatcher::addEventListener(
            'onTestEvent',
            function ($value) use (&$ran) {
                $ran = $value;
            }
        );
        $this->assertFalse($ran);
        Dispatcher::dispatchEvent('onTestEvent', [true]);
        $this->assertTrue($ran);
        // now remove
        Dispatcher::removeEventListener($id);
        Dispatcher::dispatchEvent('onTestEvent', [false]);
        $this->assertTrue($ran);
    }

    public function testStaticSubscribers()
    {
        Dispatcher::addSubscriber(StaticSubscriber::class);
        Dispatcher::dispatchEvent('onTestEvent', ['first event ran']);
        $this->assertEquals('first event ran', StaticSubscriber::$value);
        Dispatcher::removeSubscriber(StaticSubscriber::class);
        Dispatcher::dispatchEvent('onTestEvent', ['second event ran']);
        $this->assertEquals('first event ran', StaticSubscriber::$value);
    }

    public function testObjectSubscribers()
    {
        $subscriber = new ObjectSubscriber();
        Dispatcher::addSubscriber($subscriber);
        Dispatcher::dispatchEvent('onTestEvent', ['first event ran']);
        $this->assertEquals('first event ran', $subscriber->value);
        Dispatcher::removeSubscriber($subscriber);
        Dispatcher::dispatchEvent('onTestEvent', ['second event ran']);
        $this->assertEquals('first event ran', $subscriber->value);
    }
}

class ObjectSubscriber
{
    public $value;
    public function onTestEvent($value)
    {
        $this->value = $value;
    }
}

class StaticSubscriber
{
    public static $value;
    public static function onTestEvent($value)
    {
        self::$value = $value;
    }
}
