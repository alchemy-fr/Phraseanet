<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\ValidationParticipant;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneParticipant extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var \Entities\ValidationParticipant
     */
    public $validationParticipant;

    public function load(ObjectManager $manager)
    {
        $validationParticipant = new \Entities\ValidationParticipant();

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new validation Session');
        }
        $validationParticipant->setParticipant($this->user);

        $validationParticipant->setSession(
            $this->getReference('one-validation-session')
        );

        $manager->persist($validationParticipant);
        $manager->flush();

        $this->validationParticipant = $validationParticipant;

        $this->addReference('one-validation-participant', $validationParticipant);
    }
}
