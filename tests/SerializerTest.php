<?php

namespace DigraphCMS;

use PHPUnit\Framework\TestCase;

class SerializerTest extends TestCase
{
    protected function apply($data)
    {
        $serialized = Serializer::serialize($data);
        $unserialized = Serializer::unserialize($serialized);
        return $unserialized;
    }

    public function testSerializingScalars()
    {
        $this->assertEquals(5, static::apply(5));
        $this->assertEquals(5.5, static::apply(5.5));
        $this->assertEquals('hello', static::apply('hello'));
        $this->assertEquals(true, static::apply(true));
        $this->assertEquals(false, static::apply(false));
        $this->assertEquals(null, static::apply(null));
        $this->assertEquals([1, 2, 3], static::apply([1, 2, 3]));
    }

    public function testSerializingStringWithQuotes()
    {
        $this->assertEquals('\'hello\'', static::apply('\'hello\''));
        $this->assertEquals('"hello"', static::apply('"hello"'));
    }

    public function testSerializingEmptyString()
    {
        $this->assertEquals('', static::apply(''));
    }

    public function testSerializingEmptyObject()
    {
        $obj = new \stdClass();
        $this->assertEquals($obj, static::apply($obj));
    }

    public function testSerializingNestedArrays()
    {
        $array = [1, [2, 3], [[4, 5], 6]];
        $this->assertEquals($array, static::apply($array));
    }

    public function testSerializingMixedArrays()
    {
        $array = [1, 'string', [3.5, true], ['nested' => [false, null]]];
        $this->assertEquals($array, static::apply($array));
    }

    public function testSerializingAssociativeArray()
    {
        $array = ['key1' => 'value1', 'key2' => 'value2'];
        $this->assertEquals($array, static::apply($array));
    }

    public function testSerializingLargeArray()
    {
        $array = range(1, 10000);
        $this->assertEquals($array, static::apply($array));
    }

    public function testSerializingObjects()
    {
        $obj = new \stdClass();
        $obj->a = 5;
        $obj->b = 'hello';
        $obj->c = [1, 2, 3];
        $this->assertEquals($obj, static::apply($obj));
    }

    public function testSerializingObjectWithNestedObjects()
    {
        $obj1 = new \stdClass();
        $obj2 = new \stdClass();
        $obj1->nested = $obj2;
        $obj2->value = 'test';
        $this->assertEquals($obj1, static::apply($obj1));
    }

    public function testSerializingClosures()
    {
        // test a normal anonymous function
        $closure = function ($a, $b) {
            return $a + $b;
        };
        $this->assertEquals(5, static::apply($closure)(2, 3));
        // test an arrow function
        $closure = fn ($a, $b) => $a + $b;
        $this->assertEquals(5, static::apply($closure)(2, 3));
    }

    public function testSerializingClosureWithThisCalls()
    {
        // test a normal anonymous function
        $closure = function ($a, $b) {
            return $this->callable_method($a, $b);
        };
        $this->assertEquals(5, static::apply($closure)(2, 3));
        // test an arrow function
        $closure = fn ($a, $b) => $this->callable_method($a, $b);
        $this->assertEquals(5, static::apply($closure)(2, 3));
        // test with array callable syntax
        $this->assertEquals(5, static::apply([$this, 'callable_method'])(2, 3));
        // test with first-class callable syntax
        $this->assertEquals(5, static::apply($this->callable_method(...))(2, 3));
    }

    public function testSerializingObjectContainingClosure()
    {
        $obj = new \stdClass();
        $obj->closure = function ($a, $b) {
            return $a + $b;
        };
        $this->assertEquals(
            5,
            call_user_func(
                static::apply($obj)->closure,
                2,
                3
            )
        );
    }

    public function testSerializingArrayContainingClosure()
    {
        $array = [
            'closure' => function ($a, $b) {
                return $a + $b;
            }
        ];
        $this->assertEquals(
            5,
            call_user_func(
                static::apply($array)['closure'],
                2,
                3
            )
        );
    }

    public function testSerializingWithStaticMethodCalls()
    {
        // test a normal anonymous function
        $closure = function ($a, $b) {
            return static::static_callable_method($a, $b);
        };
        $this->assertEquals(5, static::apply($closure)(2, 3));
        // test an arrow function
        $closure = fn ($a, $b) => static::static_callable_method($a, $b);
        $this->assertEquals(5, static::apply($closure)(2, 3));
        // test with array callable syntax
        $this->assertEquals(5, static::apply([static::class, 'static_callable_method'])(2, 3));
        // test with first-class callable syntax
        $this->assertEquals(5, static::apply(static::static_callable_method(...))(2, 3));
    }

    /**
     * Called by various closures (and hopefully first-class callable syntax in the future)
     * to ensure that $this context works properly
     */
    protected function callable_method($a, $b)
    {
        return $a + $b;
    }

    /**
     * Called by various closures (and hopefully first-class callable syntax in the future)
     * to ensure that static context works properly
     */
    protected static function static_callable_method($a, $b)
    {
        return $a + $b;
    }
}
