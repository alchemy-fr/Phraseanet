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

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManager;

class AuthorizationChecker
{
    /** @var AccessDecisionManager */
    private $accessDecisionManager;
    /** @var TokenInterface */
    private $token;

    public function __construct(AccessDecisionManager $accessDecisionManager, TokenInterface $token)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->token = $token;
    }

    public function isGranted($attributes, $object = null)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes];
        }

        return $this->accessDecisionManager->decide($this->token, $attributes, $object);
    }
}
