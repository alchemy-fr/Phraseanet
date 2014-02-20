<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication;

use Alchemy\Phrasea\Model\Entities\User;
use Silex\Application;

class ACLProvider
{
    /**
     * An array cache for ACL's.
     *
     * @var array
     */
    private static $cache = [];

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Gets ACL for user.
     *
     * @param User $user
     *
     * @return \ACL
     */
    public function get(User $user)
    {
        if (null !== $acl = $this->fetchFromCache($user)) {
            return $acl;
        }

        return $this->fetch($user);
    }

    /**
     * Purges ACL cache
     */
    public static function purge()
    {
        self::$cache = [];
    }

    /**
     * Fetchs ACL from cache for users.
     *
     * @param User $user
     *
     * @return null || \ACL
     */
    private function fetchFromCache(User $user)
    {
        return $this->hasCache($user) ? self::$cache[$user->getId()] : null;
    }

    /**
     * Tells whether ACL for user is already cached.
     *
     * @param User $user
     *
     * @return boolean
     */
    private function hasCache(User $user)
    {
        return isset(self::$cache[$user->getId()]);
    }

    /**
     * Saves user's ACL in cache and returns it.
     *
     * @param User $user
     *
     * @return \ACL
     */
    private function fetch(User $user)
    {
        return self::$cache[$user->getId()] = new \ACL($user, $this->app);
    }
}
