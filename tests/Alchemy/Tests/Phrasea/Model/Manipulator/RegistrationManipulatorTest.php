<?php

namespace Alchemy\Tests\Phrasea\Registration;

use Alchemy\Phrasea\Model\Entities\Registration;
use Alchemy\Phrasea\Model\Manipulator\RegistrationManipulator;

/**
 * @group functional
 * @group legacy
 */
class RegistrationManipulatorTest extends \PhraseanetTestCase
{
    public function testCreateRegistration()
    {
        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $registration = $service->createRegistration(self::$DI['user'], self::$DI['collection']);

        $this->assertInstanceOf('Alchemy\Phrasea\Model\Entities\Registration', $registration);
        $this->assertEquals(self::$DI['collection']->get_base_id(), $registration->getBaseId());
        $this->assertEquals(self::$DI['user']->getId(), $registration->getUser()->getId());
    }

    public function testRejectRegistration()
    {
        $registration = self::$DI['registration_1'];

        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $service->rejectRegistration($registration);

        $this->assertFalse($registration->isPending());
        $this->assertTrue($registration->isRejected());
    }

    public function testAcceptRegistration()
    {
        $registration = self::$DI['registration_1'];

        $aclMock = $this->getMockBuilder('ACL')->disableOriginalConstructor()->getMock();
        $aclMock->expects($this->once())->method('give_access_to_sbas')->with($this->equalTo([self::$DI['collection']->get_sbas_id()]));
        $aclMock->expects($this->once())->method('give_access_to_base')->with($this->equalTo([self::$DI['collection']->get_base_id()]));
        $aclMock->expects($this->once())->method('update_rights_to_base')->with($this->equalTo(self::$DI['collection']->get_base_id()), $this->equalTo([
            \ACL::CANPUTINALBUM   => true,
            \ACL::CANDWNLDHD      => true,
            \ACL::NOWATERMARK     => false,
            \ACL::CANDWNLDPREVIEW => true,
            \ACL::ACTIF           => true,
        ]));

        $aclProviderMock = $this->getMockBuilder('Alchemy\Phrasea\Authentication\ACLProvider')->disableOriginalConstructor()->getMock();
        $aclProviderMock->expects($this->any())->method('get')->with($this->isInstanceOf('Alchemy\Phrasea\Model\Entities\User'))->will($this->returnvalue($aclMock));

        self::$DI['app']['acl'] = $aclProviderMock;

        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $service->acceptRegistration($registration, true, false);
    }

    public function testDeleteRegistrationForUser()
    {
        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $qb = self::$DI['app']['repo.registrations']->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')
            ->where($qb->expr()->eq('r.user', ':user'))
            ->setParameter(':user', self::$DI['user_alt1']->getId())
            ->getQuery()
            ->getSingleScalarResult();
        $service->deleteUserRegistrations(self::$DI['user_alt1'], [self::$DI['collection']]);
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
    }

    public function testDeleteOldRegistrations()
    {
        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $qb = self::$DI['app']['repo.registrations']->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')->getQuery()->getSingleScalarResult();
        $service->deleteOldRegistrations();
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
    }

    public function testDeleteRegistrationOnCollection()
    {
        $service = new RegistrationManipulator(self::$DI['app'], self::$DI['app']['orm.em'], self::$DI['app']['acl'], self::$DI['app']['phraseanet.appbox'], self::$DI['app']['repo.registrations']);
        $qb = self::$DI['app']['repo.registrations']->createQueryBuilder('r');
        $nbRegistrationBefore = $qb->select('COUNT(r)')->getQuery()->getSingleScalarResult();
        $service->deleteRegistrationsOnCollection(self::$DI['collection']);
        $nbRegistrationAfter = $qb->getQuery()->getSingleScalarResult();
        $this->assertGreaterThan($nbRegistrationAfter, $nbRegistrationBefore);
    }
}
