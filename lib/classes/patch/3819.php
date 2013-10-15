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
use Symfony\Component\Process\ExecutableFinder;

class patch_3819 implements patchInterface
{
    /** @var string */
    private $release = '3.8.1';

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
        $sql = 'SELECT base_id, ord, sbas_id
            FROM  `bas`
            ORDER BY sbas_id, ord';
        $stmt = $appbox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sbasData = array();
        $sbas_id = null;
        $reorder = array();
        foreach ($rs as $row) {
            $sbasData[$row['sbas_id']][] = array('base_id' => $row['base_id']);
            if ($sbas_id !== $row['sbas_id']) {
                $orders = array();
            }
            $sbas_id = $row['sbas_id'];
            if (in_array($row['ord'], $orders, true)) {
                $reorder[] = $row['sbas_id'];
            }
            $orders[] = $row['ord'];
        }
        $reorder = array_unique($reorder);

        if (count($reorder) > 0) {
            $sql = 'UPDATE bas SET ord = :ord WHERE base_id = :base_id';
            $stmt = $appbox->get_connection()->prepare($sql);
            foreach ($reorder as $sbas_id) {
                $i = 1;
                foreach ($sbasData[$sbas_id] as $data) {
                    $stmt->execute(array('base_id' => $data['base_id'], 'ord' => $i++));
                }
            }
            $stmt->closeCursor();
        }

        return true;
    }
}
