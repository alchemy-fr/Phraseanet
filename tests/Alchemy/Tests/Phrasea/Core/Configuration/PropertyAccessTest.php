<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Tests\Phrasea\MockArrayConf;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class PropertyAccessTest extends \PhraseanetTestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider('provideGetData')]
    public function testGet($conf, $props, $expected, $default)
    {
        $propAccess = new PropertyAccess(new MockArrayConf($conf));
        $this->assertSame($expected, $propAccess->get($props, $default));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideHasData')]
    public function testHas($conf, $props, $expected)
    {
        $propAccess = new PropertyAccess(new MockArrayConf($conf));
        $this->assertSame($expected, $propAccess->has($props));
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideSetData')]
    public function testSet($conf, $props, $value, $expectedConf)
    {
        $conf = new MockArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($value, $propAccess->set($props, $value));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideRemoveData')]
    public function testRemove($conf, $props, $expectedReturnValue, $expectedConf)
    {
        $conf = new MockArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($expectedReturnValue, $propAccess->remove($props));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('provideMergeData')]
    public function testMerge($conf, $props, $value, $expectedReturnValue, $expectedConf)
    {
        $conf = new MockArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($expectedReturnValue, $propAccess->merge($props, $value));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    public static function provideMergeData()
    {
        $conf = [
            'key1' => ['subkey1' => 'value1'],
        ];

        return [
            [$conf, 'key1', ['subkey2' => 'valuetest'], ['subkey1' => 'value1', 'subkey2' => 'valuetest'], ['key1' => ['subkey1' => 'value1', 'subkey2' => 'valuetest']]],
            [$conf, 'key1', ['subkey1' => 'valuetest'], ['subkey1' => 'valuetest'], ['key1' => ['subkey1' => 'valuetest']]],
            [$conf, 'key2', ['subkey1' => 'valuetest'], ['subkey1' => 'valuetest'], ['key1' => ['subkey1' => 'value1'], 'key2' => ['subkey1' => 'valuetest']]],
            [$conf, ['key1', 'subkey2'], ['subkey3' => 'valuetest'], ['subkey3' => 'valuetest'], ['key1' => ['subkey1' => 'value1', 'subkey2' => ['subkey3' => 'valuetest']]]],
        ];
    }

    public static function provideGetData()
    {
        $conf = [
            'key1' => ['subkey1' => 'value1'],
            'key2' => ['subkey1' => 'value1', 'subkey2' => ['subkey3' => 'value3']],
        ];

        return [
            [$conf, 'key1', ['subkey1' => 'value1'], null],
            [$conf, 'key1', ['subkey1' => 'value1'], 'ladada'],
            [$conf, 'key2', ['subkey1' => 'value1', 'subkey2' => ['subkey3' => 'value3']], null],
            [$conf, 'key2', ['subkey1' => 'value1', 'subkey2' => ['subkey3' => 'value3']], 'ladada'],
            [$conf, ['key2', 'subkey1'], 'value1', null],
            [$conf, ['key2', 'subkey1'], 'value1', 'ladada'],
            [$conf, ['key2', 'subkey2', 'subkey3'], 'value3', null],
            [$conf, ['key2', 'subkey2', 'subkey3'], 'value3', 'ladada'],
            [$conf, ['key2', 'subkey2', 'subkey4'], null, null],
            [$conf, ['key2', 'subkey2', 'subkey4'], 'ladada', 'ladada'],
            [$conf, ['key', 'subkey', 'subkey'], null, null],
            [$conf, ['key', 'subkey', 'subkey'], 'ladada', 'ladada'],
            [$conf, 'key3', null, null],
            [$conf, 'key3', 'ladada', 'ladada'],
        ];
    }

    public static function provideHasData()
    {
        $conf = [
            'key1' => ['subkey1' => 'value1'],
            'key2' => ['subkey1' => 'value1', 'subkey2' => ['subkey3' => 'value3']],
        ];

        return [
            [$conf, 'key1', true],
            [$conf, 'key2', true],
            [$conf, ['key2', 'subkey1'], true],
            [$conf, ['key2', 'subkey2', 'subkey3'], true],
            [$conf, ['key2', 'subkey2', 'subkey4'], false],
            [$conf, ['key', 'subkey', 'subkey'], false],
            [$conf, 'key3', false],
        ];
    }

    public static function provideSetData()
    {
        $conf = [
            'key1' => ['subkey1' => 'value1'],
        ];

        return [
            [$conf, 'key1', 'valuetest', ['key1' => 'valuetest']],
            [$conf, 'key2', 'valuetest', ['key1' => ['subkey1' => 'value1'], 'key2' => 'valuetest']],
            [$conf, ['key2', 'subkey1'], 'valuetest', ['key1' => ['subkey1' => 'value1'], 'key2' => ['subkey1' => 'valuetest']]],
            [$conf, ['key1', 'subkey2'], 'valuetest', ['key1' => ['subkey1' => 'value1', 'subkey2' => 'valuetest']]],
        ];
    }

    public static function provideRemoveData()
    {
        $conf = [
            'key1' => ['subkey1' => 'value1'],
        ];

        return [
            [$conf, 'key1', ['subkey1' => 'value1'], []],
            [$conf, ['key1', 'subkey1'], 'value1', ['key1' => []]],
            [$conf, ['key1', 'subkey2'], null, $conf],
            [$conf, 'key2', null, $conf],
        ];
    }
}
