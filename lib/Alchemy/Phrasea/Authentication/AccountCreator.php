<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Model\Entities\User;
use RandomLib\Generator;

class AccountCreator
{
    private $appbox;
    private $enabled;
    private $random;
    private $templates;

    public function __construct(Generator $random, \appbox $appbox, $enabled, $templates)
    {
        $this->appbox = $appbox;
        $this->enabled = $enabled;
        $this->random = $random;
        $this->templates = $templates;
    }

    /**
     * Returns the default templates
     *
     * @return array
     */
    public function getTemplates()
    {
        return $this->templates;
    }

    /**
     * @return Boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Creates an account
     *
     * @param Application $app       The application
     * @param string      $id        The base for user login
     * @param string      $email     The email
     * @param array       $templates Some extra templates to apply with the ones of this creator
     *
     * @return User
     *
     * @throws RuntimeException         In case the AccountCreator is disabled
     * @throws InvalidArgumentException In case a user with the same email already exists
     */
    public function create(Application $app, $id, $email = null, array $templates = [])
    {
        if (!$this->enabled) {
            throw new RuntimeException('Account creator is disabled');
        }

        $login = $id;
        $n = 1;

        if (null !== $email && null !== $app['repo.users']->findByEmail($email)) {
            throw new InvalidArgumentException('Provided email already exist in account base.');
        }

        while (null !== $app['repo.users']->findByLogin($login)) {
            $login = $id . '#' . $n;
            $n++;
        }

        $user = $app['manipulator.user']->createUser($login, $this->random->generateString(128), $email);

        $base_ids = [];
        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach ($databox->get_collections() as $collection) {
                $base_ids[] = $collection->get_base_id();
            }
        }

        foreach (array_merge($this->templates, $templates) as $template) {
            $app->getAclForUser($user)->apply_model($template, $base_ids);
        }

        return $user;
    }
}
