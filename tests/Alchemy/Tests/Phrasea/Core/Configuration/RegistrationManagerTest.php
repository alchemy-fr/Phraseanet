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
        $collection = current($databox->get_collections());

        $this->assertEquals($value, count($rs[$databox->get_sbas_id()]['registrations-by-type'][$type]));
    }

    public function userDataProvider()
    {
        $pendingRegistration = new Registration();
        $pendingRegistration->setBaseId(1);
        $pendingRegistration->setUser(new User());
        $pendingRegistration->setPending(true);
        $pendingRegistration->setRejected(false);

        $rejectedRegistration = new Registration();
        $rejectedRegistration->setBaseId(1);
        $rejectedRegistration->setUser(new User());
        $rejectedRegistration->setPending(false);
        $rejectedRegistration->setRejected(true);

        $acceptedRegistration = new Registration();
        $acceptedRegistration->setBaseId(1);
        $acceptedRegistration->setUser(new User());
        $acceptedRegistration->setPending(false);
        $acceptedRegistration->setRejected(false);

        $registrations = [
            'pending'  => $pendingRegistration,
            'accepted' => $acceptedRegistration,
            'rejected' => $rejectedRegistration,
            'inactive' => null
        ];

        $databox = current((new \appbox(new Application(Application::ENV_TEST)))->get_databoxes());
        $collection = current($databox->get_collections());

        $tests = [];

        // ====== no access in basusr : result comes only from "registration" ======
        foreach($registrations as $k=>$registration) {
            //        pending, accepted, rejected, inactive
            $tests[] = [
                [
                    $databox->get_sbas_id() => [
                        $collection->get_base_id() => [
                            'base-id'      => $collection->get_base_id(),
                            'db-name'      => 'toto',
                            'active'       => null,
                            'time-limited' => null,
                            'in-time'      => null,
                            'registration' => $registration
                        ]
                    ]
                ],
                $k,
                1
            ];
        }

        // ======= rights with time limit : registration does not matter =======
        foreach($registrations as $registration) {
            $tests[] = [
                [
                    $databox->get_sbas_id() => [
                        $collection->get_base_id() => [
                            'base-id'      => $collection->get_base_id(),
                            'db-name'      => 'toto',
                            'active'       => true,
                            'time-limited' => true,
                            'in-time'      => true,
                            'registration' => $registration
                        ]
                    ]
                ],
                'in-time',
                1
            ];
            $tests[] = [
                [
                    $databox->get_sbas_id() => [
                        $collection->get_base_id() => [
                            'base-id'      => $collection->get_base_id(),
                            'db-name'      => 'toto',
                            'active'       => true,
                            'time-limited' => true,
                            'in-time'      => false,
                            'registration' => $registration
                        ]
                    ]
                ],
                'out-dated',
                1
            ];
        }

        // ======= rights, no time limit : registration may matter =======
        foreach($registrations as $k=>$registration) {
            //        pending, accepted, rejected, inactive
            $tests[] = [
                [
                    $databox->get_sbas_id() => [
                        $collection->get_base_id() => [
                            'base-id'      => $collection->get_base_id(),
                            'db-name'      => 'toto',
                            'active'       => true,
                            'time-limited' => false,
                            'in-time'      => null,
                            'registration' => $registration
                        ]
                    ]
                ],
                $k=='accepted' ? 'accepted' : 'active',
                1
            ];
            $tests[] = [
                [
                    $databox->get_sbas_id() => [
                        $collection->get_base_id() => [
                            'base-id'      => $collection->get_base_id(),
                            'db-name'      => 'toto',
                            'active'       => false,
                            'time-limited' => false,
                            'in-time'      => null,
                            'registration' => $registration
                        ]
                    ]
                ],
                $k=='rejected' ? 'rejected' : 'inactive',
                1
            ];
        }

        return $tests;
    }
}
