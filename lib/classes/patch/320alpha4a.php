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

class patch_320alpha4a extends patchAbstract
{
    /** @var string */
    private $release = '3.2.0-alpha.4';

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

        $phrasea_maps = [
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
        ];

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
        $databox->feed_meta_fields();

        $databox->delete_data_from_cache(databox::CACHE_STRUCTURE);
        $databox->delete_data_from_cache(databox::CACHE_META_STRUCT);

        $conn = $app->getApplicationBox()->get_connection();

        $sql = 'DELETE FROM `task2` WHERE class="readmeta"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        unset($stmt);

        return true;
    }
}
