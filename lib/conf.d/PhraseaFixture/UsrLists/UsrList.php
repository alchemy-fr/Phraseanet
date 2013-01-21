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

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrList extends ListAbstract implements FixtureInterface
{
    /**
     *
     * @var \Entities\UsrList
     */
    public $list;

    public function load(ObjectManager $manager)
    {
        $list = new \Entities\UsrList();

        $owner = $this->getReference('one-listowner');

        $list->setName('new list');
        $list->addUsrListOwner($owner);

        /* @var $owner \Entities\UsrListOwner */
        $owner->setList($list);

        $manager->persist($list);
        $manager->merge($owner);
        $manager->flush();

        $this->list = $list;

        $this->addReference('one-list', $list);
    }
}
