<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_387alpha2a implements patchInterface
{
    /** @var string */
    private $release = '3.8.7-alpha.2';

    /** @var array */
    private $concern = array(base::APPLICATION_BOX);

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
        $sql = 'ALTER TABLE api_accounts ADD deleted TINYINT DEFAULT 0';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();

        $stmt->closeCursor();

        $sql = 'ALTER TABLE api_applications ADD deleted TINYINT DEFAULT 0';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();

        $stmt->closeCursor();

        $sql = 'ALTER TABLE api_applications MODIFY webhook_url VARCHAR(4096);';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();

        $stmt->closeCursor();


        return true;
    }
}
