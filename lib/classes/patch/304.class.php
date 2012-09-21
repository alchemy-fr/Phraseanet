<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_304 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.0.4';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$databox, Application $app)
    {
        $sql = 'SELECT id FROM pref WHERE prop = "indexes"';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        if ($rowcount == 0) {
            $sql = 'INSERT INTO pref
                (id, prop, value, locale, updated_on, created_on)
                VALUES
                (null, "indexes", "1", "", NOW(), NOW())';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return true;
    }
}
