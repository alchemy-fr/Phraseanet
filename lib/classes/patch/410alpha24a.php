<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_410alpha24a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.24a';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
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
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        
        $sql = "ALTER TABLE `records_rights` CHANGE `document` `document` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0', CHANGE `preview` `preview` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0'";
  
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();


        $sql = "ALTER TABLE `basusr` CHANGE `basusr_infousr` `basusr_infousr` TEXT NOT NULL DEFAULT '\'\''";
  
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return true;
    }
}
