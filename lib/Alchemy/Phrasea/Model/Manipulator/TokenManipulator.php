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
    const TYPE_ACCOUNT_DELETE = 'account-delete';
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

    private $temporaryDownloadPath;

    public function __construct(
        ObjectManager $om,
        Generator $random,
        TokenRepository $repository,
        $temporaryDownloadPath)
    {
        $this->om = $om;
        $this->random = $random;
        $this->repository = $repository;
        $this->temporaryDownloadPath = $temporaryDownloadPath;
    }

    /**
     * @param User|null      $user
     * @param string         $type
     * @param \DateTime|null $expiration
     * @param mixed|null     $data
     *
     * @return Token
     */
    public function create($user, $type, \DateTime $expiration = null, $data = null)
    {
        static $stmt = null;

        // $this->removeExpiredTokens();

        if($stmt === null) {
            $conn = $this->repository->getEntityManager()->getConnection();
            $sql = "INSERT INTO Tokens (value, user_id, type, data, created, updated, expiration)\n"
                . " VALUES(:value, :user_id, :type, :data, :created, :updated, :expiration)";
            $stmt = $conn->prepare($sql);
        }

        $token = null;
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $stmtParms = [
            ':value' => null,
            ':user_id' => $user->getId(),
            ':type' => $type,
            ':data' => $data,
            ':created' => $now,
            ':updated' => $now,
            ':expiration' => ($expiration === null ? null : $expiration->format('Y-m-d H:i:s'))
        ];
        for($try=0; $try<1024; $try++) {
            $stmtParms['value'] = $this->random->generateString(32, self::LETTERS_AND_NUMBERS);
            if($stmt->execute($stmtParms) === true) {
                $token = new Token();
                $token->setUser($user)
                    ->setType($type)
                    ->setValue($stmtParms['value'])
                    ->setExpiration($expiration)
                    ->setData($data);
                break;
            }
        }
        if ($token === null) {
            throw new \RuntimeException('Unable to create a token.');
        }

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
     * Create feedEntryTokens for many users in one shot
     *
     * @param User[] $users
     * @param FeedEntry $entry
     * @return Token[]
     * @throws \Doctrine\DBAL\DBALException
     */
    public function createFeedEntryTokens($users, FeedEntry $entry)
    {
        // $this->removeExpiredTokens();

        $conn = $this->repository->getEntityManager()->getConnection();

        // use an optimized tmp table we can fill fast (only 2 values changes by row, others are default)
        $now = $conn->quote((new \DateTime())->format('Y-m-d H:i:s'));
        $conn->executeQuery("CREATE TEMPORARY TABLE `tmpTokens` (\n"
            . " `value` char(128),\n"
            . " `user_id` int(11),\n"
            . " `type` char(32) DEFAULT " . $conn->quote(self::TYPE_FEED_ENTRY) . ",\n"
            . " `data` int(11) DEFAULT " . $conn->quote($entry->getId()) . ",\n"
            . " `created` datetime DEFAULT " . $now . ",\n"
            . " `updated` datetime DEFAULT " . $now . ",\n"
            . " `expiration` datetime DEFAULT NULL\n"
            . ") ENGINE=MEMORY;"
        );

        $tokens = [];
        $sql = "";
        foreach ($users as $user) {
            $value = $this->random->generateString(32, self::LETTERS_AND_NUMBERS) . $user->getId();
            // todo: don't build a too long sql, we should flush/run into temp table if l>limit.
            // But for now we trust that 100 (see FeedEntrySsuscriber) tokens is ok
            $sql .= ($sql?',':'') . ('(' . $conn->quote($value) . ',' . $conn->quote($user->getId()) . ')');

            $token = new Token();
            $token->setUser($user)
                ->setType(self::TYPE_FEED_ENTRY)
                ->setValue($value)
                ->setExpiration(null)
                ->setData($entry->getId());
            $tokens[] = $token;
        }

        $conn->executeQuery("INSERT INTO tmpTokens (`value`, `user_id`) VALUES " . $sql);
        $conn->executeQuery("INSERT INTO Tokens SELECT * FROM tmpTokens");
        $conn->executeQuery("DROP TABLE tmpTokens");

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
    public function createAccountDeleteToken(User $user, $email)
    {
        return $this->create($user, self::TYPE_ACCOUNT_DELETE, new \DateTime('+1 hour'), $email);
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
