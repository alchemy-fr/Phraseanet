<?php

namespace Alchemy\Tests\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Core\Configuration\RegistrationManager;

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

        $service = new RegistrationManager($mockAppbox, self::$DI['app']['manipulator.registration']->getRepository(), self::$DI['app']['locale']);
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

        $databox = current(self::$DI['app']['phraseanet.appbox']->get_databoxes());
        $collection = current($databox->get_collections());

        $this->assertEquals($value, count($rs[$databox->get_sbas_id()]['registrations']['by-type'][$type]));
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
        $rejectedRegistration->setPending(true);
        $rejectedRegistration->setRejected(true);

        $databox = current((new \appbox(new Application()))->get_databoxes());
        $collection = current($databox->get_collections());

        $noLimitedPendingRegistration = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'toto',
                        'active' => true,
                        'time-limited' => false,
                        'in-time' => null,
                        'registration' => $pendingRegistration
                    ]
                ]
            ],
            'pending',
            1
        ];

        $rejectedRegistration = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'titi',
                        'active' => true,
                        'time-limited' => false,
                        'in-time' => null,
                        'registration' => $rejectedRegistration
                    ]
                ]
            ],
            'rejected',
            1
        ];

        $noActiveRegistration = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => 1,
                        'db-name' => 'tutu',
                        'active' => false,
                        'time-limited' => false,
                        'in-time' => null,
                        'registration' => $pendingRegistration
                    ]
                ]
            ],
            'inactive',
            1
        ];

        $limitedActiveIntimePendingRegistration = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'tata',
                        'active' => true,
                        'time-limited' => true,
                        'in-time' => true,
                        'registration' => $pendingRegistration
                    ]
                ]
            ],
            'in-time',
            1
        ];

        $limitedActiveOutdatedPendingRegistration = [
            [
                $databox->get_sbas_id() => [
                    $collection->get_base_id() => [
                        'base-id' => $collection->get_base_id(),
                        'db-name' => 'toutou',
                        'active' => true,
                        'time-limited' => true,
                        'in-time' => false,
                        'registration' => $pendingRegistration
                    ]
                ]
            ],
            'out-time',
            1
        ];

        return [
            $noLimitedPendingRegistration,
            $noActiveRegistration,
            $limitedActiveIntimePendingRegistration,
            $limitedActiveOutdatedPendingRegistration,
            $rejectedRegistration
        ];
    }
}
