<?php

use Alchemy\Phrasea\Application;

class patch_415PHRAS3584 implements patchInterface
{
    /** @var string */
    private $release = '4.1.5';

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

    private function patch_databox(databox $databox, Application $app)
    {
        $cnx = $databox->get_connection();
        $sql = "ALTER TABLE `record` ADD `cover_record_id` INT(11) NULL DEFAULT NULL AFTER `parent_record_id`, ADD INDEX `cover_record_id` (`cover_record_id`);";
        try {
            $cnx->exec($sql);
        }
        catch (\Exception $e) {
            // the field already exist ?
        }
    }

    private function patch_appbox(base $databox, Application $app)
    {
    }
}
