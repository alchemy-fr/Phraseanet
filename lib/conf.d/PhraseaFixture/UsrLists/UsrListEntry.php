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

use Alchemy\Phrasea\Model\Entities\UsrList as UsrListEntity;
use Alchemy\Phrasea\Model\Entities\UsrListEntry as UsrListEntryEntity;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UsrListEntry extends ListAbstract implements FixtureInterface
{
    /**
     * @var UsrListEntry
     */
    public $entry;

    public function load(ObjectManager $manager)
    {
        $entry = new UsrListEntryEntity();

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new basket');
        }

        $list = $this->getReference('one-list');

        $entry->setUser($this->user);
        $entry->setList($list);

        /* @var $list UsrListEntity */
        $list->addEntrie($entry);

        $manager->persist($entry);
        $manager->flush();

        $this->entry = $entry;

        $this->addReference('one-entry', $entry);
    }
}
