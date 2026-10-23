<?php

#[\PHPUnit\Framework\Attributes\Group('functional')]
#[\PHPUnit\Framework\Attributes\Group('legacy')]
class BasketParticipantRepositoryTest extends \PhraseanetTestCase
{

    public function testFindNotConfirmedAndNotRemindedParticipants()
    {
        $em = self::$DI['app']['orm.em'];
        $repo = $em->getRepository('Phraseanet:BasketParticipant');
        /* @var $repo Alchemy\Phrasea\Model\Repositories\BasketParticipantRepository */
        $participants = $repo->findNotConfirmedAndNotRemindedParticipantsByTimeLeftPercent(20, new \DateTime());
        $this->assertEquals(3, count($participants));
    }
}
