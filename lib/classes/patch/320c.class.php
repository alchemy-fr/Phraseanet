<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_320c implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.2.0.0.a4';

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
        $sql = 'TRUNCATE metadatas';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'TRUNCATE metadatas_structure';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'TRUNCATE technical_datas';
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();


        $phrasea_maps = array(
            'pdftext'        => '/rdf:RDF/rdf:Description/PHRASEANET:pdftext'
            , 'tf-archivedate' => '/rdf:RDF/rdf:Description/PHRASEANET:tf-archivedate'
            , 'tf-atime'       => '/rdf:RDF/rdf:Description/PHRASEANET:tf-atime'
            , 'tf-chgdocdate'  => '/rdf:RDF/rdf:Description/PHRASEANET:tf-chgdocdate'
            , 'tf-ctime'       => '/rdf:RDF/rdf:Description/PHRASEANET:tf-ctime'
            , 'tf-editdate'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-editdate'
            , 'tf-mtime'       => '/rdf:RDF/rdf:Description/PHRASEANET:tf-mtime'
            , 'tf-parentdir'   => '/rdf:RDF/rdf:Description/PHRASEANET:tf-parentdir'
            , 'tf-bits'        => '/rdf:RDF/rdf:Description/PHRASEANET:tf-bits'
            , 'tf-channels'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-channels'
            , 'tf-extension'   => '/rdf:RDF/rdf:Description/PHRASEANET:tf-extension'
            , 'tf-filename'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-filename'
            , 'tf-filepath'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-filepath'
            , 'tf-height'      => '/rdf:RDF/rdf:Description/PHRASEANET:tf-height'
            , 'tf-mimetype'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-mimetype'
            , 'tf-recordid'    => '/rdf:RDF/rdf:Description/PHRASEANET:tf-recordid'
            , 'tf-size'        => '/rdf:RDF/rdf:Description/PHRASEANET:tf-size'
            , 'tf-width'       => '/rdf:RDF/rdf:Description/PHRASEANET:tf-width'
        );

        $sxe = $databox->get_sxml_structure();
        $dom_struct = $databox->get_dom_structure();
        $xp_struct = $databox->get_xpath_structure();

        foreach ($sxe->description->children() as $fname => $field) {
            $src = trim(isset($field['src']) ? $field['src'] : '');

            if (array_key_exists($src, $phrasea_maps)) {
                $src = $phrasea_maps[$src];
            }

            $nodes = $xp_struct->query('/record/description/' . $fname);
            if ($nodes->length > 0) {
                $node = $nodes->item(0);
                $node->setAttribute('src', $src);
                $node->removeAttribute('meta_id');
            }
        }

        $databox->saveStructure($dom_struct);

        $ext_databox = new extended_databox($databox->get_sbas_id());
        $ext_databox->migrate_fields();

        $databox->delete_data_from_cache(databox::CACHE_STRUCTURE);
        $databox->delete_data_from_cache(databox::CACHE_META_STRUCT);


        $conn = connection::getPDOConnection();
        $sql = 'INSERT INTO `task2`
                    (`task_id`, `usr_id_owner`, `pid`, `status`, `crashed`,
                        `active`, `name`, `last_exec_time`, `class`, `settings`, `completed`)
                    VALUES
                    (null, 0, 0, "stopped", 0, 1, "upgrade to v3.2 for sbas ' . $databox->get_sbas_id() . '",
                    "0000-00-00 00:00:00", "task_period_upgradetov32",
                        "<?xml version=\"1.0\" encoding=\"UTF-8\"?>' .
            '<tasksettings><sbas_id>' . $databox->get_sbas_id() . '</sbas_id></tasksettings>", -1)';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM `task2` WHERE class="readmeta"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);


        return true;
    }
}

class extended_databox extends databox
{

    public function __construct($sbas_id)
    {
        parent::__construct($sbas_id);
    }

    public function migrate_fields()
    {
        $this->feed_meta_fields();

        return $this;
    }
}
