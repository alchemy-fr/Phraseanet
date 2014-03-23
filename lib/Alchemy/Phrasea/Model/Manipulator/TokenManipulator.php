<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Doctrine\Common\Persistence\ObjectManager;
use RandomLib\Generator;

class TokenManipulator implements ManipulatorInterface
{
    const LETTERS_AND_NUMBERS = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

    const TYPE_FEED_ENTRY = 'FEED_ENTRY';
    const TYPE_PASSWORD = 'password';
    const TYPE_ACCOUNT_UNLOCK = 'account-unlock';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_MAIL_DOWNLOAD = 'mail-download';
    const TYPE_EMAIL = 'email';
    const TYPE_EMAIL_RESET = 'email-reset';
    const TYPE_VIEW = 'view';
    const TYPE_VALIDATE = 'validate';
    const TYPE_RSS = 'rss';

    /** @var Objectmanager */
    private $om;
    private $random;
    private $repository;

    public function __construct(ObjectManager $om, Generator $random, TokenRepository $repository)
    {
        $this->om = $om;
        $this->random = $random;
        $this->repository = $repository;
    }

    /**
     * @param User|null      $user
     * @param string         $type
     * @param \DateTime|null $expiration
     * @param mixed|null     $data
     *
     * @return Token
     */
    public function create(User $user = null, $type, \DateTime $expiration = null, $data = null)
    {
        $this->removeExpiredTokens();

        $n = 0;
        do {
            if ($n++ > 1024) {
                throw new \RuntimeException('Unable to create a token.');
            }
            $value = $this->random->generateString(32, self::LETTERS_AND_NUMBERS);
            $found = null !== $this->om->getRepository('Phraseanet:Token')->find($value);
        } while ($found);

        $token = new Token();

        $token->setUser($user)
            ->setType($type)
            ->setValue($value)
            ->setExpiration($expiration)
            ->setData($data);

        $this->om->persist($token);
        $this->om->flush();

        return $token;
    }

    /**
     * @param Basket $basket
     * @param User   $user
     *
     * @return Token
     */
    public function createBasketValidationToken(Basket $basket, User $user = null)
    {
        if (null === $basket->getValidation()) {
            throw new \InvalidArgumentException('A validation token requires a validation basket.');
        }

        return $this->create($user ?: $basket->getValidation()->getInitiator(), self::TYPE_VALIDATE, new \DateTime('+10 days'), $basket->getId());
    }

    /**
     * @param Basket $basket
     * @param User   $user
     *
     * @return Token
     */
    public function createBasketAccessToken(Basket $basket, User $user)
    {
        return $this->create($user, self::TYPE_VIEW, null, $basket->getId());
    }

    /**
     * @param User      $user
     * @param FeedEntry $entry
     *
     * @return Token
     */
    public function createFeedEntryToken(User $user, FeedEntry $entry)
    {
        return $this->create($user, self::TYPE_FEED_ENTRY, null, $entry->getId());
    }

    /**
     * @param User $user
     * @param $data
     *
     * @return Token
     */
    public function createDownloadToken(User $user, $data)
    {
        return $this->create($user, self::TYPE_DOWNLOAD, new \DateTime('+3 hours'), $data);
    }

    /**
     * @param $data
     *
     * @return Token
     */
    public function createEmailExportToken($data)
    {
        return $this->create(null, self::TYPE_EMAIL, new \DateTime('+1 day'), $data);
    }

    /**
     * @param User $user
     * @param $email
     *
     * @return Token
     */
    public function createResetEmailToken(User $user, $email)
    {
        return $this->create($user, self::TYPE_EMAIL_RESET, new \DateTime('+1 day'), $email);
    }

    /**
     * @param User $user
     *
     * @return Token
     */
    public function createAccountUnlockToken(User $user)
    {
        return $this->create($user, self::TYPE_ACCOUNT_UNLOCK, new \DateTime('+3 days'));
    }

    /**
     * @param User $user
     *
     * @return Token
     */
    public function createResetPasswordToken(User $user)
    {
        return $this->create($user, self::TYPE_PASSWORD, new \DateTime('+1 day'));
    }

    /**
     * Updates a token.
     *
     * @param Token $token
     *
     * @return Token
     */
    public function update(Token $token)
    {
        $this->om->persist($token);
        $this->om->flush();

        return $token;
    }

    /**
     * Removes a token.
     *
     * @param Token $token
     */
    public function delete(Token $token)
    {
        $this->om->remove($token);
        $this->om->flush();
    }

    /**
     * Removes expired tokens
     */
    public function removeExpiredTokens()
    {
        foreach ($this->repository->findExpiredTokens() as $token) {
            switch ($token->getType()) {
                case 'download':
                case 'email':
                    $file = $this->app['root.path'] . '/tmp/download/' . $token->getValue() . '.zip';
                    if (is_file($file)) {
                        unlink($file);
                    }
                    break;
            }
            $this->om->remove($token);
        }
        $this->om->flush();
    }
}
