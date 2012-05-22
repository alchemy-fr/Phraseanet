<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\UsrLists;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrListOwner extends ListAbstract implements FixtureInterface
{
    /**
     *
     * @var \Entities\StoryWZ
     */
    public $owner;

    public function load(ObjectManager $manager)
    {
        $owner = new \Entities\UsrListOwner();

        $owner->setRole(\Entities\UsrListOwner::ROLE_ADMIN);

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new basket');
        }

        $owner->setUser($this->user);

        $manager->persist($owner);
        $manager->flush();

        $this->owner = $owner;

        $this->addReference('one-listowner', $owner);
    }
}
