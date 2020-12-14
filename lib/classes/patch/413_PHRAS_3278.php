<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;


class patch_413_PHRAS_3278 implements patchInterface
{
    /** @var string */
    private $release = '4.1.3';

    /** @var array */
    private $concern = [base::APPLICATION_BOX, base::DATA_BOX];

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

    private function patch_appbox(base $databox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        // patch for reminder validation key, default value to 20
        $conf->remove(['registry', 'actions', 'validation-reminder-days']);
        $conf->set(['registry', 'actions', 'validation-reminder-time-left-percent'], 20);

        // if not exist add maxResultWindow key
        if (!$conf->has(['main', 'search-engine', 'options', 'maxResultWindow'])) {
            $conf->set(['main', 'search-engine', 'options', 'maxResultWindow'], 500000);
        }

        // if not exist add populate_permalinks key
        if (!$conf->has(['main', 'search-engine', 'options', 'populate_permalinks'])) {
            $conf->set(['main', 'search-engine', 'options', 'populate_permalinks'], false);
        }
    }

    private function patch_databox(base $databox, Application $app)
    {
        // patch to invert push and validation action in log_docs

        // add a new temp action
        $sql = "ALTER TABLE log_docs CHANGE action action ENUM('push','add','validate','edit','collection','status','print','substit','publish','download','mail','ftp','delete','to_do','') CHARACTER SET ascii COLLATE ascii_bin NOT NULL";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = "UPDATE log_docs SET action = 'to_do' where action = 'push'";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = "UPDATE log_docs SET action = 'push' where action = 'validate'";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = "UPDATE log_docs SET action = 'validate' where action = 'to_do'";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        // remove temp action
        $sql = "ALTER TABLE log_docs CHANGE action action ENUM('push','add','validate','edit','collection','status','print','substit','publish','download','mail','ftp','delete','') CHARACTER SET ascii COLLATE ascii_bin NOT NULL";
        $stmt = $databox->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    }
}
