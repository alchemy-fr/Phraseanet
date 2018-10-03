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

class patch_381alpha2a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.1-alpha.2';

    /** @var array */
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
        $sql = 'SELECT `value` FROM `registry` WHERE `key` = "GV_i18n_service"';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (null !== $row && false !== strpos($row['value'], 'localization.webservice.alchemyasp.com')) {
            $sql = 'UPDATE `registry` SET `value` = "http://geonames.alchemyasp.com/" WHERE `key` = "GV_i18n_service"';
            $stmt = $appbox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        return true;
    }
}
