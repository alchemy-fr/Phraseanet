<?php

namespace Alchemy\Tests\Phrasea\Core\Service\Border;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Service\Border\BorderManager;

class BorderManagerTest extends \PhraseanetPHPUnitAbstract
{

    /**
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::getDriver
     */
    public function testGetDriver()
    {
        $options = array(
            'enabled'  => true,
            'checkers' => array(
                'type'    => '',
                'options' => array()
            )
        );

        $manager = new BorderManager(self::$DI['app'], $options);

        $this->assertInstanceOf('\\Alchemy\\Phrasea\\Border\\Manager', $manager->getDriver());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::getType
     */
    public function testGetType()
    {
        $options = array(
            'enabled'  => true,
            'checkers' => array(
                'type'    => '',
                'options' => array()
            )
        );
        $manager = new BorderManager(self::$DI['app'], $options);

        $this->assertEquals('border', $manager->getType());
    }

    /**
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::getMandatoryOptions
     */
    public function testGetMandatoryOptions()
    {
        $manager = new BorderManager(self::$DI['app'], array('enabled'  => true, 'checkers' => array()));
        $this->assertInternalType('array', $manager->getMandatoryOptions());
    }

    /**
     * @dataProvider getVariousWrongOptions
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::init
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::getUnregisteredCheckers
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::addUnregisteredCheck
     */
    public function testGetUnregisteredCheckers($options)
    {
        $manager = new BorderManager(self::$DI['app'], $options);

        $this->assertEquals(1, count($manager->getUnregisteredCheckers()));
    }

    /**
     * @dataProvider getVariousOptions
     * @covers Alchemy\Phrasea\Core\Service\Border\BorderManager::init
     */
    public function testGetGoodConf($options)
    {
        $manager = new BorderManager(self::$DI['app'], $options);

        $this->assertEquals(0, count($manager->getUnregisteredCheckers()));
    }

    public function getVariousWrongOptions()
    {
        list($databox, $collection) = $this->getDataboxAndCollection();

        return array(
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'bidule',
                            'options' => array(),
                        ),
                    )
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'options' => array(),
                        ),
                    )
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'enabled' => false,
                            'options' => array(),
                        ),
                    )
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'options' => array(),
                            'databoxes' => array(0),
                        ),
                    ),
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'options' => array(),
                            'collections' => array(0),
                        ),
                    ),
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'options' => array(),
                            'databoxes' => array($databox->get_sbas_id()),
                            'collections' => array($collection->get_base_id()),
                        ),
                    ),
                )
            ),
        );
    }

    public function getDataboxAndCollection()
    {
        $app = new Application('test');
        $databox = $collection = null;

        foreach ($app['phraseanet.appbox']->get_databoxes() as $db) {
            if (!$databox) {
                $databox = $db;
            }
            if (!$collection) {
                foreach ($db->get_collections() as $coll) {
                    $collection = $coll;
                    break;
                }
            }
        }

        return array($databox, $collection);
    }

    public function getVariousOptions()
    {
        list($databox, $collection) = $this->getDataboxAndCollection();

        return array(
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'enabled' => true,
                            'options' => array(),
                            'databoxes' => array($databox->get_sbas_id()),
                        ),
                    ),
                )
            ),
            array(
                array(
                    'enabled'  => true,
                    'checkers' => array(
                        array(
                            'type'    => 'Checker\\UUID',
                            'enabled' => true,
                            'options' => array(),
                            'collections' => array($collection->get_base_id()),
                        ),
                    ),
                )
            ),
        );
    }
}
