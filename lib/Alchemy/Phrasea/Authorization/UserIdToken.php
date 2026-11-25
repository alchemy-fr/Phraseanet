<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Authorization;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

/**
 * Token of an authenticated Phraseanet user, identified by its id only.
 */
class UserIdToken extends AbstractToken
{
    private $userId;

    public function __construct($userId, array $roles = [])
    {
        parent::__construct($roles);
        $this->userId = (string) $userId;
    }

    public function getUserIdentifier(): string
    {
        return $this->userId;
    }

    public function __serialize(): array
    {
        return [$this->userId, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->userId, $parentData] = $data;
        parent::__unserialize($parentData);
    }
}
