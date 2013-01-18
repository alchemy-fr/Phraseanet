<?php

class ValidationParticipantRepositoryTest extends PhraseanetPHPUnitAuthenticatedAbstract
{

    public function testFindNotConfirmedAndNotRemindedParticipants()
    {
        $this->insertOneValidationBasket(array(
            'expires' => new \DateTime('+1 days')
        ));

        $em = self::$DI['app']['EM'];
        $repo = $em->getRepository('\Entities\ValidationParticipant');
        /* @var $repo \Repositories\ValidationParticipantRepository */
        $expireDate = new \DateTime('+2 days');
        $participants = $repo->findNotConfirmedAndNotRemindedParticipantsByExpireDate($expireDate);
        $this->assertEquals(1, count($participants));
    }
}
