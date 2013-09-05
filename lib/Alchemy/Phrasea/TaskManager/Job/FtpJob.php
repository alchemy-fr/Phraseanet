<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\TaskManager\Editor\FtpEditor;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Mail\MailSuccessFTPSender;
use Alchemy\Phrasea\Notification\Receiver;
use Entities\Task;
use Entities\FtpExport;
use Entities\FtpExportElement;

class FtpJob extends AbstractJob
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'FTP task';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Use this task to enable FTP push.';
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new FtpEditor();
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();

        $this->removeDeadExports($app);
        $exports = $this->retrieveExports($app);

        foreach ($exports as $export) {
            $this->doExport($app, $data->getTask(), $export);
        }
    }

    private function removeDeadExports(Application $app)
    {
        foreach ($app['EM']
                ->getRepository('Entities\FtpExport')
                ->findCrashedExports(new \DateTime('-1 month')) as $export) {
            $app['EM']->remove($export);
        }
        $app['EM']->flush();
    }

    private function retrieveExports(Application $app)
    {
        return $app['EM']
                ->getRepository('Entities\FtpExport')
                ->findDoableExports();
    }

    protected function doExport(Application $app, Task $task, FtpExport $export)
    {
        $settings = simplexml_load_string($task->getSettings());

        $proxy = (string) $settings->proxy;
        $proxyport = (string) $settings->proxyport;

        $state = "";
        $ftp_server = $export->getAddr();
        $ftp_user_name = $export->getLogin();
        $ftp_user_pass = $export->getPwd();

        $ftpLog = $ftp_user_name . "@" . \p4string::addEndSlash($ftp_server) . $export->getDestfolder();

        if ($export->getCrash() == 0) {
            $line = sprintf(
                _('task::ftp:Etat d\'envoi FTP vers le serveur' .
                    ' "%1$s" avec le compte "%2$s" et pour destination le dossier : "%3$s"') . PHP_EOL
                , $ftp_server
                , $ftp_user_name
                , $export->getDestfolder()
            );
            $state .= $line;
            $this->log('debug', $line);
        }

        $state .= $line = sprintf(
                _("task::ftp:TENTATIVE no %s, %s")
                , $export->getCrash() + 1
                , "  (" . date('r') . ")"
            ) . PHP_EOL;


        $this->log('debug', $line);

        try {
            $ssl = $export->isSsl();
            $ftp_client = $app['phraseanet.ftp.client']($ftp_server, 21, 300, $ssl, $proxy, $proxyport);
            $ftp_client->login($ftp_user_name, $ftp_user_pass);

            if ($export->isPassif()) {
                try {
                    $ftp_client->passive(true);
                } catch (\Exception $e) {
                    $this->log('debug', $e->getMessage());
                }
            }

            if (trim($export->getDestfolder()) != '') {
                try {
                    $ftp_client->chdir($export->getDestFolder());
                    $export->setDestfolder('/' . $export->getDestfolder());
                } catch (\Exception $e) {
                    $this->log('debug', $e->getMessage());
                }
            } else {
                $export->setDestfolder('/');
            }

            if (trim($export->getFoldertocreate()) != '') {
                try {
                    $ftp_client->mkdir($export->getFoldertocreate());
                } catch (\Exception $e) {
                    $this->log('debug', $e->getMessage());
                }
                try {
                    $new_dir = $ftp_client->add_end_slash($export->getDestfolder())
                        . $export->getFoldertocreate();
                    $ftp_client->chdir($new_dir);
                } catch (\Exception $e) {
                    $this->log('debug', $e->getMessage());
                }
            }

            $obj = array();

            $basefolder = '';
            if (!in_array(trim($export->getDestfolder()), array('.', './', ''))) {
                $basefolder = \p4string::addEndSlash($export->getDestfolder());
            }

            $basefolder .= $export->getFoldertocreate();

            if (in_array(trim($basefolder), array('.', './', ''))) {
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
                    $sbas_id = \phrasea::sbasFromBas($app, $base_id);
                    $record = new \record_adapter($app, $sbas_id, $record_id);

                    $sdcaption = $record->get_caption()->serialize(\caption_record::SERIALIZE_XML, $exportElement->isBusinessfields());

                    $remotefile = $exportElement->getFilename();

                    if ($subdef == 'caption') {
                        $desc = $record->get_caption()->serialize(\caption_record::SERIALIZE_XML, $exportElement->isBusinessfields());

                        $localfile = $app['root.path'] . '/tmp/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new \Exception('Impossible de creer un fichier temporaire');
                        }
                    } elseif ($subdef == 'caption-yaml') {
                        $desc = $record->get_caption()->serialize(\caption_record::SERIALIZE_YAML, $exportElement->isBusinessfields());

                        $localfile = $app['root.path'] . '/tmp/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new \Exception('Impossible de creer un fichier temporaire');
                        }
                    } else {
                        $sd = $record->get_subdefs();

                        if (!$sd || !isset($sd[$subdef])) {
                            continue;
                        }

                        $localfile = $sd[$subdef]->get_pathfile();
                        if (!file_exists($localfile)) {
                            throw new \Exception('Le fichier local n\'existe pas');
                        }
                    }

                    $current_folder = \p4string::delEndSlash(str_replace('//', '/', $basefolder . $exportElement->getFolder()));

                    if ($ftp_client->pwd() != $current_folder) {
                        try {
                            $ftp_client->chdir($current_folder);
                        } catch (\Exception $e) {
                            $this->log('debug', $e->getMessage());
                        }
                    }

                    $ftp_client->put($remotefile, $localfile);

                    $obj[] = array(
                        "name"     => $subdef, "size"     => filesize($localfile),
                        "shortXml" => ($sdcaption ? $sdcaption : '')
                    );

                    if ($subdef == 'caption') {
                        unlink($localfile);
                    }

                    $exportElement
                            ->setDone(true)
                            ->setError(false);
                    $app['EM']->persist($exportElement);
                    $app['EM']->flush();
                    $this->logexport($app, $record, $obj, $ftpLog);
                } catch (\Exception $e) {
                    $state .= $line = sprintf(_('task::ftp:File "%1$s" (record %2$s) de la base "%3$s"' .
                                ' (Export du Document) : Transfert cancelled (le document n\'existe plus)')
                            , basename($localfile), $record_id
                            , \phrasea::sbas_labels(\phrasea::sbasFromBas($app, $base_id), $app)) . "\n<br/>";

                    $this->log('debug', $line);

                    // One failure max
                    $exportElement
                            ->setDone($exportElement->isError())
                            ->setError(true);
                    $app['EM']->persist($exportElement);
                    $app['EM']->flush();
                }
            }

            if ($export->isLogfile()) {
                $this->log('debug', "logfile ");

                $date = new DateTime();
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

                $tmpfile = $app['root.path'] . '/tmp/tmpftpbuffer' . $date->format('U') . '.txt';

                file_put_contents($tmpfile, $buffer);

                $remotefile = $date->format('U') . '-transfert.log';
                $ftp_client->chdir($export->getDestFolder());
                $ftp_client->put($remotefile, $tmpfile);
                unlink($tmpfile);
            }

            $ftp_client->close();
        } catch (\Exception $e) {
            $state .= $line = $e . "\n";

            $this->log('debug', $line);

            $export->incrementCrash();
            $app['EM']->persist($export);
            $app['EM']->flush();
        }

        $this->finalize($app, $export);
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
                $app['EM']->remove($export);
                $app['EM']->flush();
            } else {
                $export->setCrash($export->getNbretry());
                foreach ($export->getElements() as $element) {
                    if (!$element->isError()) {
                        $app['EM']->remove($export);
                    }
                }
                $app['EM']->flush();
            }

            return $this;
        }
    }

    private function send_mails(Application $app, FtpExport $export)
    {
        $transferts = array();
        $transfert_status = _('task::ftp:Tous les documents ont ete transferes avec succes');

        foreach ($export->getElements() as $element) {
            if (!$element->isError() && $element->isDone()) {
                $transferts[] =
                    '<li>' . sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s')
                        , $element->getRecordId(), $element->getFilename()
                        , \phrasea::sbas_labels(\phrasea::sbasFromBas($app, $element->getBaseId()), $app)
                        , \phrasea::bas_labels($element->getBaseId(), $app), $element->getSubdef()) . ' : ' . _('Transfert OK') . '</li>';
            } else {
                $transferts[] =
                    '<li>' . sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s')
                        , $element->getRecordId(), $element->getFilename()
                        , \phrasea::sbas_labels(\phrasea::sbasFromBas($app, $element->getBaseId()), $app), \phrasea::bas_labels($element->getBaseId(), $app)
                        , $element->getSubdef()) . ' : ' . _('Transfert Annule') . '</li>';
                $transfert_status = _('task::ftp:Certains documents n\'ont pas pu etre tranferes');
            }
        }

        if ($export->getCrash() >= $export->getNbretry()) {
            $connection_status = _('Des difficultes ont ete rencontres a la connection au serveur distant');
        } else {
            $connection_status = _('La connection vers le serveur distant est OK');
        }

        $text_mail_sender = $export->getTextMailSender();
        $text_mail_receiver = $export->getTextMailReceiver();
        $sendermail = $export->getSendermail();
        $ftp_server = $export->getAddr();

        $message = "\n\n----------------------------------------\n\n";
        $message =  $connection_status . "\n";
        $message .= $transfert_status . "\n";
        $message .= _("task::ftp:Details des fichiers") . "\n\n";

        $message .= implode("\n", $transferts);

        $sender_message = $text_mail_sender . $message;
        $receiver_message = $text_mail_receiver . $message;

        try {
            $receiver = new Receiver(null, $sendermail);
            $mail = MailSuccessFTPSender::create($app, $receiver, null, $sender_message);
            $mail->setServer($ftp_server);
            $app['notification.deliverer']->deliver($mail);
        } catch (InvalidArgumentException $e) {
        }

        try {
            $receiver = new Receiver(null, $export->getMail());
            $mail = MailSuccessFTPSender::create($app, $receiver, null, $receiver_message);
            $mail->setServer($ftp_server);
            $app['notification.deliverer']->deliver($mail);
        } catch (\Exception $e) {
            $this->log('debug', sprintf('Unable to deliver success message : %s', $e->getMessage()));
        }
    }

    private function logexport(Application $app, \record_adapter $record, $obj, $ftpLog)
    {
        foreach ($obj as $oneObj) {
            $app['phraseanet.logger']($record->get_databox())
                ->log($record, \Session_Logger::EVENT_EXPORTFTP, $ftpLog, '');
        }

        return $this;
    }
}
