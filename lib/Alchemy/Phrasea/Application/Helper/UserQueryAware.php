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

trait UserQueryAware
{
    private $userQueryFactory;

    /**
     * Set User Query Factory
     *
     * @param callable $factory
     * @return $this
     */
    public function setUserQueryFactory(callable $factory)
    {
        $this->userQueryFactory = $factory;

        return $this;
    }

    /**
     * @return callable
     */
    public function getUserQueryFactory()
    {
        if (!is_callable($this->userQueryFactory)) {
            throw new \LogicException('User Query factory was not set');
        }

        return $this->userQueryFactory;
    }

    /**
     * @return \User_Query
     */
    public function createUserQuery()
    {
        $userQuery = call_user_func($this->getUserQueryFactory());
        if (!$userQuery instanceof \User_Query) {
            throw new \LogicException(sprintf(
                'User Query factory does not create %s instance, got "%s" instead',
                \User_Query::class,
                is_object($userQuery) ? get_class($userQuery) : gettype($userQuery)
            ));
        }

        return $userQuery;
    }
}
