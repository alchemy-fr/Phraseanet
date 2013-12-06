<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class PropertyAccessTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider provideGetData
     */
    public function testGet($conf, $props, $expected, $default)
    {
        $propAccess = new PropertyAccess(new ArrayConf($conf));
        $this->assertSame($expected, $propAccess->get($props, $default));
    }

    /**
     * @dataProvider provideHasData
     */
    public function testHas($conf, $props, $expected)
    {
        $propAccess = new PropertyAccess(new ArrayConf($conf));
        $this->assertSame($expected, $propAccess->has($props));
    }

    /**
     * @dataProvider provideSetData
     */
    public function testSet($conf, $props, $value, $expectedConf)
    {
        $conf = new ArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($value, $propAccess->set($props, $value));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    /**
     * @dataProvider provideRemoveData
     */
    public function testRemove($conf, $props, $expectedReturnValue, $expectedConf)
    {
        $conf = new ArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($expectedReturnValue, $propAccess->remove($props));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    /**
     * @dataProvider provideMergeData
     */
    public function testMerge($conf, $props, $value, $expectedReturnValue, $expectedConf)
    {
        $conf = new ArrayConf($conf);
        $propAccess = new PropertyAccess($conf);
        $this->assertSame($expectedReturnValue, $propAccess->merge($props, $value));
        $this->assertSame($expectedConf, $conf->getConfig());
    }

    public function provideMergeData()
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

    public function provideGetData()
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

    public function provideHasData()
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

    public function provideSetData()
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

    public function provideRemoveData()
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

class ArrayConf implements ConfigurationInterface
{
    private $conf;

    public function __construct(array $conf)
    {
        $this->conf = $conf;
    }

    public function getConfig()
    {
        return $this->conf;
    }

    public function setConfig(array $config)
    {
        $this->conf = $config;
    }

    public function offsetGet($offset)
    {
        throw new \Exception('not implemented');
    }

    public function offsetSet($offset, $value)
    {
        throw new \Exception('not implemented');
    }

    public function offsetUnset($offset)
    {
        throw new \Exception('not implemented');
    }

    public function offsetExists($offset)
    {
        throw new \Exception('not implemented');
    }

    public function setDefault($name)
    {
        throw new \Exception('not implemented');
    }

    public function initialize()
    {
        throw new \Exception('not implemented');
    }

    public function delete()
    {
        throw new \Exception('not implemented');
    }

    public function isSetup()
    {
        throw new \Exception('not implemented');
    }

    public function compileAndWrite()
    {
        throw new \Exception('not implemented');
    }
}
