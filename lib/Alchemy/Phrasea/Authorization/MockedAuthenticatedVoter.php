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

use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class MockedAuthenticatedVoter implements VoterInterface
{
    const IS_AUTHENTICATED_FULLY = 'IS_AUTHENTICATED_FULLY';
    const IS_AUTHENTICATED_ANONYMOUSLY = 'IS_AUTHENTICATED_ANONYMOUSLY';

    public function supportsAttribute($attribute)
    {
        return null !== $attribute && (self::IS_AUTHENTICATED_FULLY === $attribute || self::IS_AUTHENTICATED_ANONYMOUSLY === $attribute);
    }

    public function supportsClass($class)
    {
        return true;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;

            if (self::IS_AUTHENTICATED_FULLY === $attribute
                && $token instanceof PreAuthenticatedToken
            ) {
                return VoterInterface::ACCESS_GRANTED;
            }

            if (self::IS_AUTHENTICATED_ANONYMOUSLY === $attribute
                && $token instanceof AnonymousToken
            ) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }
}
