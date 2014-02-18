<?php

class ValidationParticipantRepositoryTest extends \PhraseanetAuthenticatedTestCase
{

    public function testFindNotConfirmedAndNotRemindedParticipants()
    {
        $em = self::$DI['app']['EM'];
        $repo = $em->getRepository('Phraseanet:ValidationParticipant');
        /* @var $repo Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository */
        $expireDate = new \DateTime('+8 days');
        $participants = $repo->findNotConfirmedAndNotRemindedParticipantsByExpireDate($expireDate);
        $this->assertEquals(3, count($participants));
    }
}
