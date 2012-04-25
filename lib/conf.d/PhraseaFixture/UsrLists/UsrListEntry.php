<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

namespace PhraseaFixture\UsrLists;

use Doctrine\ORM\EntityManager;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class UsrListEntry extends ListAbstract implements FixtureInterface
{
    /**
     *
     * @var \Entities\UsrListEntry
     */
    public $entry;

    public function load(ObjectManager $manager)
    {
        $entry = new \Entities\UsrListEntry();

        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new basket');
        }

        $list = $this->getReference('one-list');

        $entry->setUser($this->user);
        $entry->setList($list);

        /* @var $list \Entities\UsrList */
        $list->addUsrListEntry($entry);

        $manager->persist($entry);
        $manager->flush();

        $this->entry = $entry;

        $this->addReference('one-entry', $entry);
    }
}
