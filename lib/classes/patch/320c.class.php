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

/**
 *
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
    public function get_release()
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
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base &$databox, Application $app)
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
            'pdftext'        => 'Phraseanet:pdftext'
            , 'tf-archivedate' => 'Phraseanet:tf-archivedate'
            , 'tf-atime'       => 'Phraseanet:tf-atime'
            , 'tf-chgdocdate'  => 'Phraseanet:tf-chgdocdate'
            , 'tf-ctime'       => 'Phraseanet:tf-ctime'
            , 'tf-editdate'    => 'Phraseanet:tf-editdate'
            , 'tf-mtime'       => 'Phraseanet:tf-mtime'
            , 'tf-parentdir'   => 'Phraseanet:tf-parentdir'
            , 'tf-bits'        => 'Phraseanet:tf-bits'
            , 'tf-channels'    => 'Phraseanet:tf-channels'
            , 'tf-extension'   => 'Phraseanet:tf-extension'
            , 'tf-filename'    => 'Phraseanet:tf-filename'
            , 'tf-filepath'    => 'Phraseanet:tf-filepath'
            , 'tf-height'      => 'Phraseanet:tf-height'
            , 'tf-mimetype'    => 'Phraseanet:tf-mimetype'
            , 'tf-recordid'    => 'Phraseanet:tf-recordid'
            , 'tf-size'        => 'Phraseanet:tf-size'
            , 'tf-width'       => 'Phraseanet:tf-width'
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

        $conn = connection::getPDOConnection($app);

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
