<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2019 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_410alpha26a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.26a';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function apply(base $databox, Application $app)
    {
        $sql = "ALTER TABLE `record` ADD INDEX `moddate` (`moddate`);";
        try {
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
        catch (\Exception $e) {
            // the index already exists ?
        }

        return true;
    }
}