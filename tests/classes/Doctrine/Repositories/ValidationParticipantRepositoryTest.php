<?php

class ValidationParticipantRepositoryTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

    public function testFindNotConfirmedAndNotRemindedParticipants()
    {
        $this->insertOneValidationBasket([
            'expires' => new \DateTime('+1 days')
        ]);

        $em = self::$DI['app']['EM'];
        $repo = $em->getRepository('\Alchemy\Phrasea\Model\Entities\ValidationParticipant');
        /* @var $repo Alchemy\Phrasea\Model\Repositories\ValidationParticipantRepository */
        $expireDate = new \DateTime('+2 days');
        $participants = $repo->findNotConfirmedAndNotRemindedParticipantsByExpireDate($expireDate);
        $this->assertEquals(1, count($participants));
    }
}
