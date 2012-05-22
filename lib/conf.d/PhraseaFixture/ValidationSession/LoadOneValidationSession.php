<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\ValidationSession;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneValidationSession extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var \Entities\ValidationSession
     */
    public $validationSession;

    public function load(ObjectManager $manager)
    {
        $validationSession = new \Entities\ValidationSession();

        $validationSession->setBasket(
            $this->getReference('one-basket') // load the one-basket stored reference
        );

        $validationSession->setDescription('Une description au hasard');
        $validationSession->setName('Un nom de validation');

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
