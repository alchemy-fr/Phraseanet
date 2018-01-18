<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Symfony\Component\Yaml\Yaml;

class YamlCountingParse extends Yaml
{
    private static $parseCount = 0;
    /** @var callable */
    private static $parseCallable;

    public static function reset()
    {
        self::$parseCount = 0;
        self::$parseCallable = null;
    }

    public static function getParseCount()
    {
        return self::$parseCount;
    }

    public static function setParseBehavior(callable $callable)
    {
        self::$parseCallable = $callable;
    }

    public static function parse($input, $exceptionOnInvalidType = false, $objectSupport = false, $objectForMap = false)
    {
        self::$parseCount++;

        if (null === self::$parseCallable) {
            return parent::parse($input, $exceptionOnInvalidType, $objectSupport, $objectForMap);
        }

        return call_user_func(self::$parseCallable, $input, $exceptionOnInvalidType, $objectSupport, $objectForMap);
    }
}
