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

class patch_370alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.0-alpha.4';

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
        $sql = 'SELECT id, src FROM metadatas_structure';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $update = [];

        $tagDirname = new \Alchemy\Phrasea\Metadata\Tag\TfDirname();
        $tagBasename = new \Alchemy\Phrasea\Metadata\Tag\TfBasename();

        foreach ($rs as $row) {
            if (strpos(strtolower($row['src']), 'tf-parentdir') !== false) {
                $update[] = ['id'  => $row['id'], 'src' => $tagDirname->getTagname()];
            }
            if (strpos(strtolower($row['src']), 'tf-filename') !== false) {
                $update[] = ['id'  => $row['id'], 'src' => $tagBasename->getTagname()];
            }
        }

        $sql = 'UPDATE metadatas_structure SET src = :src
                WHERE id = :id';
        $stmt = $databox->get_connection()->prepare($sql);

        foreach ($update as $row) {
            $stmt->execute([':src' => $row['src'], ':id'  => $row['id']]);
        }

        $stmt->closeCursor();

        return true;
    }
}
