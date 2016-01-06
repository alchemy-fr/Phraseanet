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

use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

abstract class BaseVoter implements VoterInterface
{
    private $supportedAttributes;
    private $supportedClasses;

    /** @var UserRepository */
    protected $userRepository;

    /**
     * @param UserRepository $userRepository
     * @param array          $attributes
     * @param string|array   $supportedClasses
     */
    public function __construct(UserRepository $userRepository, array $attributes, $supportedClasses)
    {
        $this->supportedAttributes = $attributes;
        $this->supportedClasses = is_array($supportedClasses) ? $supportedClasses : [$supportedClasses];

        if (!is_callable([$this, 'isGranted'])) {
            throw new \LogicException('Subclasses should implement a "isGranted" method');
        }
        $this->userRepository = $userRepository;
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, $this->supportedAttributes);
    }

    public function supportsClass($class)
    {
        foreach ($this->supportedClasses as $supportedClass) {
            if ($class == $supportedClass || is_subclass_of($class, $supportedClass)) {
                return true;
            }
        }

        return false;
    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !$this->supportsClass(get_class($object))) {
            return self::ACCESS_ABSTAIN;
        }

        $user = (ctype_digit($token->getUser())) ? $this->userRepository->find((int) $token->getUser()) : null;

        foreach ($attributes as $attribute) {
            $attribute = strtolower($attribute);

            if ($this->supportsAttribute($attribute)) {
                $isGranted = call_user_func([$this, 'isGranted'], $attribute, $object, $user);

                return $isGranted ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }

    /**
     * @param string    $attribute
     * @param object    $object
     * @param User|null $user
     * @return bool
     */
    //abstract protected function isGranted($attribute, $object, User $user = null);
}
