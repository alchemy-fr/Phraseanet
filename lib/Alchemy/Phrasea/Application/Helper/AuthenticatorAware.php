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

use Alchemy\Phrasea\Authentication\Authenticator;
use Alchemy\Phrasea\Model\Entities\User;

trait AuthenticatorAware
{
    /** @var Authenticator|callable */
    private $authenticator;

    /**
     * @param Authenticator|callable $authenticator
     * @return $this
     */
    public function setAuthenticator($authenticator)
    {
        if (!$authenticator instanceof \appbox && !is_callable($authenticator)) {
            throw new \InvalidArgumentException(sprintf(
                '%s expects parameter to be a "%s" instance or a callable, got "%s".',
                __METHOD__,
                \appbox::class,
                is_object($authenticator) ? get_class($authenticator) : gettype($authenticator)
            ));
        }
        $this->authenticator = $authenticator;

        return $this;
    }

    /**
     * @return Authenticator
     */
    public function getAuthenticator()
    {
        if ($this->authenticator instanceof Authenticator) {
            return $this->authenticator;
        }

        if (null === $this->authenticator && $this instanceof \Pimple && $this->offsetExists('authentication')) {
            $this->authenticator = function () {
                return $this['authentication'];
            };
        }

        if (null === $this->authenticator) {
            throw new \LogicException(Authenticator::class . ' instance or locator was not set');
        }

        $instance = call_user_func($this->authenticator);
        if (!$instance instanceof Authenticator) {
            throw new \LogicException(sprintf(
                'Expects locator to return instance of "%s", got "%s"',
                Authenticator::class,
                is_object($instance) ? get_class($instance) : gettype($instance)
            ));
        }
        $this->authenticator = $instance;

        return $this->authenticator;
    }

    /**
     * @return User|null
     */
    public function getAuthenticatedUser()
    {
        return $this->getAuthenticator()->getUser();
    }
}
