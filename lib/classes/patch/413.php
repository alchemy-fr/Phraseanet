<?php

use Alchemy\Phrasea\Application;

class patch_413 implements patchInterface
{
    /** @var string */
    private $release = '4.1.3';

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
    public function apply(base $databox, Application $app)
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

        // patch for reminder validation key, default value to 20

        if ($app['conf']->has(['registry', 'actions', 'validation-reminder-days'])) {
            $app['conf']->remove(['registry', 'actions', 'validation-reminder-days']);
            $app['conf']->set(['registry', 'actions', 'validation-reminder-time-left-percent'], 20);
        } else {
            $app['conf']->set(['registry', 'actions', 'validation-reminder-time-left-percent'], 20);
        }

        // if not exist add maxResultWindow key
        if (!$app['conf']->has(['main', 'search-engine', 'maxResultWindow'])) {
            $app['conf']->set(['main', 'search-engine', 'maxResultWindow'], 500000);
        }

        return true;
    }
}
