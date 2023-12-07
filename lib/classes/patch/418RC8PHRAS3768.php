<?php

use Alchemy\Phrasea\Application;

class patch_418RC8PHRAS3768 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc8';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

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
    }

    private function patch_appbox(base $appbox, Application $app)
    {
// disabled cause crash on some install (missing table) due to pre-post migration (?)
        //        $cnx = $appbox->get_connection();
//        $sql = "ALTER TABLE `BasketElements` ADD `vote_expired` DATETIME NULL, ADD INDEX `vote_expired` (`vote_expired`)";
//        try {
//            $cnx->exec($sql);
//        }
//        catch (\Exception $e) {
            // the field already exist ?
//        }
    }
}
