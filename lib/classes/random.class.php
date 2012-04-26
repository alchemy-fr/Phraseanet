<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

    /**
     *
     * @return Void
     */
    public static function cleanTokens()
    {
        try {
            $conn = connection::getPDOConnection();

            $date = new DateTime();
            $date = phraseadate::format_mysql($date);
            $registry = registry::get_instance();

            $sql = 'SELECT * FROM tokens WHERE expire_on < :date
              AND datas IS NOT NULL AND (type="download" OR type="email")';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':date' => $date));
            $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();
            foreach ($rs as $row) {
                switch ($row['type']) {
                    case 'download':
                    case 'email':
                        $file = $registry->get('GV_RootPath') . 'tmp/download/' . $row['value'] . '.zip';
                        if (is_file($file))
                            unlink($file);
                        break;
                }
            }

            $sql = 'DELETE FROM tokens WHERE expire_on < :date and (type="download" OR type="email")';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':date' => $date));
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    /**
     *
     * @param int $length
     * @param constant $possible
     * @return string
     */
    public static function generatePassword($length = 8, $possible = SELF::LETTERS_AND_NUMBERS)
    {
        if ( ! is_int($length))
            throw new Exception_InvalidArgument ();

        $password = "";
        if ( ! in_array($possible, array(self::LETTERS_AND_NUMBERS, self::LETTERS, self::NUMBERS)))
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
     * @param string $type
     * @param int $usr
     * @param string $end_date
     * @param mixed content $datas
     * @return boolean
     */
    public static function getUrlToken($type, $usr, DateTime $end_date = null, $datas = '')
    {
        self::cleanTokens();
        $conn = connection::getPDOConnection();
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
            $stmt->execute(array(':test' => $test));
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

            $params = array(
                ':token'    => $token
                , ':type'     => $type
                , ':usr'      => ($usr ? $usr : '-1')
                , ':end_date' => ($end_date instanceof DateTime ? phraseadate::format_mysql($end_date) : null)
                , ':datas'    => ((trim($datas) != '') ? $datas : null)
            );
            $stmt->execute($params);
            $stmt->closeCursor();
        }

        return $token;
    }

    public static function removeToken($token)
    {
        self::cleanTokens();

        try {
            $conn = connection::getPDOConnection();
            $sql = 'DELETE FROM tokens WHERE value = :token';
            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':token' => $token));
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public static function updateToken($token, $datas)
    {
        try {
            $conn = connection::getPDOConnection();

            $sql = 'UPDATE tokens SET datas = :datas
              WHERE value = :token';

            $stmt = $conn->prepare($sql);
            $stmt->execute(array(':datas' => $datas, ':token' => $token));
            $stmt->closeCursor();

            return true;
        } catch (Exception $e) {

        }

        return false;
    }

    public static function helloToken($token)
    {
        self::cleanTokens();

        $conn = connection::getPDOConnection();
        $sql = 'SELECT * FROM tokens
            WHERE value = :token
              AND (expire_on > NOW() OR expire_on IS NULL)';
        $stmt = $conn->prepare($sql);
        $stmt->execute(array(':token' => $token));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ( ! $row)
            throw new Exception_NotFound('Token not found');

        return $row;
    }
}
