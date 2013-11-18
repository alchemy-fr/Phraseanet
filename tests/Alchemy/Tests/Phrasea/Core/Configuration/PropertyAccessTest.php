<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\ConfigurationInterface;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class PropertyAccessTest extends \PHPUnit_Framework_TestCase
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
        $conf = array(
            'key1' => array('subkey1' => 'value1'),
        );

        return array(
            array($conf, 'key1', array('subkey2' => 'valuetest'), array('subkey1' => 'value1', 'subkey2' => 'valuetest'), array('key1' => array('subkey1' => 'value1', 'subkey2' => 'valuetest'))),
            array($conf, 'key1', array('subkey1' => 'valuetest'), array('subkey1' => 'valuetest'), array('key1' => array('subkey1' => 'valuetest'))),
            array($conf, 'key2', array('subkey1' => 'valuetest'), array('subkey1' => 'valuetest'), array('key1' => array('subkey1' => 'value1'), 'key2' => array('subkey1' => 'valuetest'))),
            array($conf, array('key1', 'subkey2'), array('subkey3' => 'valuetest'), array('subkey3' => 'valuetest'), array('key1' => array('subkey1' => 'value1', 'subkey2' => array('subkey3' => 'valuetest')))),
        );
    }

    public function provideGetData()
    {
        $conf = array(
            'key1' => array('subkey1' => 'value1'),
            'key2' => array('subkey1' => 'value1', 'subkey2' => array('subkey3' => 'value3')),
        );

        return array(
            array($conf, 'key1', array('subkey1' => 'value1'), null),
            array($conf, 'key1', array('subkey1' => 'value1'), 'ladada'),
            array($conf, 'key2', array('subkey1' => 'value1', 'subkey2' => array('subkey3' => 'value3')), null),
            array($conf, 'key2', array('subkey1' => 'value1', 'subkey2' => array('subkey3' => 'value3')), 'ladada'),
            array($conf, array('key2', 'subkey1'), 'value1', null),
            array($conf, array('key2', 'subkey1'), 'value1', 'ladada'),
            array($conf, array('key2', 'subkey2', 'subkey3'), 'value3', null),
            array($conf, array('key2', 'subkey2', 'subkey3'), 'value3', 'ladada'),
            array($conf, array('key2', 'subkey2', 'subkey4'), null, null),
            array($conf, array('key2', 'subkey2', 'subkey4'), 'ladada', 'ladada'),
            array($conf, array('key', 'subkey', 'subkey'), null, null),
            array($conf, array('key', 'subkey', 'subkey'), 'ladada', 'ladada'),
            array($conf, 'key3', null, null),
            array($conf, 'key3', 'ladada', 'ladada'),
        );
    }

    public function provideHasData()
    {
        $conf = array(
            'key1' => array('subkey1' => 'value1'),
            'key2' => array('subkey1' => 'value1', 'subkey2' => array('subkey3' => 'value3')),
        );

        return array(
            array($conf, 'key1', true),
            array($conf, 'key2', true),
            array($conf, array('key2', 'subkey1'), true),
            array($conf, array('key2', 'subkey2', 'subkey3'), true),
            array($conf, array('key2', 'subkey2', 'subkey4'), false),
            array($conf, array('key', 'subkey', 'subkey'), false),
            array($conf, 'key3', false),
        );
    }

    public function provideSetData()
    {
        $conf = array(
            'key1' => array('subkey1' => 'value1'),
        );

        return array(
            array($conf, 'key1', 'valuetest', array('key1' => 'valuetest')),
            array($conf, 'key2', 'valuetest', array('key1' => array('subkey1' => 'value1'), 'key2' => 'valuetest')),
            array($conf, array('key2', 'subkey1'), 'valuetest', array('key1' => array('subkey1' => 'value1'), 'key2' => array('subkey1' => 'valuetest'))),
            array($conf, array('key1', 'subkey2'), 'valuetest', array('key1' => array('subkey1' => 'value1', 'subkey2' => 'valuetest'))),
        );
    }

    public function provideRemoveData()
    {
        $conf = array(
            'key1' => array('subkey1' => 'value1'),
        );

        return array(
            array($conf, 'key1', array('subkey1' => 'value1'), array()),
            array($conf, array('key1', 'subkey1'), 'value1', array('key1' => array())),
            array($conf, array('key1', 'subkey2'), null, $conf),
            array($conf, 'key2', null, $conf),
        );
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
