<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;

/**
 * @group functional
 * @group legacy
 */
class RegistrationManagerTest extends \PhraseanetTestCase
{
    /**
     * @dataProvider getRegistrationProvider
     */
    public function testRegistrationIsEnabled($enabledOnColl, $expected)
    {
        $mockColl = $this->getMockBuilder('\collection')
            ->disableOriginalConstructor()
            ->getMock();

        $mockColl->expects($this->once())->method('isRegistrationEnabled')->will($this->returnValue($enabledOnColl));

        $mockDatabox = $this->getMockBuilder('\databox')
            ->disableOriginalConstructor()
            ->getMock();

        $mockAppbox = $this->getMockBuilder('\appbox')
            ->disableOriginalConstructor()
            ->getMock();

        $mockColl->expects($this->once())->method('isRegistrationEnabled')->will($this->returnValue(false));

        $mockDatabox->expects($this->once())->method('get_collections')->will($this->returnValue([$mockColl]));
        $mockAppbox->expects($this->once())->method('get_databoxes')->will($this->returnValue([$mockDatabox]));

        $service = new RegistrationManager($mockAppbox, self::$DI['app']['repo.registrations'], self::$DI['app']['locale']);
        $this->assertEquals($expected, $service->isRegistrationEnabled());
    }

    public function getRegistrationProvider()
    {
        return [
            [false, false],
            [true, true],
        ];
    }

    /**
     * @dataProvider userDataProvider
     */
    public function testGetRegistrationSummaryWithUserData($data, $type, $value)
    {
        $repoMock = $this->getMockBuilder('Alchemy\Phrasea\Model\Repositories\RegistrationRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getRegistrationsSummaryForUser'])
            ->getMock();
        $repoMock->expects($this->once())->method('getRegistrationsSummaryForUser')->will($this->returnValue($data));

        $service = new RegistrationManager(self::$DI['app']['phraseanet.appbox'], $repoMock, self::$DI['app']['locale']);

        $rs = $service->getRegistrationSummary(self::$DI['user']);

        $databox = current(self::$DI['app']->getDataboxes());

        if($value != count($rs[$databox->get_sbas_id()]['registrations-by-type'][$type])) {
            printf("whazaa\n");
        }
        $this->assertEquals($value, count($rs[$databox->get_sbas_id()]['registrations-by-type'][$type]));
    }

    public function userDataProvider()
    {
        $databox = current((new \appbox(new Application(Application::ENV_TEST)))->get_databoxes());
        /** @var \collection $collection */
        $collection = current($databox->get_collections());

        $sbas_id = $databox->get_sbas_id();
        $base_id = $collection->get_base_id();

        $pendingRegistration = new Registration();
        $pendingRegistration->setBaseId($base_id);
        $pendingRegistration->setUser(new User());
        $pendingRegistration->setPending(true);
        $pendingRegistration->setRejected(false);

        $rejectedRegistration = new Registration();
        $rejectedRegistration->setBaseId($base_id);
        $rejectedRegistration->setUser(new User());
        $rejectedRegistration->setPending(false);
        $rejectedRegistration->setRejected(true);

        $acceptedRegistration = new Registration();
        $acceptedRegistration->setBaseId($base_id);
        $acceptedRegistration->setUser(new User());
        $acceptedRegistration->setPending(false);
        $acceptedRegistration->setRejected(false);

        $registrations = [
            // special type when a user has no access nor demand on a registrable-or-not collection
    //        $collection->isRegistrationEnabled() ? 'registrable' : 'inactive'
    //                      => null,
            // "normal" types
            'pending'     => $pendingRegistration,
            'rejected'    => $rejectedRegistration,
            'accepted'    => $acceptedRegistration
        ];

        $ret = [];

        foreach($registrations as $label=>$reg) {
            // if access is already active (true) or inactive (false), or time-limited, registration is nonsense
            $ret[] = [
                [
                    $sbas_id => [
                        $base_id => [
                            'base-id' => $base_id,
                            'db-name' => 'active_reg_'.$label,
                            'active' => true,       // known...
                            'time-limited' => false,
                            'in-time' => null,
                            'registration' => $reg
                        ]
                    ]
                ],
                'active',   // ...result !
                1
            ];
            $ret[] = [
                [
                    $sbas_id => [
                        $base_id => [
                            'base-id' => $base_id,
                            'db-name' => 'inactive_reg_'.$label,
                            'active' => false,       // known...
                            'time-limited' => false,
                            'in-time' => null,
                            'registration' => $reg
                        ]
                    ]
                ],
                $collection->isRegistrationEnabled() ? 'registrable' : 'inactive',   // ...result !
                1
            ];
            $ret[] = [
                [
                    $sbas_id => [
                        $base_id => [
                            'base-id' => $base_id,
                            'db-name' => 'limited_in_reg_'.$label,
                            'active' => false,       // known...
                            'time-limited' => true,
                            'in-time' => true,
                            'registration' => $reg
                        ]
                    ]
                ],
                'in-time',   // ...result !
                1
            ];
            $ret[] = [
                [
                    $sbas_id => [
                        $base_id => [
                            'base-id' => $base_id,
                            'db-name' => 'limited_out_reg_'.$label,
                            'active' => false,       // known...
                            'time-limited' => true,
                            'in-time' => false,
                            'registration' => $reg
                        ]
                    ]
                ],
                'out-time',   // ...result !
                1
            ];

            // if no access, registration cares
            $ret[] = [
                [
                    $sbas_id => [
                        $base_id => [
                            'base-id' => $base_id,
                            'db-name' => 'noaccess_reg_'.$label,
                            'active' => null,
                            'time-limited' => false,
                            'in-time' => null,
                            'registration' => $reg
                        ]
                    ]
                ],
                $label,
                1
            ];
        }

        return $ret;
    }
}
