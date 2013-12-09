<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_370alpha5a implements patchInterface
{
    /** @var string */
    private $release = '3.7.0-alpha.5';

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
    public function getDoctrineMigrations()
    {
        return [];
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

        foreach ($rs as $row) {
            $src = str_replace(
                ['/rdf:RDF/rdf:Description/PHRASEANET:', '/rdf:RDF/rdf:Description/'], ['Phraseanet:', ''], $row['src']
            );
            $update[] = ['id'  => $row['id'], 'src' => $src];
        }

        $sql = 'UPDATE metadatas_structure SET src = :src WHERE id = :id';
        $stmt = $databox->get_connection()->prepare($sql);

        foreach ($update as $row) {
            $stmt->execute([':src' => $row['src'], ':id'  => $row['id']]);
        }

        $stmt->closeCursor();

        return true;
    }
}
