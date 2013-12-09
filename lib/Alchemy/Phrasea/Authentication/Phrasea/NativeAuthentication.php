<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;
use Symfony\Component\HttpFoundation\Request;

class NativeAuthentication implements PasswordAuthenticationInterface
{
    /** @var \connection_interface */
    private $conn;
    /** @var PasswordEncoder */
    private $encoder;
    /** @var OldPasswordEncoder */
    private $oldEncoder;

    public function __construct(PasswordEncoder $encoder, OldPasswordEncoder $oldEncoder, \connection_interface $conn)
    {
        $this->conn = $conn;
        $this->encoder = $encoder;
        $this->oldEncoder = $oldEncoder;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsrId($username, $password, Request $request)
    {
        if (in_array($username, ['invite', 'autoregister'])) {
            return null;
        }

        $sql = 'SELECT nonce, salted_password, mail_locked, usr_id, usr_login, usr_password
                FROM usr
                WHERE usr_login = :login
                  AND usr_login NOT LIKE "(#deleted_%"
                  AND model_of="0" AND invite="0"
                LIMIT 0, 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':login' => $username]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            return null;
        }

        // check locked account
        if ('1' == $row['mail_locked']) {
            throw new AccountLockedException('The account is locked', $row['usr_id']);
        }

        if ('0' == $row['salted_password']) {
            // we need a quick update and continue
            if ($this->oldEncoder->isPasswordValid($row['usr_password'], $password, $row['nonce'])) {

                $row['nonce'] = \random::generatePassword(8, \random::LETTERS_AND_NUMBERS);
                $row['usr_password'] = $this->encoder->encodePassword($password, $row['nonce']);

                $sql = 'UPDATE usr SET usr_password = :password, nonce = :nonce
                        WHERE usr_id = :usr_id';
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    ':password' => $row['usr_password'],
                    ':nonce' => $row['nonce'],
                    ':usr_id' => $row['usr_id'],
                ]);
                $stmt->closeCursor();
            }
        }

        if (!$this->encoder->isPasswordValid($row['usr_password'], $password, $row['nonce'])) {
            return null;
        }

        return $row['usr_id'];
    }

    /**
     * {@inheritdoc}
     *
     * @return NativeAuthentication
     */
    public static function create(Application $app)
    {
        return new static($app['auth.password-encoder'], $app['auth.old-password-encoder'], $app['phraseanet.appbox']->get_connection());
    }
}
