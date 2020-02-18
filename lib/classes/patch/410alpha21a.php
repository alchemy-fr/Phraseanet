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

class patch_410alpha21a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.21a';

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
        // fix the Longitude value

        $sql = 'SELECT id, record_id, name, value FROM technical_datas WHERE trim(name) = "LongitudeRef" ';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if (trim($row['value']) === 'W' ) {
                $sql = 'UPDATE technical_datas SET value = CONCAT("-", value) WHERE trim(name) = "Longitude" AND record_id =:record_id';
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute([':record_id' => $row['record_id']]);
            }

            $sqlDelete = 'DELETE FROM technical_datas WHERE id =:id';

            $stmt1 = $databox->get_connection()->prepare($sqlDelete);
            $stmt1->execute([':id' => $row['id']]);
            $stmt1->closeCursor();
        }

        $stmt->closeCursor();

        // fix the Latitude value

        $sql = 'SELECT id, record_id, name, value FROM technical_datas WHERE trim(name) = "LatitudeRef" ';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        foreach ($rs as $row) {
            if (trim($row['value']) === 'S' ) {
                $sql = 'UPDATE technical_datas SET value = CONCAT("-", value) WHERE trim(name) = "Latitude" AND record_id =:record_id';
                $stmt = $databox->get_connection()->prepare($sql);
                $stmt->execute([':record_id' => $row['record_id']]);
            }

            $sqlDelete = 'DELETE FROM technical_datas WHERE id =:id';

            $stmt1 = $databox->get_connection()->prepare($sqlDelete);
            $stmt1->execute([':id' => $row['id']]);
            $stmt1->closeCursor();
        }

        $stmt->closeCursor();

        return true;
    }
}
