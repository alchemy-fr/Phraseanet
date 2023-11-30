<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;

class patch_418RC8PHRAS3944 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc8';

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
    public function getDoctrineMigrations()
    {
        return [];
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
    public function apply(base $base, Application $app)
    {
        if ($base->get_base_type() === base::DATA_BOX) {
            $this->patch_databox($base, $app);
        }
        elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private $thumbSubstitution = null;

    public function patch_databox(databox $databox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        $field_names = [
            $conf->get(["video-editor", "ChapterVttFieldName"]),
            "VideoTextTrackFr",
            "VideoTextTrackEn",
            "VideoTextTrackGe",
        ];
        $field_ids = [];

        /** @var databox_field $dbf */
        foreach($databox->get_meta_structure() as $dbf) {
            if(in_array($dbf->get_name(), $field_names)) {
                $field_ids[] = $dbf->get_id();
            }
        }
        if(count($field_ids) === 0) {
            return;
        }

        $sql_sel = 'SELECT `id`, `value` FROM `metadatas`'
            . ' WHERE `value` RLIKE(\'\\\\d\\\\d:\\\\d\\\\d:\\\\d\\\\d\\\\.(\\\\d{1,2})\\\\D\')'
            . ' AND `meta_struct_id` IN(' . join(',', $field_ids) . ')'
            . ' AND `id` > :minid'
            . ' ORDER BY `id` ASC LIMIT 50';
        $minid = 0;
        $stmt_sel = $databox->get_connection()->prepare($sql_sel);
        $stmt_sel->bindParam(':minid', $minid, PDO::PARAM_INT);


        $sql_upd = 'UPDATE `metadatas` SET `value` = :value WHERE `id` = :id';
        $upd_value = "";
        $upd_id = 0;
        $stmt_upd = $databox->get_connection()->prepare($sql_upd);
        $stmt_upd->bindParam(':value', $upd_value, PDO::PARAM_STR);
        $stmt_upd->bindParam(':id', $upd_id, PDO::PARAM_STR);

        do {
            $to_fix = [];
            $row_found = false;
            $stmt_sel->execute();
            while (($row = $stmt_sel->fetch(PDO::FETCH_ASSOC))) {
                $row_found = true;
                $vtt = $row['value'];
                $vtt_fixed = preg_replace(
                    [
                        '/(\d\d:\d\d:\d\d)\.(\d\d\D)/i',
                        '/(\d\d:\d\d:\d\d)\.(\d\D)/i'
                    ],
                    [
                        '\1.0\2',
                        '\1.00\2'
                    ],
                    $vtt
                );

                $to_fix[$row['id']] = $vtt_fixed;
                $minid = (int)$row['id'];
            }
            $stmt_sel->closeCursor();

            foreach ($to_fix as $upd_id => $upd_value) {
                $stmt_upd->execute();
            }
        }
        while($row_found);
    }

    private function patch_appbox(base $appbox, Application $app)
    {
    }
}
