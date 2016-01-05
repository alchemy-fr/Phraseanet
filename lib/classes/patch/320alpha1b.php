<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_320alpha1b extends patchAbstract
{
    /**  @var string */
    private $release = '3.2.0-alpha.1';

    /** @var Array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'REPLACE INTO records_rights
                (
                    SELECT null as id, usr_id, b.sbas_id, record_id, "1" as document, null as preview,
                        "push" as `case`, pushFrom as pusher_usr_id
                    FROM sselcont c, ssel s, bas b
                    WHERE c.ssel_id = s.ssel_id
                        AND b.base_id = c.base_id
                        AND c.canHD = 1
                 )';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
