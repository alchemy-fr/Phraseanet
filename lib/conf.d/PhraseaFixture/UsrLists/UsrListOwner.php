<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\UsrLists;

use Alchemy\Phrasea\Model\Entities\UsrListOwner as UsrListOwnerEntity;
use Alchemy\Phrasea\Model\Entities\StoryWZ;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UsrListOwner extends ListAbstract implements FixtureInterface
{
    /**
     * @var StoryWZ
     */
    public $owner;

    public function load(ObjectManager $manager)
    {
        $owner = new UsrListOwnerEntity();

        $owner->setRole(UsrListOwnerEntity::ROLE_ADMIN);

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
