<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_ftpPull extends task_appboxAbstract
{
    protected $proxy;
    protected $proxyport;
    protected $host;
    protected $port;
    protected $user;
    protected $password;
    protected $ssl;
    protected $passive;
    protected $ftppath;
    protected $localpath;

    public function getName()
    {
        return(_("task::ftp:FTP Pull"));
    }

    public function help()
    {
        return '';
    }

    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            'proxy'
            , 'proxyport'
            , 'host'
            , 'port'
            , 'user'
            , 'password'
            , 'ssl'
            , 'ftppath'
            , 'localpath'
            , 'passive'
            , 'period'
        );
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        if (@$dom->loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array(
            'str:proxy'
            , 'str:proxyport'
            , 'str:period'
            , 'boo:passive'
            , 'boo:ssl'
            , 'str:password'
            , 'str:user'
            , 'str:ftppath'
            , 'str:localpath'
            , 'str:port'
            , 'str:host'
            ) as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if ($ns = $dom->getElementsByTagName($pname)->item(0)) {
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
                $xmlchanged = true;
            }
        }

        return($dom->saveXML());
    }


    public function printInterfaceJS()
    {
        global $parm;
        ?>
        <script type="text/javascript">

            function taskFillGraphic_<?php echo(get_class($this));?>(xml)
            {
                if(xml)
                {
                    xml = $.parseXML(xml);
                    xml = $(xml);

                    with(document.forms['graphicForm'])
                    {
                        proxy.value     = xml.find("proxy").text();
                        proxyport.value = xml.find("proxyport").text();
                        period.value    = xml.find("period").text();
                        localpath.value = xml.find("localpath").text();
                        ftppath.value   = xml.find("ftppath").text();
                        host.value      = xml.find("host").text();
                        port.value      = xml.find("port").text();
                        user.value      = xml.find("user").text();
                        password.value  = xml.find("password").text();
                        ssl.checked     = Number(xml.find("ssl").text()) > 0;
                        passive.checked = Number(xml.find("passive").text()) > 0;
                    }
                }
            }

            $(document).ready(function(){
                var limits = {
                    'period' :{'min':<?php echo self::MINPERIOD; ?>, 'max':<?php echo self::MAXPERIOD; ?>}
                } ;
                $(".formElem").change(function(){
                    fieldname = $(this).attr("name");
                    switch((this.nodeName+$(this).attr("type")).toLowerCase())
                    {
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
    }

    public function getInterfaceHTML()
    {
        global $parm;
        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <br/>
            <?php echo('task::ftp:proxy') ?>
            <input class="formElem" type="text" name="proxy" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:proxy port') ?>
            <input class="formElem" type="text" name="proxyport" style="width:400px;" /><br/>
            <br/>

            <?php echo('task::ftp:host') ?>
            <input class="formElem" type="text" name="host" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:port') ?>
            <input class="formElem" type="text" name="port" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:user') ?>
            <input class="formElem" type="text" name="user" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:password') ?>
            <input class="formElem" type="password" name="password" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:chemin distant') ?>
            <input class="formElem" type="text" name="ftppath" style="width:400px;" /><br/>
            <br/>
            <?php echo('task::ftp:localpath') ?>
            <input class="formElem" type="text" name="localpath" style="width:400px;" /><br/>
            <br/>

            <input class="formElem" type="checkbox" name="passive" />
            <?php echo _('task::ftp:mode passif') ?>
            <br/>
            <input class="formElem" type="checkbox" name="ssl" />
            <?php echo _('task::ftp:utiliser SSL') ?>
            <br/>
            <?php echo('task::_common_:periodicite de la tache') ?>
            <input class="formElem" type="text" name="period" style="width:40px;" />
            &nbsp;<?php echo('task::_common_:minutes (unite temporelle)') ?><br/>
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
            , 'localpath'
            , 'ftppath'
            , 'port'
            , 'host'
            , 'user'
            , 'password'
            , 'passive'
            , 'ssl'
            , 'debug'
        );

        if ($parm["xml"] === null) {
            // pas de xml 'raw' : on accepte les champs 'graphic view'
            $domdoc = new DOMDocument();
            if (($domTaskSettings = $domdoc->loadXML($taskrow["settings"])) != FALSE) {
                $xmlchanged = false;
                foreach (array(
                'proxy'
                , 'proxyport'
                , 'period'
                , 'localpath'
                , 'ftppath'
                , 'host'
                , 'port'
                , 'user'
                , 'password'
                , 'passive'
                , 'ssl'
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
        if ($parm["xml"] && ! $domdoc->loadXML($parm["xml"])) {
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
        $this->host = (string) ($sx_task_settings->host);
        $this->port = (string) ($sx_task_settings->port);
        $this->user = (string) ($sx_task_settings->user);
        $this->password = (string) ($sx_task_settings->password);
        $this->ssl = !!((string) ($sx_task_settings->ssl));
        $this->passive = !!((string) ($sx_task_settings->passive));
        $this->ftppath = (string) ($sx_task_settings->ftppath);
        $this->localpath = (string) ($sx_task_settings->localpath);

        parent::loadSettings($sx_task_settings);
    }

    protected function retrieveContent(appbox $appbox)
    {
        foreach (array('localpath', 'host', 'port', 'user', 'password', 'ftppath') as $f) {
            if (trim((string) ($this->{$f})) === '') {
                $this->log(sprintf('setting \'%s\' must be set', $f), self::LOG_ERROR);
                $this->running = FALSE;
            }
        }

        $this->dependencyContainer['filesystem']->mkdir($this->localpath, 0750);

        if (!is_dir($this->localpath)) {
            $this->log(sprintf('\'%s\' does not exists', $this->localpath), self::LOG_ERROR);
            $this->running = FALSE;
        }
        if (!is_writeable($this->localpath)) {
            $this->log(sprintf('\'%s\' is not writeable', $this->localpath), self::LOG_ERROR);
            $this->running = FALSE;
        }

        if (!$this->running) {
            $this->set_status(self::STATE_STOPPED);

            return array();
        }

        try {
            $ftp = $this->dependencyContainer['phraseanet.ftp.client']($this->host, $this->port, 90, $this->ssl, $this->proxy, $this->proxyport);
            $ftp->login($this->user, $this->password);
            $ftp->chdir($this->ftppath);
            $list_1 = $ftp->list_directory(true);

            $done = 0;
            $todo = count($list_1);
            $this->setProgress($done, $todo);

            $this->logger->addDebug("attente de 25sec pour avoir les fichiers froids...");

            $this->sleep(25);
            if (!$this->running) {
                if (isset($ftp) && $ftp instanceof ftpclient) {
                    $ftp->close();
                }

                return array();
            }

            $list_2 = $ftp->list_directory(true);

            foreach ($list_1 as $filepath => $timestamp) {
                $done++;
                $this->setProgress($done, $todo);

                if (!isset($list_2[$filepath])) {
                    $this->logger->addDebug("le fichier $filepath a disparu...\n");
                    continue;
                }
                if ($list_2[$filepath] !== $timestamp) {
                    $this->logger->addDebug("le fichier $filepath a ete modifie depuis le dernier passage...");
                    continue;
                }

                $finalpath = p4string::addEndSlash($this->localpath) . ($filepath[0] == '/' ? mb_substr($filepath, 1) : $filepath);
                $this->logger->addDebug("Ok pour rappatriement de $filepath vers $finalpath\n");

                try {
                    if (file_exists($finalpath)) {
                        throw new Exception("Un fichier du meme nom ($finalpath) existe deja...");
                    }

                    $this->dependencyContainer['filesystem']->mkdir(dirname($finalpath), 0750);

                    $ftp->get($finalpath, $filepath);
                    $ftp->delete($filepath);
                } catch (Exception $e) {
                    $this->logger->addDebug("Erreur lors du rappatriement de $filepath : " . $e->getMessage());
                }
            }

            $ftp->close();

            $this->setProgress(0, 0);
        } catch (Exception $e) {
            if (isset($ftp) && $ftp instanceof ftpclient) {
                $ftp->close();
            }
            $this->log($e->getMessage(), self::LOG_ERROR);

            return array();
        }
    }

    protected function processOneContent(appbox $appbox, Array $row)
    {

    }

    protected function postProcessOneContent(appbox $appbox, Array $row)
    {

    }
}

