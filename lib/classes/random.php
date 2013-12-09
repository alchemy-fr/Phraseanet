<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class random
{
    /**
     *
     */
    const NUMBERS = "0123456789";
    /**
     *
     */
    const LETTERS = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    /**
     *
     */
    const LETTERS_AND_NUMBERS = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const TYPE_FEED_ENTRY = 'FEED_ENTRY';
    const TYPE_PASSWORD = 'password';
    const TYPE_DOWNLOAD = 'download';
    const TYPE_MAIL_DOWNLOAD = 'mail-download';
    const TYPE_EMAIL = 'email';
    const TYPE_VIEW = 'view';
    const TYPE_VALIDATE = 'validate';
    const TYPE_RSS = 'rss';

    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return Boolean
     */
    public function cleanTokens()
    {
        try {
            $conn = connection::getPDOConnection($this->app);

            $date = new DateTime();
            $date = $this->app['date-formatter']->format_mysql($date);

            $sql = 'SELECT * FROM tokens WHERE expire_on < :date
              AND datas IS NOT NULL AND (type="download" OR type="email")';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':date' => $date]);
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            foreach ($rs as $row) {
                switch ($row['type']) {
                    case 'download':
                    case 'email':
                        $file = $this->app['root.path'] . '/tmp/download/' . $row['value'] . '.zip';
                        if (is_file($file))
                            unlink($file);
                        break;
                }
            }

            $sql = 'DELETE FROM tokens WHERE expire_on < :date and (type="download" OR type="email")';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':date' => $date]);
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    /**
     *
     * @param  int      $length
     * @param  constant $possible
     * @return string
     */
    public static function generatePassword($length = 8, $possible = SELF::LETTERS_AND_NUMBERS)
    {
        if ( ! is_int($length))
            throw new Exception_InvalidArgument ();

        $password = "";
        if ( ! in_array($possible, [self::LETTERS_AND_NUMBERS, self::LETTERS, self::NUMBERS]))
            $possible = self::LETTERS_AND_NUMBERS;
        $i = 0;
        $possible_length = strlen($possible);
        while ($i < $length) {
            $char = substr($possible, mt_rand(0, $possible_length - 1), 1);
            $password .= $char;
            $i ++;
        }

        return $password;
    }

    /**
     *
     * @param string        $type
     * @param int           $usr
     * @param DateTime      $end_date
     * @param mixed content $datas
     *
     * @return boolean
     */
    public function getUrlToken($type, $usr, DateTime $end_date = null, $datas = '')
    {
        $this->cleanTokens();
        $conn = connection::getPDOConnection($this->app);
        $token = $test = false;

        switch ($type) {
            case self::TYPE_DOWNLOAD:
            case self::TYPE_PASSWORD:
            case self::TYPE_MAIL_DOWNLOAD:
            case self::TYPE_EMAIL:
            case self::TYPE_VALIDATE:
            case self::TYPE_VIEW:
            case self::TYPE_RSS:
            case self::TYPE_FEED_ENTRY:

                break;
            default:
                throw new Exception_InvalidArgument();
                break;
        }

        $n = 1;

        $sql = 'SELECT id FROM tokens WHERE value = :test ';
        $stmt = $conn->prepare($sql);
        while ($n < 100) {
            $test = self::generatePassword(16);
            $stmt->execute([':test' => $test]);
            if ($stmt->rowCount() === 0) {
                $token = $test;
                break;
            }
            $n ++;
        }
        $stmt->closeCursor();

        if ($token) {
            $sql = 'INSERT INTO tokens (id, value, type, usr_id, created_on, expire_on, datas)
          VALUES (null, :token, :type, :usr, NOW(), :end_date, :datas)';
            $stmt = $conn->prepare($sql);

            $params = [
                ':token'    => $token
                , ':type'     => $type
                , ':usr'      => ($usr ? $usr : '-1')
                , ':end_date' => ($end_date instanceof DateTime ? $end_date->format(DATE_ISO8601) : null)
                , ':datas'    => ((trim($datas) != '') ? $datas : null)
            ];
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        return $token;
    }

    public function removeToken($token)
    {
        $this->cleanTokens();

        try {
            $conn = connection::getPDOConnection($this->app);
            $sql = 'DELETE FROM tokens WHERE value = :token';
            $stmt = $conn->prepare($sql);
            $stmt->execute([':token' => $token]);
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public function updateToken($token, $datas)
    {
        try {
            $conn = connection::getPDOConnection($this->app);

            $sql = 'UPDATE tokens SET datas = :datas
              WHERE value = :token';

            $stmt = $conn->prepare($sql);
            $stmt->execute([':datas' => $datas, ':token' => $token]);
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public function helloToken($token)
    {
        $this->cleanTokens();

        $conn = connection::getPDOConnection($this->app);
        $sql = 'SELECT * FROM tokens
            WHERE value = :token
              AND (expire_on > NOW() OR expire_on IS NULL)';
        $stmt = $conn->prepare($sql);
        $stmt->execute([':token' => $token]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new NotFoundHttpException('Token not found');

        return $row;
    }

    /**
     * Get the validation token for one user and one validation basket
     *
     * @param integer $userId
     * @param integer $basketId
     *
     * @return string The token
     *
     * @throws NotFoundHttpException
     */
    public function getValidationToken($userId, $basketId)
    {
        $conn = \connection::getPDOConnection($this->app);
        $sql = '
            SELECT value FROM tokens
            WHERE type = :type
            AND usr_id = :usr_id
            AND datas = :basket_id
            AND (expire_on > NOW() OR expire_on IS NULL)';

        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':type'      => self::TYPE_VALIDATE,
            ':usr_id'    => (int) $userId,
            ':basket_id' => (int) $basketId,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (! $row) {
            throw new NotFoundHttpException('Token not found');
        }

        return $row['value'];
    }
}
