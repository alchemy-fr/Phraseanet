<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Core\Event\ExportFailureEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\Token;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\TokenRepository;
use Alchemy\Phrasea\Model\Repositories\UserRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Mail\MailRecordsExport;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\WorkerManager\Event\ExportMailFailureEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Pusher\Pusher;

class ExportMailWorker implements WorkerInterface
{
    use Application\Helper\NotifierAware;
    use FilesystemAware;

    private $app;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function process(array $payload)
    {
        $this->repoWorkerJob = $this->getWorkerRunningJobRepository();
        $em = $this->repoWorkerJob->getEntityManager();
        $em->beginTransaction();
        $this->repoWorkerJob->reconnect();
        $date = new \DateTime();

        $message = [
            'message_type'  => MessagePublisher::EXPORT_MAIL_TYPE,
            'payload'       => $payload
        ];

        try {
            $workerRunningJob = new WorkerRunningJob();
            $workerRunningJob
                ->setWork(MessagePublisher::EXPORT_MAIL_TYPE)
                ->setPayload($message)
                ->setPublished($date->setTimestamp($payload['published']))
                ->setStatus(WorkerRunningJob::RUNNING)
            ;

            $em->persist($workerRunningJob);

            $em->flush();

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
        }

        $filesystem = $this->getFilesystem();

        $destMails = unserialize($payload['destinationMails']);

        $params = unserialize($payload['params']);

        /** @var UserRepository $userRepository */
        $userRepository = $this->app['repo.users'];

        $user = $userRepository->find($payload['emitterUserId']);
        $localeEmitter = $user->getLocale();

        /** @var TokenRepository $tokenRepository */
        $tokenRepository = $this->app['repo.tokens'];

        /** @var Token $token */
        $token = $tokenRepository->findValidToken($payload['tokenValue']);

        $list = unserialize($token->getData());

        foreach($list['files'] as $k_file => $v_file) {
            foreach($v_file['subdefs'] as $k_subdef => $v_subdef) {
                if($v_subdef['to_stamp']) {
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
            }
        }

        $caption_dir = null;
        // add the captions files if exist
        foreach ($list['captions'] as $v_caption) {
            if (!$caption_dir) {
                // do this only once
                $caption_dir = $this->app['tmp.caption.path'] . '/' . time() . $payload['emitterUserId'] . '/';
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
        }

        $this->repoWorkerJob->reconnect();
        //zip documents
        \set_export::build_zip(
            $this->app,
            $token,
            $list,
            $this->app['tmp.download.path'].'/'. $token->getValue() . '.zip'
        );

        $remaingEmails = $destMails;
        $deliverEmails = [];

        $emitter = new Emitter($user->getDisplayName(), $user->getEmail());

        foreach ($destMails as $key => $mail) {
            try {
                $receiver = new Receiver(null, trim($mail));
            } catch (InvalidArgumentException $e) {
                continue;
            }

            $userTo = $userRepository->findByEmail(trim($mail));

            $locale = null;
            if ($userTo !== null) {
                $locale = ($userTo->getLocale() != null) ? $userTo->getLocale() : $localeEmitter;
            }

            $deliverEmails[] = $mail;

            $mail = MailRecordsExport::create($this->app, $receiver, $emitter, $params['textmail']);
            $mail->setButtonUrl($params['url']);
            $mail->setExpiration($token->getExpiration());

            if ($locale != null) {
                $mail->setLocale($locale);
            }

            $this->deliver($mail, $params['reading_confirm']);

            unset($remaingEmails[$key]);
        }

        if (count($deliverEmails) > 0) {
            $this->app['alchemy_worker.message.publisher']->pushLog("Email successfully sended to : " . implode(', ', $deliverEmails));
        }

        //some mails failed
        if (count($remaingEmails) > 0) {
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            //  notify to send to the retry queue
            $this->app['dispatcher']->dispatch(WorkerEvents::EXPORT_MAIL_FAILURE, new ExportMailFailureEvent(
                $payload['emitterUserId'],
                $payload['tokenValue'],
                $remaingEmails,
                $payload['params'],
                'some mails failed',
                $count
            ));

            foreach ($remaingEmails as $mail) {
                $this->app['dispatcher']->dispatch(PhraseaEvents::EXPORT_MAIL_FAILURE, new ExportFailureEvent(
                        $user,
                        $params['ssttid'],
                        $params['lst'],
                        \eventsmanager_notify_downloadmailfail::MAIL_FAIL,
                        $mail
                    )
                );
            }
        }

        if ($workerRunningJob != null) {
            $this->repoWorkerJob->markFinished($workerRunningJob->getId(), $this->getMessagePublisher(), MessagePublisher::EXPORT_MAIL_TYPE);
        }

        sleep(30);
        $options = array(
            'cluster' => 'eu',
            'useTLS' => true
        );
        $pusher = new Pusher(
            '07b97d8d50b1f2b3d515',
            'c441cc58dbf1f51f3e0c',
            '1682224',
            $options
        );
        $data['message'] = 'hello world';
        $pusher->trigger('my-channel', 'my-event', $data);
    }

    /**
     * @return WorkerRunningJobRepository
     */
    private function getWorkerRunningJobRepository()
    {
        return $this->app['repo.worker-running-job'];
    }

    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return $this->app['alchemy_worker.message.publisher'];
    }
}
