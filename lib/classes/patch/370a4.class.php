<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_370a4 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.0.0.a4';

    /**
     *
     * @var Array
     */
    private $concern = array(base::DATA_BOX);

    /**
     *
     * @return string
     */
    function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    function concern()
    {
        return $this->concern;
    }

    function apply(base &$databox)
    {

        $sql = 'SELECT id, src FROM metadatas_structure';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $update = array();

        $tagDirname = new \Alchemy\Phrasea\Metadata\Tag\TfDirname();
        $tagBasename = new \Alchemy\Phrasea\Metadata\Tag\TfBasename();

        foreach ($rs as $row) {

            if (strpos(strtolower($row['src']), 'tf-parentdir') !== false) {
                $update[] = array('id'  => $row['id'], 'src' => $tagDirname->getTagname());
            }
            if (strpos(strtolower($row['src']), 'tf-filename') !== false) {
                $update[] = array('id'  => $row['id'], 'src' => $tagBasename->getTagname());
            }
        }

        $sql = 'UPDATE metadatas_structure SET src = :src WHERE id = :id';
        $stmt = $databox->get_connection()->prepare($sql);

        foreach ($update as $row) {
            $stmt->execute(array(':src' => $row['src'], ':id'  => $row['id']));
        }

        $stmt->closeCursor();

        return true;
    }
}

