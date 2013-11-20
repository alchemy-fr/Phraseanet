<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\ValidationSession;

use Alchemy\Phrasea\Model\Entities\ValidationSession as ValidationSessionEntity;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadOneValidationSession extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var ValidationSessionEntity
     */
    public $validationSession;

    public function load(ObjectManager $manager)
    {
        $validationSession = new ValidationSessionEntity();

        $validationSession->setBasket(
            $this->getReference('one-basket') // load the one-basket stored reference
        );

        $expires = new \DateTime();
        $expires->modify('+1 week');
        $validationSession->setExpires($expires);

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new validation Session');
        }
        $validationSession->setInitiator($this->user);

        $manager->persist($validationSession);
        $manager->flush();

        $this->validationSession = $validationSession;

        $this->addReference('one-validation-session', $validationSession);
    }
}
