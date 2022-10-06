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

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\Basket;
use Alchemy\Phrasea\Model\Entities\FeedEntry;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\User;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use RandomLib\Generator;
use RuntimeException;

class TokenManipulator implements ManipulatorInterface
{
    const LETTERS_AND_NUMBERS = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const TYPE_FEED_ENTRY = 'FEED_ENTRY';
    const TYPE_PASSWORD = 'password';
    const TYPE_ACCOUNT_UNLOCK = 'account-unlock';
    const TYPE_ACCOUNT_DELETE = 'account-delete';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_MAIL_DOWNLOAD = 'mail-download';
    const TYPE_EMAIL = 'email';
    const TYPE_EMAIL_RESET = 'email-reset';
    const TYPE_VIEW = 'view';
    const TYPE_VALIDATE = 'validate';
    const TYPE_RSS = 'rss';
    const TYPE_USER_RELANCE = 'user-relance';

    /** @var Objectmanager */
    private $om;
    private $random;
    private $repository;
    private $conf;

    private $temporaryDownloadPath;

    public function __construct(
        ObjectManager $om,
        Generator $random,
        TokenRepository $repository,
        $temporaryDownloadPath,
        PropertyAccess $configuration)
    {
        $this->om = $om;
        $this->random = $random;
        $this->repository = $repository;
        $this->temporaryDownloadPath = $temporaryDownloadPath;
        $this->conf = $configuration;
    }

    /**
     * @param User|null      $user
     * @param string         $type
     * @param DateTime|null  $expiration
     * @param mixed|null     $data
     *
     * @return Token
     */
    public function create($user, $type, $expiration = null, $data = null)
    {
        // remove all expired token after 30 days
        $this->removeExpiredTokens(30);

        $n = 0;
        do {
            if ($n++ > 1024) {
                throw new RuntimeException('Unable to create a token.');
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
     * @param User $user
     * @param DateTime|null $expiration
     *
     * @return Token
     */
    public function createBasketValidationToken(Basket $basket, User $user, $expiration)
    {
        return $this->create($user, self::TYPE_VALIDATE, $expiration, $basket->getId());
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
     * Create feedEntryTokens for many users in one shot
     *
     * @param User[] $users
     * @param FeedEntry $entry
     * @return Token[]
     */
    public function createFeedEntryTokens($users, FeedEntry $entry)
    {
        // $this->removeExpiredTokens();

        $tokens = [];
        foreach ($users as $user) {
            $value = $this->random->generateString(32, self::LETTERS_AND_NUMBERS) . $user->getId();

            $token = new Token();
            $token->setUser($user)
                ->setType(self::TYPE_FEED_ENTRY)
                ->setValue($value)
                ->setExpiration(null)
                ->setData($entry->getId());
            $tokens[] = $token;

            $this->om->persist($token);
        }
        $this->om->flush();
        $this->om->clear();

        return $tokens;
    }

    /**
     * @param User $user
     * @param $data
     *
     * @return Token
     */
    public function createDownloadToken(User $user, $data)
    {
        $downloadLinkValidity = (int) $this->conf->get(['registry', 'actions', 'download-link-validity'], 24);

        return $this->create($user, self::TYPE_DOWNLOAD, new DateTime("+{$downloadLinkValidity} hours"), $data);
    }

    /**
     * @param $data
     *
     * @return Token
     */
    public function createEmailExportToken($data)
    {
        $downloadLinkValidity = (int) $this->conf->get(['registry', 'actions', 'download-link-validity'], 24);

        return $this->create(null, self::TYPE_EMAIL, new DateTime("+{$downloadLinkValidity} hours"), $data);
    }

    /**
     * @param User $user
     * @param $email
     *
     * @return Token
     */
    public function createResetEmailToken(User $user, $email)
    {
        return $this->create($user, self::TYPE_EMAIL_RESET, new DateTime('+1 day'), $email);
    }

    /**
     * @param User $user
     *
     * @return Token
     */
    public function createAccountUnlockToken(User $user)
    {
        return $this->create($user, self::TYPE_ACCOUNT_UNLOCK, new DateTime('+3 days'));
    }

    /**
     * @param User $user
     * @param string $email
     *
     * @return Token
     */
    public function createAccountDeleteToken(User $user, $email)
    {
        return $this->create($user, self::TYPE_ACCOUNT_DELETE, new DateTime('+1 hour'), $email);
    }

    /**
     * @param User $user
     *
     * @return Token
     */
    public function createResetPasswordToken(User $user)
    {
        return $this->create($user, self::TYPE_PASSWORD, new DateTime('+3 day'));
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
    public function removeExpiredTokens($nbDaysAfterExpiration = 0)
    {
        foreach ($this->repository->findExpiredTokens($nbDaysAfterExpiration) as $token) {
            switch ($token->getType()) {
                case 'download':
                case 'email':
                    $file = $this->temporaryDownloadPath . '/' . $token->getValue() . '.zip';
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
