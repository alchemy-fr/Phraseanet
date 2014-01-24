<?php

namespace Alchemy\Tests\Phrasea\Registration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\ProvidersCollection;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\RegistrationDemand;
use Alchemy\Phrasea\Registration\RegistrationManager;

class RegistrationManagerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider registrationConfigProvider
     */
    public function testRegistrationIsEnable($data, $value)
    {
        $service = $this->getMockBuilder('Alchemy\Phrasea\Registration\RegistrationManager')
            ->setConstructorArgs([self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']])
            ->setMethods(['getRegistrationInformations'])
            ->getMock();

        $service->expects($this->once())->method('getRegistrationInformations')->will($this->returnValue($data));
        $this->assertEquals($value, $service->isRegistrationEnabled());
    }

    /**
     * @dataProvider databoxXmlConfiguration
     */
    public function testIsRegistrationEnabledForDatabox($data, $value)
    {
        $service = new RegistrationManager(self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);

        $mock = $this->getMockBuilder('\databox')
            ->disableOriginalConstructor()
            ->setMethods(['get_sxml_structure'])
            ->getMock();

        $mock->expects($this->once())->method('get_sxml_structure')->will($this->returnValue($data));
        $this->assertEquals($value, $service->isRegistrationEnabledForDatabox($mock));
    }

    /**
     * @dataProvider collectionXmlConfiguration
     */
    public function testIsRegistrationEnabledForCollection($data, $value)
    {
        $service = new RegistrationManager(self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);

        $mock = $this->getMockBuilder('\collection')
            ->disableOriginalConstructor()
            ->setMethods(['get_prefs'])
            ->getMock();

        $mock->expects($this->once())->method('get_prefs')->will($this->returnValue($data));
        $this->assertEquals($value, $service->isRegistrationEnabledForCollection($mock));
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testGetRegistrationInformationsWithUserData($data, $type, $value)
    {
        $service = $this->getMockBuilder('Alchemy\Phrasea\Registration\RegistrationManager')
            ->setConstructorArgs([self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']])
            ->setMethods(['getRegistrationDemandsForUser'])
            ->getMock();

        $service->expects($this->once())->method('getRegistrationDemandsForUser')->will($this->returnValue($data));

        $rs = $service->getRegistrationInformations(4);

        $databox = current(self::$DI['app']['phraseanet.appbox']->get_databoxes());
        $collection = current($databox->get_collections());

        $this->assertEquals($value, count($rs[$databox->get_sbas_id()]['demands']['by-type'][$type]));
        $this->assertNotNull($rs[$databox->get_sbas_id()]['demands']['by-collection'][$collection->get_base_id()]);
    }

    public function databoxXmlConfiguration()
    {
        $xmlInscript =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<record><caninscript>1</caninscript>1</record>
XML;
        $xmlNoInscript =
            <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<record><caninscript>0</caninscript>1</record>
XML;
        $xmlNoInscriptEmpty =
    <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<record><caninscript></caninscript></record>
XML;

        return [
            [simplexml_load_string($xmlInscript), true],
            [simplexml_load_string($xmlNoInscript), false],
            [simplexml_load_string($xmlNoInscriptEmpty), false],
        ];
    }

    public function collectionXmlConfiguration()
    {
        $xmlInscript =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript>1</caninscript>1</baseprefs>
XML;
        $xmlNoInscript =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript>0</caninscript>1</baseprefs>
XML;
        $xmlNoInscriptEmpty =
<<<XML
<?xml version="1.0" encoding="UTF-8"?>
<baseprefs><caninscript></caninscript></baseprefs>
XML;

        return [
            [$xmlInscript, true],
            [$xmlNoInscript, false],
            [$xmlNoInscriptEmpty, false],
        ];
    }

    public function registrationConfigProvider()
    {
        $enableDataboxConfig = [
            [
                [
                    'config' => [
                        'db-name'       => 'a_db_name',
                        'cgu'           => null,
                        'cgu-release'   => null,
                        'can-register'  => true,
                        'collections'   => [],
                    ]
                ]
            ],
            false
        ];

        $enableCollectionConfig = [
            [
                [
                    'config' => [
                        'db-name'       => 'a_db_name',
                        'cgu'           => null,
                        'cgu-release'   => null,
                        'can-register'  => false,
                        'collections'   => [
                            [
                                'coll-name'     => 'a_coll_name',
                                'can-register'  => true,
                                'cgu'           => null,
                                'cgu-release'   => null,
                                'demand'        => null
                            ]
                        ],
                    ]
                ]
            ],
            true
        ];

        $nothingEnabledConfig = [
            [
                [
                    'config' => [
                        'db-name'       => 'a_db_name',
                        'cgu'           => null,
                        'cgu-release'   => null,
                        'can-register'  => false,
                        'collections'   => [
                            [
                                'coll-name'     => 'a_coll_name',
                                'can-register'  => false,
                                'cgu'           => null,
                                'cgu-release'   => null,
                                'demand'        => null
                            ],
                            [
                                'coll-name'     => 'an_other_coll_name',
                                'can-register'  => false,
                                'cgu'           => null,
                                'cgu-release'   => null,
                                'demand'        => null
                            ]
                        ],
                    ]
                ]
            ],
            false
        ];

        $noCollectionEnabledButBaseEnabledConfig = [
            [
                [
                    'config' => [
                        'db-name'       => 'a_db_name',
                        'cgu'           => null,
                        'cgu-release'   => null,
                        'can-register'  => true,
                        'collections'   => [
                            [
                                'coll-name'     => 'a_coll_name',
                                'can-register'  => false,
                                'cgu'           => null,
                                'cgu-release'   => null,
                                'demand'        => null
                            ],
                            [
                                'coll-name'     => 'an_other_coll_name',
                                'can-register'  => false,
                                'cgu'           => null,
                                'cgu-release'   => null,
                                'demand'        => null
                            ]
                        ],
                    ]
                ]
            ],
            false
        ];

        return [
            $enableDataboxConfig,
            $enableCollectionConfig,
            $nothingEnabledConfig,
            $noCollectionEnabledButBaseEnabledConfig
        ];
    }

    public function userDataProvider()
    {
        $pendingDemand = new RegistrationDemand();
        $pendingDemand->setBaseId(1);
        $pendingDemand->setUser(3);
        $pendingDemand->setPending(true);
        $pendingDemand->setRejected(false);

        $rejectedDemand = new RegistrationDemand();
        $rejectedDemand->setBaseId(1);
        $rejectedDemand->setUser(3);
        $rejectedDemand->setPending(true);
        $rejectedDemand->setRejected(true);

        $databox = current((new \appbox(new Application()))->get_databoxes());
        $collection = current($databox->get_collections());

        $noLimitedPendingDemand = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'toto',
                        'active' => true,
                        'time-limited' => false,
                        'in-time' => null,
                        'demand' => $pendingDemand
                    ]
                ]
            ],
            'pending',
            1
        ];


        $rejectedDemand = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'titi',
                        'active' => true,
                        'time-limited' => false,
                        'in-time' => null,
                        'demand' => $rejectedDemand
                    ]
                ]
            ],
            'rejected',
            1
        ];

        $noActiveDemand = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => 1,
                        'db-name' => 'tutu',
                        'active' => false,
                        'time-limited' => false,
                        'in-time' => null,
                        'demand' => $pendingDemand
                    ]
                ]
            ],
            'inactive',
            1
        ];

        $limitedActiveIntimePendingDemand = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'tata',
                        'active' => true,
                        'time-limited' => true,
                        'in-time' => true,
                        'demand' => $pendingDemand
                    ]
                ]
            ],
            'in-time',
            1
        ];

        $limitedActiveOutdatedPendingDemand = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'toutou',
                        'active' => true,
                        'time-limited' => true,
                        'in-time' => false,
                        'demand' => $pendingDemand
                    ]
                ]
            ],
            'out-time',
            1
        ];

        return [
            $noLimitedPendingDemand,
            $noActiveDemand,
            $limitedActiveIntimePendingDemand,
            $limitedActiveOutdatedPendingDemand,
            $rejectedDemand
        ];
    }
}
