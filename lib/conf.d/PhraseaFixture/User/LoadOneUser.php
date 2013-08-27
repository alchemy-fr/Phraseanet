<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhraseaFixture\User;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Entities\User;

class LoadOneUser extends AbstractFixture implements FixtureInterface
{
    /**
     *
     * @var User
     */
    public $user;

    public function load(ObjectManager $manager)
    {
        if (null === $this->user) {
            throw new \Exception('Please set a user to persist');
        }

        $manager->persist($this->user);
        $manager->flush();

        $this->addReference('one-user', $this->user);
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
    }
}
