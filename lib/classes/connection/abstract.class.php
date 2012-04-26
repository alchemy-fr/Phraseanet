<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
abstract class connection_abstract extends PDO
{
    protected $name;
    protected $credentials = array();
    protected $multi_db = true;

    public function get_credentials()
    {
        return $this->credentials;
    }

    public function is_multi_db()
    {
        return $this->multi_db;
    }

    /**
     * html debug message from PDOException object
     * @param PDOException $e
     */
    public static function html_pdo_exception(PDOException $e)
    {
        $p = array('e' => $e);

        $core = \bootstrap::getCore();
        $twig = $core->getTwig();

        return $twig->render('common/pdo_exception.html', $p);
    }

    /**
     *
     * @return string
     */
    public function get_name()
    {
        return $this->name;
    }

    public function ping()
    {
        try {
            $this->query('SELECT 1');
        } catch (PDOException $e) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param string $statement
     * @param array $driver_options
     * @return PDOStatement
     */
    public function prepare($statement, $driver_options = array())
    {
        return parent::prepare($statement, $driver_options);
    }

    /**
     *
     * @return boolean
     */
    public function beginTransaction()
    {
        return parent::beginTransaction();
    }

    /**
     *
     * @return boolean
     */
    public function commit()
    {
        return parent::commit();
    }

    /**
     *
     * @return string
     */
    public function server_info()
    {
        return parent::getAttribute(constant("PDO::ATTR_SERVER_VERSION"));
    }
}
