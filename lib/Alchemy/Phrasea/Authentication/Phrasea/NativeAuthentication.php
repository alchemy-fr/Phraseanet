<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Symfony\Component\HttpFoundation\Request;
use Alchemy\Phrasea\Authentication\Exception\AccountLockedException;

class NativeAuthentication
{
    /** @var \connection_interface */
    private $conn;
    /** @var PasswordEncoder */
    private $encoder;
    /** @var OldPasswordEncoder */
    private $oldEncoder;
    /** @var FailureManager */
    private $failure;

    public function __construct(PasswordEncoder $encoder, OldPasswordEncoder $oldEncoder, FailureManager $failure, \connection_interface $conn)
    {
        $this->conn = $conn;
        $this->encoder = $encoder;
        $this->oldEncoder = $oldEncoder;
        $this->failure = $failure;
    }

    /**
     * Validate credentials for a web based authentication
     *
     * @param type $username
     * @param type $password
     * @param Request $request
     *
     * @return boolean
     *
     * @throws AccountLockedException
     * @throws RequireCaptchaException
     */
    public function isValid($username, $password, Request $request)
    {
        if (in_array($username, array('invite', 'autoregister'))) {
            return false;
        }

        $sql = 'SELECT nonce, salted_password, mail_locked, usr_id, usr_login, usr_password
                FROM usr
                WHERE usr_login = :login
                  AND usr_login NOT LIKE "(#deleted_%"
                  AND model_of="0" AND invite="0"
                LIMIT 0, 1';

        $stmt = $this->conn->prepare($sql);
        $stmt->execute(array(':login' => $username));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            return false;
        }

        // check locked account
        if ('1' == $row['mail_locked']) {
            throw new AccountLockedException('The account is locked', $row['usr_id']);
        }

        // check failures and throws a RequireCaptchaExeption is needed
        $this->failure->checkFailures($username, $request);

        if ('0' == $row['salted_password']) {
            // we need a quick update and continue
            if ($this->oldEncoder->isPasswordValid($row['usr_password'], $password, $row['nonce'])) {

                $row['nonce'] = \random::generatePassword(8, \random::LETTERS_AND_NUMBERS);
                $row['usr_password'] = $this->encoder->encodePassword($password, $row['nonce']);

                $sql = 'UPDATE usr SET usr_password = :password, nonce = :nonce
                        WHERE usr_id = :usr_id';
                $stmt = $this->conn->prepare($sql);
                $stmt->execute(array(
                    ':password' => $row['usr_password'],
                    ':nonce' => $row['nonce'],
                    ':usr_id' => $row['usr_id'],
                ));
                $stmt->closeCursor();
            }
        }

        if (!$this->encoder->isPasswordValid($row['usr_password'], $password, $row['nonce'])) {
            // save failures
            $this->failure->saveFailure($username, $request);
            // check failures
            $this->failure->checkFailures($username, $request);

            return false;
        }

        return $row['usr_id'];
    }
}
