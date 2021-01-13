<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Application\Helper\NotifierAware;
use Alchemy\Phrasea\Core\LazyLocator;
use Alchemy\Phrasea\Model\Entities\FtpExport;
use Alchemy\Phrasea\Model\Entities\FtpExportElement;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\FtpExportRepository;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Model\Serializer\CaptionSerializer;
use Alchemy\Phrasea\Notification\Mail\MailSuccessFTPReceiver;
use Alchemy\Phrasea\Notification\Mail\MailSuccessFTPSender;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class FtpWorker implements WorkerInterface
{
    use NotifierAware;

    private $app;
    /** @var LoggerInterface  */
    private $logger;
    /** @var TranslatorInterface  */
    private $translator;
    /** @var WorkerRunningJobRepository */
    private $repoWorker;

    public function __construct(Application $app)
    {
        $this->app          = $app;
        $this->logger       = $app['alchemy_worker.logger'];
        $this->translator   = $app['translator'];
        $this->repoWorker   = $app['repo.worker-running-job'];
    }

    public function process(array $payload)
    {
        $repoFtpExport = $this->getRepoFtpExport();
        /** @var FtpExport|null $export */
        $export = $repoFtpExport->find($payload['ftpExportId']);

        if ($export !== null) {
            $this->doExport($export, $payload);
        }
    }

    private function doExport(FtpExport $export, array $payload)
    {
        $em = $this->repoWorker->getEntityManager();
        $em->beginTransaction();
        $date = new \DateTime();

        $fullPayload = [
            'message_type'  => MessagePublisher::FTP_TYPE,
            'payload'       => $payload
        ];

        $processError = false;
        $workerMessage = '';

        if (isset($payload['workerJobId'])) {
            try {
                /** @var WorkerRunningJob $workerRunningJob */
                $workerRunningJob = $this->repoWorker->find($payload['workerJobId']);

                if ($workerRunningJob == null) {
                    $this->logger->error("Given workerJobId not found !");

                    return ;
                }

                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT . $payload['count'])
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        } else {
            try {
                $workerRunningJob = new WorkerRunningJob();
                $workerRunningJob
                    ->setWork(MessagePublisher::FTP_TYPE)
                    ->setPayload($fullPayload)
                    ->setPublished($date->setTimestamp($payload['published']))
                    ->setStatus(WorkerRunningJob::RUNNING)
                ;

                $em->persist($workerRunningJob);

                $em->flush();

                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        }

        $defaultSetting = [
            'proxy'         => false,
            'proxyPort'     => false,
            'proxyUser'     => false,
            'proxyPassword' => false
        ];

        $settings = $this->app['conf']->get(['workers', 'ftp'], $defaultSetting);

        $proxy = (string) $settings['proxy'];
        $proxyport = (string) $settings['proxyPort'];
        $proxyuser = (string) $settings['proxyUser'];
        $proxypwd  = (string) $settings['proxyPassword'];

        $state = "";
        $ftp_server = $export->getAddr();
        $ftp_user_name = $export->getLogin();
        $ftp_user_pass = $export->getPwd();

        $ftpLog = $ftp_user_name . "@" . \p4string::addEndSlash($ftp_server) . $export->getDestfolder();

        if ($export->getCrash() == 0) {
            $line = $this->translator->trans('task::ftp:Etat d\'envoi FTP vers le serveur "%server%" avec le compte "%username%" et pour destination le dossier : "%directory%"', [
                    '%server%'    => $ftp_server,
                    '%username%'  => $ftp_user_name,
                    '%directory%' => $export->getDestfolder(),
                ]) . PHP_EOL;
            $state .= $line;
            $this->logger->debug($line);
        }

        $state .= $line = $this->translator->trans("task::ftp:TENTATIVE no %number%, %date%", [
                '%number%' => $export->getCrash() + 1,
                '%date%' => "  (" . date('r') . ")"
            ]) . PHP_EOL;

        $this->logger->debug($line);

        try {
            $ssl = $export->isSsl();
            /** @var \ftpClient $ftp_client */
            $ftp_client = $this->app['phraseanet.ftp.client'](
                $ftp_server, 21, 300, $ssl, $proxy, $proxyport,
                $proxyuser, $proxypwd
            );
            $ftp_client->login($ftp_user_name, $ftp_user_pass);

            if ($export->isPassif()) {
                try {
                    $ftp_client->passive(true);
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }

            if (trim($export->getDestfolder()) != '') {
                try {
                    $ftp_client->chdir($export->getDestFolder());
                    $export->setDestfolder('/' . $export->getDestfolder());
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            } else {
                $export->setDestfolder('/');
            }

            if (trim($export->getFoldertocreate()) != '') {
                try {
                    $ftp_client->mkdir($export->getFoldertocreate());
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
                try {
                    $new_dir = $ftp_client->add_end_slash($export->getDestfolder())
                        . $export->getFoldertocreate();
                    $ftp_client->chdir($new_dir);
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage());
                }
            }

            $obj = [];

            $basefolder = '';
            if (!in_array(trim($export->getDestfolder()), ['.', './', ''])) {
                $basefolder = \p4string::addEndSlash($export->getDestfolder());
            }

            $basefolder .= $export->getFoldertocreate();

            if (in_array(trim($basefolder), ['.', './', ''])) {
                $basefolder = '/';
            }

            foreach ($export->getElements() as $exportElement) {
                if ($exportElement->isDone()) {
                    continue;
                }

                $base_id = $exportElement->getBaseId();
                $record_id = $exportElement->getRecordId();
                $subdef = $exportElement->getSubdef();
                $localfile = null;

                try {
                    $sbas_id = \phrasea::sbasFromBas($this->app, $base_id);
                    $record = new \record_adapter($this->app, $sbas_id, $record_id);

                    $sdcaption = $this->app['serializer.caption']->serialize($record->get_caption(), CaptionSerializer::SERIALIZE_XML, $exportElement->isBusinessfields());

                    $remotefile = $exportElement->getFilename();

                    if ($subdef == 'caption') {
                        $desc = $this->app['serializer.caption']->serialize($record->get_caption(), CaptionSerializer::SERIALIZE_XML, $exportElement->isBusinessfields());

                        $localfile = sys_get_temp_dir().'/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new \Exception('Impossible de creer un fichier temporaire');
                        }
                    } elseif ($subdef == 'caption-yaml') {
                        $desc = $this->app['serializer.caption']->serialize($record->get_caption(), CaptionSerializer::SERIALIZE_YAML, $exportElement->isBusinessfields());

                        $localfile = sys_get_temp_dir().'/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new \Exception('Impossible de creer un fichier temporaire');
                        }
                    } else {
                        try {
                            $sd = $record->get_subdef($subdef);
                        } catch (\Exception_Media_SubdefNotFound $notFount) {
                            continue;
                        }

                        $localfile = $sd->getRealPath();
                        if (!file_exists($localfile)) {
                            throw new \Exception('Le fichier local n\'existe pas');
                        }
                    }

                    $current_folder = rtrim(str_replace('//', '/', $basefolder . $exportElement->getFolder()), '/');

                    if ($ftp_client->pwd() != $current_folder) {
                        try {
                            $ftp_client->chdir($current_folder);
                        } catch (\Exception $e) {
                            $this->logger->debug($e->getMessage());
                        }
                    }

                    $ftp_client->put($remotefile, $localfile);

                    $obj[] = [
                        "name"     => $subdef, "size"     => filesize($localfile),
                        "shortXml" => ($sdcaption ? $sdcaption : '')
                    ];

                    if ($subdef == 'caption') {
                        unlink($localfile);
                    }

                    $exportElement
                        ->setDone(true)
                        ->setError(false);
                    $this->app['orm.em']->persist($exportElement);
                    $this->app['orm.em']->flush();
                    $this->logexport($this->app, $record, $obj, $ftpLog);
                } catch (\Exception $e) {
                    $state .= $line = $this->translator->trans('task::ftp:File "%file%" (record %record_id%) de la base "%basename%" (Export du Document) : Transfert cancelled (le document n\'existe plus)', ['%file%' => basename($localfile), '%record_id%' => $record_id, '%basename%' => \phrasea::sbas_labels(\phrasea::sbasFromBas($this->app, $base_id), $this->app)]) . "\n<br/>";

                    $this->logger->debug($line);

                    // One failure max
                    $exportElement
                        ->setDone($exportElement->isError())
                        ->setError(true);
                    $this->app['orm.em']->persist($exportElement);
                    $this->app['orm.em']->flush();

                    $processError  = true;
                    $workerMessage = $line;
                }
            }

            if ($export->isLogfile()) {
                $this->logger->debug("logfile ");

                $date = new \DateTime();
                $buffer = '#transfert finished ' . $date->format(DATE_ATOM) . "\n\n";

                foreach ($export->getElements() as $exportElement) {
                    if (!$exportElement->isDone() || $exportElement->isError()) {
                        continue;
                    }
                    $filename = $exportElement->getFilename();
                    $folder = $exportElement->getFilename();
                    $root = $export->getFoldertocreate();

                    $buffer .= $root . '/' . $folder . $filename . "\n";
                }

                $tmpfile = sys_get_temp_dir().'/tmpftpbuffer' . $date->format('U') . '.txt';

                file_put_contents($tmpfile, $buffer);

                $remotefile = $date->format('U') . '-transfert.log';
                $ftp_client->chdir($export->getDestFolder());
                $ftp_client->put($remotefile, $tmpfile);
                unlink($tmpfile);
            }

            $ftp_client->close();
        } catch (\Exception $e) {
            $state .= $line = $e . "\n";

            $this->logger->debug($line);

            $export->incrementCrash();
            $this->app['orm.em']->persist($export);
            $this->app['orm.em']->flush();

            $processError  = true;
            $workerMessage = $line;
        }

        $this->finalize($this->app, $export);

        if (!$processError && $workerRunningJob) {
            // tell that we have finished to work on this file
            $this->repoWorker->reconnect();
            $em->beginTransaction();
            try {
                $workerRunningJob->setStatus(WorkerRunningJob::FINISHED);
                $workerRunningJob->setFinished(new \DateTime('now'));
                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }
        } else {
            // if there is an error
            $count = isset($payload['count']) ? $payload['count'] + 1 : 2 ;

            $this->repoWorker->reconnect();
            $em->beginTransaction();
            try {
                $workerRunningJob
                    ->setInfo(WorkerRunningJob::ATTEMPT. ($count - 1))
                    ->setStatus(WorkerRunningJob::ERROR)
                ;

                $em->persist($workerRunningJob);
                $em->flush();
                $em->commit();
            } catch (\Exception $e) {
                $em->rollback();
            }

            $payload['workerJobId'] = $workerRunningJob->getId();
            $fullPayload = [
                'message_type'  => MessagePublisher::FTP_TYPE,
                'payload'       => $payload
            ];

            $this->getMessagePublisher()->publishRetryMessage(
                $fullPayload,
                MessagePublisher::FTP_TYPE,
                $count,
                $workerMessage
            );
        }
    }

    private function finalize(Application $app, FtpExport $export)
    {
        if ($export->getCrash() >= $export->getNbretry()) {
            $this->send_mails($app, $export);

            return $this;
        }

        $total = count($export->getElements());
        $done = count($export->getElements()->filter(function (FtpExportElement $element) {
            return $element->isDone();
        }));
        $error = count($export->getElements()->filter(function (FtpExportElement $element) {
            return $element->isError();
        }));

        if ($done === $total) {
            $this->send_mails($app, $export);

            if ((int) $error === 0) {
                $app['orm.em']->remove($export);
                $app['orm.em']->flush();
            } else {
                $export->setCrash($export->getNbretry());
                foreach ($export->getElements() as $element) {
                    if (!$element->isError()) {
                        $app['orm.em']->remove($export);
                    }
                }
                $app['orm.em']->flush();
            }

            return $this;
        }
    }

    private function send_mails(Application $app, FtpExport $export)
    {

        $this->setDelivererLocator(new LazyLocator($app, 'notification.deliverer'));

        $transferts = [];
        $transfert_status = $this->translator->trans('task::ftp:Tous les documents ont ete transferes avec succes');

        foreach ($export->getElements() as $element) {
            if (!$element->isError() && $element->isDone()) {
                $transferts[] =
                    '<li>' . $this->translator->trans('task::ftp:Record %recordid% - %filename% de la base (%databoxname% - %collectionname%) - %subdefname%', [
                        '%recordid%' => $element->getRecordId(),
                        '%filename%' => $element->getFilename(),
                        '%databoxname%' => \phrasea::sbas_labels(\phrasea::sbasFromBas($app, $element->getBaseId()), $app),
                        '%collectionname%' => \phrasea::bas_labels($element->getBaseId(), $app), $element->getSubdef(),
                        '%subdefname%' => $element->getSubdef(),
                    ]) . ' : ' . $this->translator->trans('Transfert OK') . '</li>';
            } else {
                $transferts[] =
                    '<li>' . $this->translator->trans('task::ftp:Record %recordid% - %filename% de la base (%databoxname% - %collectionname%) - %subdefname%', [
                        '%recordid%' => $element->getRecordId(),
                        '%filename%' => $element->getFilename(),
                        '%databoxname%' => \phrasea::sbas_labels(\phrasea::sbasFromBas($app, $element->getBaseId()), $app),
                        '%collectionname%' => \phrasea::bas_labels($element->getBaseId(), $app), $element->getSubdef(),
                        '%subdefname%' => $element->getSubdef(),
                    ])  . ' : ' . $this->translator->trans('Transfert Annule') . '</li>';
                $transfert_status = $this->translator->trans('task::ftp:Certains documents n\'ont pas pu etre tranferes');
            }
        }

        if ($export->getCrash() >= $export->getNbretry()) {
            $connection_status = $this->translator->trans('Des difficultes ont ete rencontres a la connection au serveur distant');
        } else {
            $connection_status = $this->translator->trans('La connection vers le serveur distant est OK');
        }

        $text_mail_sender = $export->getTextMailSender();
        $text_mail_receiver = $export->getTextMailReceiver();
        $sendermail = $export->getSendermail();
        $ftp_server = $export->getAddr();

        $message = "\n\n----------------------------------------\n\n";
        $message =  $connection_status . "\n";
        $message .= $transfert_status . "\n";
        $message .= $this->translator->trans("task::ftp:Details des fichiers") . "\n\n";

        $message .= implode("\n", $transferts);

        $sender_message = $text_mail_sender . $message;
        $receiver_message = $text_mail_receiver . $message;

        try {
            $receiver = new Receiver(null, $sendermail);
            $mail = MailSuccessFTPSender::create($app, $receiver, null, $sender_message);
            $mail->setServer($ftp_server);
            $this->deliver($mail);
        } catch (\Exception $e) {
        }

        try {
            $receiver = new Receiver(null, $export->getMail());
            $mail = MailSuccessFTPReceiver::create($app, $receiver, null, $receiver_message);
            $mail->setServer($ftp_server);
            $this->deliver($mail);
        } catch (\Exception $e) {
            $this->logger->debug(sprintf('Unable to deliver success message : %s', $e->getMessage()));
        }
    }

    private function logexport(Application $app, \record_adapter $record, $obj, $ftpLog)
    {
        foreach ($obj as $oneObj) {
            $app['phraseanet.logger']($record->getDatabox())
                ->log($record, \Session_Logger::EVENT_EXPORTFTP, $ftpLog, '');
        }

        return $this;
    }

    /**
     * @return FtpExportRepository
     */
    private function getRepoFtpExport()
    {
        return $this->app['repo.ftp-exports'];
    }

    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return $this->app['alchemy_worker.message.publisher'];
    }

}
