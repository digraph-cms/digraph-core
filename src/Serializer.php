<?php

namespace DigraphCMS;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use Throwable;

SerializableClosure::setSecretKey(Config::secret());

class Serializer
{

    /**
     * Of note in the way this works is that strings are pre-wrapped in a call
     * that will unserialize them. This is useful internally because it means
     * serialized cache values can simply be inserted into an executable PHP
     * file as a variable assignment, and they will be opcached, allowing cache
     * items that are accessed multiple times to have huge performance boosts.
     * 
     * @param mixed $value 
     * @return string 
     * @throws Throwable 
     */
    public static function serialize(mixed $value): string
    {
        try {
            // first try to use built-in serialization, so that if closure
            // serialization is ever supported we get it for free
            return sprintf(
                '\\unserialize(\'%s\')',
                str_replace('\'', '\\\'', serialize($value))
            );
        } catch (Throwable $th) {
            if ($value instanceof Closure) {
                // if the unserializable value is a closure, serialize it as a
                // SerializableClosure with a call get the closure
                return sprintf(
                    '\\unserialize(\'%s\')->getClosure()',
                    str_replace('\'', '\\\'', serialize(new SerializableClosure($value)))
                );
            } else {
                // otherwise serialize it as a closure that returns the value,
                // this way we're still leaning on the Symfony serializer
                return sprintf(
                    '\\call_user_func(\\unserialize(\'%s\')->getClosure())',
                    str_replace('\'', '\\\'', serialize(new SerializableClosure(function () use ($value) {
                        return $value;
                    })))
                );
            }
        }
    }

    /**
     * DANGER ZONE. Can evaluate arbitrary code. Should only be passed things
     * that were definitely created using Digraph::serialize() or Serializer::serialize()
     *
     * @param string $value
     * @return mixed
     */
    public static function unserialize($value)
    {
        try {
            return eval('return ' . $value . ';');
        } catch (Throwable $th) {
            throw new Exception("Failed to unserialize value: " . $value, $value, $th);
        }
    }
}
