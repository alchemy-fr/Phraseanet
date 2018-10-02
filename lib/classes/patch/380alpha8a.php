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

class patch_380alpha8a extends patchAbstract
{
    /** @var string */
    private $release = '3.8.0-alpha.8';

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
        $conn = $appbox->get_connection();

        $sql = 'SELECT settings
                FROM task2
                WHERE class="task_period_cindexer" LIMIT 1';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        if (!$row) {
            return false;
        }

        $sxe = simplexml_load_string($row['settings']);
        $indexer = $sxe->binpath . '/phraseanet_indexer';

        $app['conf']->set(['main', 'binaries', 'phraseanet_indexer'], $indexer);

        return true;
    }
}
