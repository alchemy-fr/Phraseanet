<?php

/**
 * @group functional
 * @group legacy
 */
class ValidationParticipantRepositoryTest extends \PhraseanetTestCase
{

    public function testFindNotConfirmedAndNotRemindedParticipants()
    {
        $em = self::$DI['app']['orm.em'];
        $repo = $em->getRepository('Phraseanet:ValidationParticipant');
        /* @var $repo Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository */
        $participants = $repo->findNotConfirmedAndNotRemindedParticipantsByTimeLeftPercent(20, new \DateTime());
        $this->assertEquals(3, count($participants));
    }
}
