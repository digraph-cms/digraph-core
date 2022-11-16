<?php

namespace DigraphCMS\SafeContent;

use HTMLPurifier;
use HTMLPurifier_Config;

class Sanitizer
{
    public static function full(string $input): string
    {
        static $purifier;
        if (!$purifier) {
            $config = HTMLPurifier_Config::createDefault();
            $purifier = new HTMLPurifier($config);
        }
        return $purifier->purify(strip_tags($input));
    }
}
