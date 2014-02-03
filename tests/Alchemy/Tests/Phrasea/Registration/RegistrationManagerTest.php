<?php

namespace Alchemy\Tests\Phrasea\Registration;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Registration\RegistrationManager;

class RegistrationManagerTest extends \PhraseanetTestCase
{
    public function testCreateRegistration()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\Registration'));
        $em->expects($this->once())->method('flush');

        $service = new RegistrationManager($em, self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);

        $registration = $service->createRegistration(self::$DI['user']->get_id(), self::$DI['collection']->get_base_id());

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\Registration', $registration);
        $this->assertEquals(self::$DI['collection']->get_base_id(), $registration->getBaseId());
        $this->assertEquals(self::$DI['user']->get_id(), $registration->getUser());

        return $registration;
    }

    /**
     * @depends testCreateRegistration
     */
    public function testRejectRegistration($registration)
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('persist')->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\Registration'));
        $em->expects($this->once())->method('flush');

        $service = new RegistrationManager($em, self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);

        $service->rejectRegistration($registration);
        $this->assertFalse($registration->isPending());
        $this->assertTrue($registration->isRejected());

        return $registration;
    }

    /**
     * @depends testCreateRegistration
     */
    public function testAcceptRegistration($registration)
    {
        $aclMock = $this->getMockBuilder('ACL')
            ->disableOriginalConstructor()
            ->getMock();
        $aclMock->expects($this->once())->method('give_access_to_sbas')->with($this->equalTo([self::$DI['collection']->get_sbas_id()]));
        $aclMock->expects($this->once())->method('give_access_to_base')->with($this->equalTo([self::$DI['collection']->get_base_id()]));
        $aclMock->expects($this->once())->method('update_rights_to_base')->with($this->equalTo(self::$DI['collection']->get_base_id()), $this->equalTo([
            'canputinalbum'   => '1',
            'candwnldhd'      => '1',
            'nowatermark'     => '0',
            'candwnldpreview' => '1',
            'actif'           => '1',
        ]));
        $aclProviderMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $aclProviderMock->expects($this->any())->method('get')->with($this->equalTo(self::$DI['user']))->will($this->returnvalue($aclMock));
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('remove')->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\Registration'));
        $em->expects($this->once())->method('flush');

        $service = new RegistrationManager($em, self::$DI['app']['phraseanet.appbox'], $aclProviderMock);
        $service->acceptRegistration($registration, self::$DI['user'], self::$DI['collection'], true, false);
    }

    public function testDeleteRegistrationForUser()
    {
        $service = new RegistrationManager(self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);
        $qb = $service->getRepository()->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->setParameter(':user', self::$DI['user_alt1']->get_id())
            ->getQuery()
            ->getSingleScalarResult();
        $service->deleteRegistrationsForUser(self::$DI['user_alt1']->get_id(), [self::$DI['collection']->get_base_id()]);
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
    }

    public function testDeleteOldRegistrations()
    {
        $service = new RegistrationManager(self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);
        $qb = $service->getRepository()->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')
            ->getQuery()
            ->getSingleScalarResult();
        $service->deleteOldRegistrations();
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
    }

    public function testDeleteRegistrationOnCollection()
    {
        $service = new RegistrationManager(self::$DI['app']['EM'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);
        $qb = $service->getRepository()->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')
            ->getQuery()
            ->getSingleScalarResult();
        $service->deleteRegistrationsOnCollection(self::$DI['collection']->get_base_id());
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
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
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->once())->method('getRepository')->will($this->returnValue($repoMock));

        $service = new RegistrationManager($em, self::$DI['app']['phraseanet.appbox'], self::$DI['app']['acl']);

        $rs = $service->getRegistrationSummary(4);

        $databox = current(self::$DI['app']['phraseanet.appbox']->get_databoxes());
        $collection = current($databox->get_collections());

        $this->assertEquals($value, count($rs[$databox->get_sbas_id()]['registrations']['by-type'][$type]));
        $this->assertNotNull($rs[$databox->get_sbas_id()]['registrations']['by-collection'][$collection->get_base_id()]);
    }

    public function userDataProvider()
    {
        $pendingRegistration = new Registration();
        $pendingRegistration->setBaseId(1);
        $pendingRegistration->setUser(3);
        $pendingRegistration->setPending(true);
        $pendingRegistration->setRejected(false);

        $rejectedRegistration = new Registration();
        $rejectedRegistration->setBaseId(1);
        $rejectedRegistration->setUser(3);
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
