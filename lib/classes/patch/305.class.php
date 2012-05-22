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
class patch_305 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.0.5';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

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

    public function apply(base &$appbox)
    {
        $sql = 'REPLACE INTO usr_settings
            (SELECT usr_id, "start_page_query" as prop, last_query as value
                FROM usr
                WHERE model_of="0" AND usr_login NOT LIKE "(#deleted_%")';

        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
