<?php

use Alchemy\Phrasea\Application;

class patch_418RC10PHRAS4033 implements patchInterface
{
    /** @var string */
    private $release = '4.1.8-rc10';

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
        } elseif ($base->get_base_type() === base::APPLICATION_BOX) {
            $this->patch_appbox($base, $app);
        }

        return true;
    }

    private function patch_databox(databox $databox, Application $app)
    {
        $sql = "ALTER TABLE log_docs CHANGE action action ENUM('push','add','validate','edit','collection','status','print','substit','publish','download','mail','ftp','delete','subdefCreation','') CHARACTER SET ascii COLLATE ascii_bin NOT NULL";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }

    private function patch_appbox(base $appbox, Application $app)
    {

    }
}
