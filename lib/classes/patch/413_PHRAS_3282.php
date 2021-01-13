<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;


class patch_413_PHRAS_3282 implements patchInterface
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
        'recordEdit'         => MessagePublisher::RECORD_EDIT_TYPE,
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
    }
}
