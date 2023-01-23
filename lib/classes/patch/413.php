<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class patch_413 implements patchInterface
{
    const OLDQ2NEWQ_ttl_retry = [
        'assetsIngest'       => MessagePublisher::ASSETS_INGEST_TYPE,
        'createRecord'       => MessagePublisher::CREATE_RECORD_TYPE,
        'deleteRecord'       => MessagePublisher::DELETE_RECORD_TYPE,
        'exportMail'         => MessagePublisher::EXPORT_MAIL_TYPE,
        'exposeUpload'       => MessagePublisher::EXPOSE_UPLOAD_TYPE,
        'ftp'                => MessagePublisher::FTP_TYPE,
        'populateIndex'      => MessagePublisher::POPULATE_INDEX_TYPE,
        'pullAssets'         => MessagePublisher::PULL_ASSETS_TYPE,
        'editRecord'         => MessagePublisher::EDIT_RECORD_TYPE,
        'subdefCreation'     => MessagePublisher::SUBDEF_CREATION_TYPE,
        'validationReminder' => MessagePublisher::VALIDATION_REMINDER_TYPE,
        'writeMetadatas'     => MessagePublisher::WRITE_METADATAS_TYPE,
        'webhook'            => MessagePublisher::WEBHOOK_TYPE,
    ];
    const OLDQ2NEWQ_ttl_delayed = [
        'delayedSubdef'      => MessagePublisher::SUBDEF_CREATION_TYPE,
        'delayedWriteMeta'   => MessagePublisher::WRITE_METADATAS_TYPE,
    ];

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

    private function patch_appbox(base $databox, Application $app)
    {
        /** @var PropertyAccess $conf */
        $conf = $app['conf'];

        // --------------------------------------------
        // PHRAS-3282_refacto-some-code-on-workers_MASTER
        // patch workers settings
        // --------------------------------------------

        foreach(self::OLDQ2NEWQ_ttl_retry as $old=>$new) {
            if(($v = $conf->get(['workers', 'retry_queue', $old], null)) !== null) {
                $conf->set(['workers', 'queues', $new, 'ttl_retry'], $v);
            }
        }

        foreach(self::OLDQ2NEWQ_ttl_delayed as $old=>$new) {
            if(($v = $conf->get(['workers', 'retry_queue', $old], null)) !== null) {
                $conf->set(['workers', 'queues', $new, 'ttl_delayed'], $v);
            }
        }

        if(($v = $conf->get(['workers', 'pull_assets', 'pullInterval'], null)) !== null) {
            $conf->set(['workers', 'queues', MessagePublisher::PULL_ASSETS_TYPE, 'ttl_retry'], $v * 1000);
        }

        if(($v = $conf->get(['workers', 'validationReminder', 'interval'], null)) !== null) {
            $conf->set(['workers', 'queues', MessagePublisher::VALIDATION_REMINDER_TYPE, 'ttl_retry'], $v * 1000);
        }

        $conf->remove(['workers', 'retry_queue']);
        $conf->remove(['workers', 'pull_assets']);
        $conf->remove(['workers', 'validationReminder']);

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

}
