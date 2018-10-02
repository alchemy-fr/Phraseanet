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

class patch_380alpha2b extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.2';

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
        return true;
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
        $sql = "SHOW TABLE STATUS LIKE 'cache'";
        $stmt = $app->getApplicationBox()->get_connection()->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if ($row['Auto_increment']) {
            $sql = sprintf('ALTER TABLE Sessions AUTO_INCREMENT = %d', $row['Auto_increment']);
            $app->getApplicationBox()->get_connection()->exec($sql);
        }

        return true;
    }
}
