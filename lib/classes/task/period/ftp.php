<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Core\Configuration\Configuration;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Mail\MailSuccessFTPSender;
use Alchemy\Phrasea\Notification\Receiver;
use Entities\FtpExport;
use Entities\FtpExportElement;

class task_period_ftp extends task_appboxAbstract
{
    protected $proxy;
    protected $proxyport;

    /**
     *
     * @return string
     */
    public static function getName()
    {
        return(_("task::ftp:FTP Push"));
    }

    /**
     *
     * @return string
     */
    public static function help()
    {
        return '';
    }

    /**
     *
     * @param  string $oldxml
     * @return string
     */
    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms('proxy', 'proxyport', 'period', 'syslog');
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if ((@$dom->loadXML($oldxml)) != FALSE) {
            foreach (array('str:proxy', 'str:proxyport', 'str:period', 'pop:syslog') as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != NULL) {
                    // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                    while (($n = $ns->firstChild)) {
                        $ns->removeChild($n);
                    }
                } else {
                    // le champ n'existait pas dans le xml, on le cree
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                }
                // on fixe sa valeur
                switch ($ptype) {
                    case "str":
                    case "pop":
                        $ns->appendChild($dom->createTextNode($pvalue));
                        break;
                    case "boo":
                        $ns->appendChild($dom->createTextNode($pvalue ? '1' : '0'));
                        break;
                }
            }
        }

        return($dom->saveXML());
    }


    /**
     *
     * @return void
     */
    public function printInterfaceJS()
    {
        ?>
        <script type="text/javascript">
            function taskFillGraphic_<?php echo(get_class($this));?>(xml)
            {
                if (xml) {
                    xml = $.parseXML(xml);
                    xml = $(xml);

                    with(document.forms['graphicForm'])
                    {
                        proxy.value     = xml.find("proxy").text();
                        proxyport.value = xml.find("proxyport").text();
                        period.value    = xml.find("period").text();
                    }
                }
            }

            $(document).ready(function(){
                var limits = {
                    'period' :{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>}
                } ;
                $(".formElem").change(function(){
                    fieldname = $(this).attr("name");
                    switch ((this.nodeName+$(this).attr("type")).toLowerCase()) {
                        case "inputtext":
                            if (typeof(limits[fieldname])!='undefined') {
                                var v = 0|this.value;
                                if(v < limits[fieldname].min)
                                    v = limits[fieldname].min;
                                else if(v > limits[fieldname].max)
                                    v = limits[fieldname].max;
                                this.value = v;
                            }
                            break;
                    }
                    setDirty();
                });
            });
        </script>
        <?php

        return;
    }

    /**
     *
     * @return string
     */
    public function getInterfaceHTML()
    {
        ob_start();
        ?>
        <form id="graphicForm" name="graphicForm" class="form-horizontal" onsubmit="return(false);" method="post">
            <div class="control-group">
                <label class="control-label"><?php echo _('task::ftp:proxy') ?></label>
                <div class="controls">
                    <input class="formElem" type="text" name="proxy" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('task::ftp:proxy port') ?></label>
                <div class="controls">
                    <input class="formElem" type="text" name="proxyport" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label"><?php echo _('task::_common_:periodicite de la tache') ?></label>
                <div class="controls">
                    <input class="formElem input-small" type="text" name="period" />
                    <span class="help-inline"><?php echo _('task::_common_:secondes (unite temporelle)') ?></span>
                </div>
            </div>
        </form>
        <?php

        return ob_get_clean();
    }

    public function saveChanges(connection_pdo $conn, $taskid, &$taskrow)
    {
        $request = http_request::getInstance();

        $parm = $request->get_parms(
            'xml'
            , 'name'
            , 'active'
            , 'proxy'
            , 'proxyport'
            , 'period'
        );

        if ($parm["xml"] === null) {
            // pas de xml 'raw' : on accepte les champs 'graphic view'
            $domTaskSettings = new DOMDocument();
            if ((@$domTaskSettings->loadXML($taskrow["settings"])) != FALSE) {
                $xmlchanged = false;
                foreach (array(
                'proxy'
                , 'proxyport'
                , 'period'
                ) as $f) {
                    if ($parm[$f] !== NULL) {
                        if (($ns = $domTaskSettings->getElementsByTagName($f)->item(0)) != NULL) {
                            // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                            while (($n = $ns->firstChild)) {
                                $ns->removeChild($n);
                            }
                        } else {
                            // le champ n'existait pas dans le xml, on le cree
                            $ns = $domTaskSettings->documentElement->appendChild($domTaskSettings->createElement($f));
                        }
                        // on fixe sa valeur
                        $ns->appendChild($domTaskSettings->createTextNode($parm[$f]));
                        $xmlchanged = true;
                    }
                }
                if ($xmlchanged) {
                    $parm["xml"] = $domTaskSettings->saveXML();
                }
            }
        }

        // si on doit changer le xml, on verifie qu'il est valide
        $domdoc = new DOMDocument();
        if ($parm["xml"] && ! @$domdoc->loadXML($parm["xml"])) {
            return(false);
        }

        $sql = "";
        $params = array(':task_id' => $taskid);
        if ($parm["xml"] !== NULL) {
            $sql .= ( $sql ? " ," : "") . "settings = :settings";
            $params[':settings'] = $parm['xml'];
        }
        if ($parm["name"] !== NULL) {
            $sql .= ( $sql ? " ," : "") . "name = :name";
            $params[':name'] = $parm['name'];
        }
        if ($parm["active"] !== NULL) {
            $sql .= ( $sql ? " ," : "") . "active = :active";
            $params[':active'] = $parm['active'];
        }

        if ($sql) {
            try {
                $sql = "UPDATE task2 SET $sql WHERE task_id = :task_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $stmt->closeCursor();

                return true;
            } catch (Exception $e) {
                return false;
            }
        } else {
            return true;
        }
    }

    protected function loadSettings(SimpleXMLElement $sx_task_settings)
    {
        $this->proxy = (string) $sx_task_settings->proxy;
        $this->proxyport = (string) $sx_task_settings->proxyport;

        parent::loadSettings($sx_task_settings);
    }

    protected function retrieveContent(appbox $appbox)
    {
        foreach ($this->dependencyContainer['EM']
                ->getRepository('Entities\FtpExport')
                ->findCrashedExports(new \DateTime('-1 month')) as $export) {
            $this->dependencyContainer['EM']->remove($export);
        }
        $this->dependencyContainer['EM']->flush();

        return $this->dependencyContainer['EM']
                ->getRepository('Entities\FtpExport')
                ->findDoableExports();
    }

    protected function processOneContent(appbox $appbox, $export)
    {
        $state = "";
        $ftp_server = $export->getAddr();
        $ftp_user_name = $export->getLogin();
        $ftp_user_pass = $export->getPwd();

        $ftpLog = $ftp_user_name . "@" . p4string::addEndSlash($ftp_server) . $export->getDestfolder();

        if ($export->getCrash() == 0) {
            $line = sprintf(
                _('task::ftp:Etat d\'envoi FTP vers le serveur' .
                    ' "%1$s" avec le compte "%2$s" et pour destination le dossier : "%3$s"') . PHP_EOL
                , $ftp_server
                , $ftp_user_name
                , $export->getDestfolder()
            );
            $state .= $line;
            $this->logger->addDebug($line);
        }

        $state .= $line = sprintf(
                _("task::ftp:TENTATIVE no %s, %s")
                , $export->getCrash() + 1
                , "  (" . date('r') . ")"
            ) . PHP_EOL;

        $this->logger->addDebug($line);

        try {
            $ssl = $export->isSsl();
            $ftp_client = $this->dependencyContainer['phraseanet.ftp.client']($ftp_server, 21, 300, $ssl, $this->proxy, $this->proxyport);
            $ftp_client->login($ftp_user_name, $ftp_user_pass);

            if ($export->isPassif()) {
                try {
                    $ftp_client->passive(true);
                } catch (Exception $e) {
                    $this->logger->addDebug($e->getMessage());
                }
            }

            if (trim($export->getDestfolder()) != '') {
                try {
                    $ftp_client->chdir($export->getDestFolder());
                    $export->setDestfolder('/' . $export->getDestfolder());
                } catch (Exception $e) {
                    $this->logger->addDebug($e->getMessage());
                }
            } else {
                $export->setDestfolder('/');
            }

            if (trim($export->getFoldertocreate()) != '') {
                try {
                    $ftp_client->mkdir($export->getFoldertocreate());
                } catch (Exception $e) {
                    $this->logger->addDebug($e->getMessage());
                }
                try {
                    $new_dir = $ftp_client->add_end_slash($export->getDestfolder())
                        . $export->getFoldertocreate();
                    $ftp_client->chdir($new_dir);
                } catch (Exception $e) {
                    $this->logger->addDebug($e->getMessage());
                }
            }

            $obj = array();

            $basefolder = '';
            if (!in_array(trim($export->getDestfolder()), array('.', './', ''))) {
                $basefolder = p4string::addEndSlash($export->getDestfolder());
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
                    $sbas_id = phrasea::sbasFromBas($this->dependencyContainer, $base_id);
                    $record = new record_adapter($this->dependencyContainer, $sbas_id, $record_id);

                    $sdcaption = $record->get_caption()->serialize(caption_record::SERIALIZE_XML, $exportElement->isBusinessfields());

                    $remotefile = $exportElement->getFilename();

                    if ($subdef == 'caption') {
                        $desc = $record->get_caption()->serialize(\caption_record::SERIALIZE_XML, $exportElement->isBusinessfields());

                        $localfile = $this->dependencyContainer['root.path'] . '/tmp/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new Exception('Impossible de creer un fichier temporaire');
                        }
                    } elseif ($subdef == 'caption-yaml') {
                        $desc = $record->get_caption()->serialize(\caption_record::SERIALIZE_YAML, $exportElement->isBusinessfields());

                        $localfile = $this->dependencyContainer['root.path'] . '/tmp/' . md5($desc . time() . mt_rand());
                        if (file_put_contents($localfile, $desc) === false) {
                            throw new Exception('Impossible de creer un fichier temporaire');
                        }
                    } else {
                        $sd = $record->get_subdefs();

                        if (!$sd || !isset($sd[$subdef])) {
                            continue;
                        }

                        $localfile = $sd[$subdef]->get_pathfile();
                        if (!file_exists($localfile)) {
                            throw new Exception('Le fichier local n\'existe pas');
                        }
                    }

                    $current_folder = p4string::delEndSlash(str_replace('//', '/', $basefolder . $exportElement->getFolder()));

                    if ($ftp_client->pwd() != $current_folder) {
                        try {
                            $ftp_client->chdir($current_folder);
                        } catch (Exception $e) {
                            $this->logger->addDebug($e->getMessage());
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
                    $this->dependencyContainer['EM']->persist($exportElement);
                    $this->dependencyContainer['EM']->flush();
                    $this->logexport($record, $obj, $ftpLog);
                } catch (Exception $e) {
                    $state .= $line = sprintf(_('task::ftp:File "%1$s" (record %2$s) de la base "%3$s"' .
                                ' (Export du Document) : Transfert cancelled (le document n\'existe plus)')
                            , basename($localfile), $record_id
                            , phrasea::sbas_labels(phrasea::sbasFromBas($this->dependencyContainer, $base_id), $this->dependencyContainer)) . "\n<br/>";

                    $this->logger->addDebug($line);

                    // One failure max
                    $exportElement
                            ->setDone($exportElement->isError())
                            ->setError(true);
                    $this->dependencyContainer['EM']->persist($exportElement);
                    $this->dependencyContainer['EM']->flush();
                }
            }

            if ($export->isLogfile()) {
                $this->logger->addDebug("logfile ");

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

                $tmpfile = $this->dependencyContainer['root.path'] . '/tmp/tmpftpbuffer' . $date->format('U') . '.txt';

                file_put_contents($tmpfile, $buffer);

                $remotefile = $date->format('U') . '-transfert.log';
                $ftp_client->chdir($export->getDestFolder());
                $ftp_client->put($remotefile, $tmpfile);
                unlink($tmpfile);
            }

            $ftp_client->close();
            unset($ftp_client);
        } catch (Exception $e) {
            $state .= $line = $e . "\n";

            $this->logger->addDebug($line);

            $export->incrementCrash();
            $this->dependencyContainer['EM']->persist($export);
            $this->dependencyContainer['EM']->flush();

            unset($ftp_client);
        }

        $this->finalize($appbox, $export);
    }

    protected function postProcessOneContent(appbox $appbox, $row)
    {
        return $this;
    }

    public function finalize(appbox $appbox, FtpExport $export)
    {
        if ($export->getCrash() >= $export->getNbretry()) {
            $this->send_mails($appbox, $export);

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
            $this->send_mails($appbox, $export);

            if ((int) $error === 0) {
                $this->dependencyContainer['EM']->remove($export);
                $this->dependencyContainer['EM']->flush();
            } else {
                $export->setCrash($export->getNbretry());
                foreach ($export->getElements() as $element) {
                    if (!$element->isError()) {
                        $this->dependencyContainer['EM']->remove($export);
                    }
                }
                $this->dependencyContainer['EM']->flush();
            }

            return $this;
        }
    }

    public function send_mails(appbox $appbox, FtpExport $export)
    {
        $transferts = array();
        $transfert_status = _('task::ftp:Tous les documents ont ete transferes avec succes');

        foreach ($export->getElements() as $element) {
            if (!$element->isError() && $element->isDone()) {
                $transferts[] =
                    '<li>' . sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s')
                        , $element->getRecordId(), $element->getFilename()
                        , phrasea::sbas_labels(phrasea::sbasFromBas($this->dependencyContainer, $element->getBaseId()), $this->dependencyContainer)
                        , phrasea::bas_labels($element->getBaseId(), $this->dependencyContainer), $element->getSubdef()) . ' : ' . _('Transfert OK') . '</li>';
            } else {
                $transferts[] =
                    '<li>' . sprintf(_('task::ftp:Record %1$s - %2$s de la base (%3$s - %4$s) - %5$s')
                        , $element->getRecordId(), $element->getFilename()
                        , phrasea::sbas_labels(phrasea::sbasFromBas($this->dependencyContainer, $element->getBaseId()), $this->dependencyContainer), phrasea::bas_labels($element->getBaseId(), $this->dependencyContainer)
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
        $mail = $export->getMail();
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
        } catch (InvalidArgumentException $e) {
            $receiver = null;
        }

        if ($receiver) {
            $mail = MailSuccessFTPSender::create($this->dependencyContainer, $receiver, null, $sender_message);
            $mail->setServer($ftp_server);
            $this->dependencyContainer['notification.deliverer']->deliver($mail);
        }

        try {
            $receiver = new Receiver(null, $mail);
            $mail = MailSuccessFTPSender::create($this->dependencyContainer, $receiver, null, $receiver_message);
            $mail->setServer($ftp_server);
            $this->dependencyContainer['notification.deliverer']->deliver($mail);
        } catch (\Exception $e) {
            $this->log('Unable to deliver success message');
        }
    }

    public function logexport(record_adapter $record, $obj, $ftpLog)
    {
        foreach ($obj as $oneObj) {
            $this->dependencyContainer['phraseanet.logger']($record->get_databox())
                ->log($record, Session_Logger::EVENT_EXPORTFTP, $ftpLog, '');
        }

        return $this;
    }

    /**
     * @param array $params
     */
    public static function getDefaultSettings(Configuration $config, array $params = array())
    {
        $period = isset($params['period']) ? $params['period'] : self::MINPERIOD;

        return sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>
            <tasksettings>
                <proxy></proxy>
                <proxyport></proxyport>
                <period>%s</period>
                <syslog></syslog>
            </tasksettings>", min(max($period, self::MINPERIOD), self::MAXPERIOD));
    }
}
