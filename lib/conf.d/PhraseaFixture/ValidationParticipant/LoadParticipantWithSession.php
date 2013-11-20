<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\ValidationParticipant;

use Alchemy\Phrasea\Model\Entities\ValidationParticipant;
use Alchemy\Phrasea\Model\Entities\ValidationSession;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadParticipantWithSession extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     * @var Participant
     */
    public $validationParticipant;

    /**
     *
     * @var ValidationSession
     */
    private $session;

    public function load(ObjectManager $manager)
    {
        $validationParticipant = new ValidationParticipant();

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new validation Session');
        }
        $validationParticipant->setUser($this->user);

        if (null === $this->session) {
            throw new \LogicException('Attach a session to the current participant');
        }
        $validationParticipant->setSession($this->session);

        $manager->persist($validationParticipant);
        $manager->flush();

        $this->validationParticipant = $validationParticipant;
    }

    public function setSession(ValidationSession $session)
    {
        $this->session = $session;
    }
}
