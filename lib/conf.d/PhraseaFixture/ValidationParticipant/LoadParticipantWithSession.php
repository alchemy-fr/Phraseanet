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

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadParticipantWithSession extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var \Entities\ValidationParticipant
     */
    public $validationParticipant;

    /**
     *
     * @var \Entities\ValidationSession
     */
    private $session;

    public function load(ObjectManager $manager)
    {
        $validationParticipant = new \Entities\ValidationParticipant();

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

    public function setSession(\Entities\ValidationSession $session)
    {
        $this->session = $session;
    }
}
