<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\Lazaret;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class LoadOneFile extends \PhraseaFixture\AbstractWZ implements FixtureInterface
{
    /**
     *
     * @var \Entities\LazaretFile
     */
    public $file;
    public $collectionId;

    public function load(ObjectManager $manager)
    {
        if (null === $this->user) {
            throw new \LogicException('Fill a user to store a new basket');
        }

        $lazaretSession = new \Entities\LazaretSession();
        $lazaretSession->setUsrId($this->user->get_id());
        $lazaretSession->setUpdated(new \DateTime('now'));
        $lazaretSession->setCreated(new \DateTime('-1 day'));

        $lazaretFile = new \Entities\LazaretFile();
        $lazaretFile->setOriginalName('test');
        $lazaretFile->setPathname('test\test');
        $lazaretFile->setBaseId($this->collectionId);
        $lazaretFile->setSession($lazaretSession);
        $lazaretFile->setSha256('3191af52748620e0d0da50a7b8020e118bd8b8a0845120b0bb');
        $lazaretFile->setUuid('7b8ef0e3-dc8f-4b66-9e2f-bd049d175124');
        $lazaretFile->setCreated(new \DateTime('now'));
        $lazaretFile->setUpdated(new \DateTime('-1 day'));

        $manager->persist($lazaretFile);
        $manager->flush();

        $this->file = $lazaretFile;

        $this->addReference('one-lazaret-file', $lazaretFile);
    }

    public function setCollectionId($collectionId)
    {
        $this->collectionId = (int) $collectionId;
    }
}
