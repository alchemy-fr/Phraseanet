<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Pusher\Pusher;

class DownloadAsyncWorker implements WorkerInterface
{
    use Application\Helper\NotifierAware;
    use FilesystemAware;

    private $app;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;
    /**
     * @var PropertyAccess
     */
    private $conf;

    /** @var Pusher|null */
    private $pusher = null;

    /** @var string  */
    private $pusher_channel_name = "";

    public function __construct(Application $app, PropertyAccess $conf)
    {
        $this->app = $app;
        $this->conf = $conf;
    }

    public function process(array $payload)
    {
        $this->repoWorkerJob = $this->getWorkerRunningJobRepository();
        $em = $this->repoWorkerJob->getEntityManager();
        $em->beginTransaction();
        $this->repoWorkerJob->reconnect();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::DOWNLOAD_ASYNC_TYPE,
            'payload'       => $payload
        ];

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::DOWNLOAD_ASYNC_TYPE)
                ->setPayload($message)
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            $workerRunningJob = null;
        }

        $filesystem = $this->getFilesystem();

        $params = unserialize($payload['params']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];

        $user = $userRepository->find($payload['userId']);
        $localeEmitter = $user->getLocale();

        /** @var TokenRepository $tokenRepository */
        $tokenRepository = $this->app['repo.tokens'];

        /** @var Token $token */
        $token = $tokenRepository->findValidToken($payload['tokenValue']);

        if($this->conf->get(['downloas_async', 'enabled'], false)) {
            $options = array(
                'cluster' => 'eu',
                'useTLS' => true
            );
            try {
                $this->pusher = new Pusher(
                    $this->conf->get(['pusher', 'auth_key'], ''),
                    $this->conf->get(['pusher', 'secret'], ''),
                    $this->conf->get(['pusher', 'app_id'], ''),
                    $options
                );
                $this->pusher_channel_name = $token->getValue();
            }
            catch (\Exception $e) {
                // no-op
            }
        }

        $list = unserialize($token->getData());

        foreach($list['files'] as $k_file => $v_file) {
            foreach($v_file['subdefs'] as $k_subdef => $v_subdef) {
                if($k_subdef === "document" && $v_subdef['to_stamp']) {
                    // we must stamp this document
                    try {
                        $record = $this->app->getApplicationBox()->get_databox($v_file['databox_id'])->get_record($v_file['record_id']);
                        $sd = $record->get_subdef($k_subdef);
                        if(!is_null($path = \recordutils_image::stamp($this->app, $sd))) {
                            // stamped !
                            $pi = pathinfo($path);
                            $list['files'][$k_file]['subdefs'][$k_subdef]['path'] = $pi['dirname'];
                            $list['files'][$k_file]['subdefs'][$k_subdef]['file'] = $pi['basename'];
                            $list['files'][$k_file]['subdefs'][$k_subdef]['size'] = filesize($path);
                        }
                    }
                    catch (\Exception $e) {
                        // failed to stamp ? ignore and send the original file
                    }
                }
                if($list['files'][$k_file]['subdefs'][$k_subdef]['size'] > 0) {
                    $this->push(
                        'file_ok',
                        [
                            'message'    => "",
                            'databox_id' => $list['files'][$k_file]['databox_id'],
                            'record_id'  => $list['files'][$k_file]['record_id'],
                            'subdef'     => $k_subdef,
                            'size'       => $list['files'][$k_file]['subdefs'][$k_subdef]['size'],
                            'human_size' => $this->getHumanSize($list['files'][$k_file]['subdefs'][$k_subdef]['size']),
                        ]
                    );
                }
            }
        }

        $caption_dir = null;
        // add the captions files if exist
        foreach ($list['captions'] as $v_caption) {
            if (!$caption_dir) {
                // do this only once
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $payload['userId'] . '/';
                $filesystem->mkdir($caption_dir, 0750);
            }

            $subdefName = $v_caption['subdefName'];
            $kFile = $v_caption['fileId'];

            $download_element = new \record_exportElement(
                $this->app,
                $list['files'][$kFile]['databox_id'],
                $list['files'][$kFile]['record_id'],
                $v_caption['elementDirectory'],
                $v_caption['remain_hd'],
                $user
            );

            $file = $list['files'][$kFile]["export_name"]
                . $list['files'][$kFile]["subdefs"][$subdefName]["ajout"] . '.'
                . $list['files'][$kFile]["subdefs"][$subdefName]["exportExt"];

            $desc = $this->app['serializer.caption']->serialize($download_element->get_caption(), $v_caption['serializeMethod'], $v_caption['businessFields']);
            file_put_contents($caption_dir . $file, $desc);

            $list['files'][$kFile]["subdefs"][$subdefName]["path"] = $caption_dir;
            $list['files'][$kFile]["subdefs"][$subdefName]["file"] = $file;
            $list['files'][$kFile]["subdefs"][$subdefName]["size"] = filesize($caption_dir . $file);
            $list['files'][$kFile]["subdefs"][$subdefName]['businessfields'] = $v_caption['businessFields'];

            $this->push(
                'file_ok',
                [
                    'message' => "",
                    'databox_id' => $list['files'][$kFile]['databox_id'],
                    'record_id' => $list['files'][$kFile]['record_id'],
                    'subdef' => $subdefName,
                    'size' => $list['files'][$kFile]["subdefs"][$subdefName]["size"],
                    'human_size' => $this->getHumanSize($list['files'][$kFile]["subdefs"][$subdefName]["size"]),
                ]
            );
        }

        $this->repoWorkerJob->reconnect();
        //zip documents
        \set_export::build_zip(
            $this->app,
            $token,
            $list,
            $this->app['tmp.download.path'].'/'. $token->getValue() . '.zip'
        );

        if ($workerRunningJob != null) {
            $this->repoWorkerJob->reconnect();
            $workerRunningJob
                ->setStatus(WorkerRunningJob::FINISHED)
                ->setFinished(new \DateTime('now'))
            ;

            $em->persist($workerRunningJob);

            $em->flush();
        }

        sleep(1);

        $this->push('zip_ready', ['message' => ""]);
    }

    private function push(string $event, $data)
    {
        if($this->pusher) {
            $r = $this->pusher->trigger(
                $this->pusher_channel_name,
                $event,
                $data
            );
        }
    }

    // todo : this Ko;Mo;Go code already exists in phraseanet (download)
    private function getHumanSize(int $size)
    {
        $unit = 'octets';
        $units = ['Go', 'Mo', 'Ko'];
        $format = "%d %s";
        while ($size > 1024 && !empty($units)) {
            $unit = array_pop($units);
            $size /= 1024.0;
            $format = "%.02f %s";
        }
        return sprintf($format, $size, $unit);
    }


    /**
     * @return WorkerRunningJobRepository
     */
    private function getWorkerRunningJobRepository()
    {
        return $this->app['repo.worker-running-job'];
    }
}
