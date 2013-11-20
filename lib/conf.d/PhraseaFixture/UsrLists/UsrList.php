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
use Alchemy\Phrasea\Model\Entities\UsrListOwner as UsrListOwnerEntity;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class UsrList extends ListAbstract implements FixtureInterface
{
    /**
     *
     * @var UsrList
     */
    public $list;

    public function load(ObjectManager $manager)
    {
        $list = new UsrListEntity();

        $owner = $this->getReference('one-listowner');

        $list->setName('new list');
        $list->addOwner($owner);

        /* @var $owner UsrListOwnerEntity */
        $owner->setList($list);

        $manager->persist($list);
        $manager->merge($owner);
        $manager->flush();

        $this->list = $list;

        $this->addReference('one-list', $list);
    }
}
