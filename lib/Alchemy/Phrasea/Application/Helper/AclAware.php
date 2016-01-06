<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Application\Helper;

use Alchemy\Phrasea\Authentication\ACLProvider;
use Alchemy\Phrasea\Model\Entities\User;

trait AclAware
{
    /** @var ACLProvider */
    private $aclProvider;

    /**
     * @param ACLProvider|callable $provider
     * @return $this
     */
    public function setAclProvider($provider)
    {
        if (!$provider instanceof ACLProvider && !is_callable($provider)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects parameter to be a "%s" instance or a callable, got "%s".',
                __METHOD__,
                ACLProvider::class,
                is_object($provider) ? get_class($provider) : gettype($provider)
            ));
        }
        $this->aclProvider = $provider;

        return $this;
    }

    /**
     * @return ACLProvider
     */
    public function getAclProvider()
    {
        if ($this->aclProvider instanceof ACLProvider) {
            return $this->aclProvider;
        }

        if (null === $this->aclProvider && $this instanceof \Pimple && $this->offsetExists('acl')) {
            $this->aclProvider = function () {
                return $this['acl'];
            };
        }

        if (null === $this->aclProvider) {
            throw new \LogicException(ACLProvider::class . ' instance or locator was not set');
        }

        $instance = call_user_func($this->aclProvider);
        if (!$instance instanceof ACLProvider) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                ACLProvider::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->aclProvider = $instance;

        return $this->aclProvider;
    }

    /**
     * @param User $user
     * @return \ACL
     */
    public function getAclForUser(User $user)
    {
        return $this->getAclProvider()->get($user);
    }
}
