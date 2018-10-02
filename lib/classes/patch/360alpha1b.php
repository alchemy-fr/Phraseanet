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
use Doctrine\DBAL\DBALException;

class patch_360alpha1b extends patchAbstract
{
    /** @var string */
    private $release = '3.6.0-alpha.1';

    /** @var array */
    private $concern = [base::DATA_BOX];

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
    public function apply(base $databox, Application $app)
    {
        try {
            $sql = 'ALTER TABLE `metadatas` DROP INDEX `unique`';

            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        } catch (DBALException $e) {

        }

        return true;
    }
}
