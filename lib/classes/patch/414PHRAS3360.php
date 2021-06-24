<?php

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;

class patch_414PHRAS3360 implements patchInterface
{
    /** @var string */
    private $release = '4.1.4';

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

        $queues = [
            MessagePublisher::ASSETS_INGEST_TYPE    => [],
            MessagePublisher::CREATE_RECORD_TYPE    => [],
            MessagePublisher::EXPORT_MAIL_TYPE      => [],
            MessagePublisher::FTP_TYPE              => [],
            MessagePublisher::POPULATE_INDEX_TYPE   => [],
            MessagePublisher::PULL_ASSETS_TYPE      => [],
            MessagePublisher::SUBDEF_CREATION_TYPE  => [],
            MessagePublisher::VALIDATION_REMINDER_TYPE  => [],
            MessagePublisher::WEBHOOK_TYPE          => [],
            MessagePublisher::WRITE_METADATAS_TYPE  => [],
        ];

        // add bloc worker if not exist
        if (!$conf->has(['workers'])) {
            $workers = [
                'queue' => [
                    'worker-queue' => [
                        'registry'  => 'alchemy_worker.queue_registry',
                        'host'      => 'rabbitmq',
                        'port'      => 5672,
                        'ssl'       => false,
                        'user'      => 'alchemy',
                        'password'  => 'vdh4dpe5Wy3R',
                        'vhost'     => '/'
                    ]
                ],
                'queues' => $queues
            ];

            $conf->set(['workers'], $workers);
        } elseif (!$conf->has(['workers', 'queues'])) {
            $conf->set(['workers', 'queues'], $queues);
        }

        // if no ssl key, add it
        if (!$conf->has(['workers', 'queue', 'worker-queue', 'ssl'])) {
            $conf->set(['workers', 'queue', 'worker-queue', 'ssl'], false);
        }

        // add bloc network proxy if not yet exist
        if (!$conf->has(['network-proxies'])) {
            $proxies = [
                'http-proxy' => [
                    'enabled'   => false,
                    'host'      => null,
                    'port'      => null,
                    'user'      => null,
                    'password'  => null
                ],
                'ftp-proxy' => [
                    'enabled' => false,
                    'host'      => null,
                    'port'      => null,
                    'user'      => null,
                    'password'  => null
                ]
            ];

            $conf->set(['network-proxies'], $proxies);
        }
    }
}
