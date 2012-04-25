<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     task_manager
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_ftpPull extends task_appboxAbstract
{
    protected $debug = false;
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
            "proxy", "proxyport", "host", "port", "user"
            , "password", "ssl", "ftppath", "localpath"
            , "passive", "period"
        );
        if ($dom = @DOMDocument::loadXML($oldxml)) {
            $xmlchanged = false;
            foreach (array("str:proxy", "str:proxyport", "str:period", "boo:passive", "boo:ssl", "str:password", "str:user", "str:ftppath", "str:localpath", "str:port", "str:host") as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if ($ns = $dom->getElementsByTagName($pname)->item(0)) {
                    // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                    while (($n = $ns->firstChild))
                        $ns->removeChild($n);
                } else {
                    // le champ n'existait pas dans le xml, on le cree
                    $dom->documentElement->appendChild($dom->createTextNode("\t"));
                    $ns = $dom->documentElement->appendChild($dom->createElement($pname));
                    $dom->documentElement->appendChild($dom->createTextNode("\n"));
                }
                // on fixe sa valeur
                switch ($ptype) {
                    case "str":
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

    public function xml2graphic($xml, $form)
    {
        if (($sxml = simplexml_load_string($xml))) { // in fact XML IS always valid here...
            // ... but we could check for safe values (ex. 0 < period < 3600)
            ?>
            <script type="text/javascript">
            <?php echo $form ?>.proxy.value    = "<?php echo p4string::MakeString($sxml->proxy, "js", '"') ?>";
            <?php echo $form ?>.proxyport.value  = "<?php echo p4string::MakeString($sxml->proxyport, "js", '"') ?>";
            <?php echo $form ?>.period.value    = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";

            <?php echo $form ?>.localpath.value  = "<?php echo p4string::MakeString($sxml->localpath, "js", '"') ?>";
            <?php echo $form ?>.ftppath.value  = "<?php echo p4string::MakeString($sxml->ftppath, "js", '"') ?>";
            <?php echo $form ?>.host.value    = "<?php echo p4string::MakeString($sxml->host, "js", '"') ?>";
            <?php echo $form ?>.port.value    = "<?php echo p4string::MakeString($sxml->port, "js", '"') ?>";
            <?php echo $form ?>.user.value    = "<?php echo p4string::MakeString($sxml->user, "js", '"') ?>";
            <?php echo $form ?>.password.value  = "<?php echo p4string::MakeString($sxml->password, "js", '"') ?>";
            <?php echo $form ?>.ssl.checked    = <?php echo p4field::isyes($sxml->ssl) ? "true" : 'false' ?>;
            <?php echo $form ?>.passive.checked  = <?php echo p4field::isyes($sxml->passive) ? "true" : 'false' ?>;
            </script>
            <?php
            return("");
        } else { // ... so we NEVER come here
            // bad xml
            return("BAD XML");
        }
    }

    public function printInterfaceJS()
    {
        global $parm;
        ?>
        <script type="text/javascript">
            function chgxmltxt(textinput, fieldname)
            {
                setDirty();
            }
            function chgxmlck(checkinput, fieldname)
            {
                setDirty();
            }
            function chgxmlpopup(popupinput, fieldname)
            {
                setDirty();
            }
        </script>
        <?php
    }

    function getGraphicForm()
    {
        return true;
    }

    public function printInterfaceHTML()
    {
        global $parm;
        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <br/>
            <?php echo('task::ftp:proxy') ?>
            <input type="text" name="proxy" style="width:400px;" onchange="chgxmltxt(this, 'proxy');"><br/>
            <br/>
            <?php echo('task::ftp:proxy port') ?>
            <input type="text" name="proxyport" style="width:400px;" onchange="chgxmltxt(this, 'proxyport');"><br/>
            <br/>

            <?php echo('task::ftp:host') ?>
            <input type="text" name="host" style="width:400px;" onchange="chgxmltxt(this, 'host');"><br/>
            <br/>
            <?php echo('task::ftp:port') ?>
            <input type="text" name="port" style="width:400px;" onchange="chgxmltxt(this, 'port');"><br/>
            <br/>
            <?php echo('task::ftp:user') ?>
            <input type="text" name="user" style="width:400px;" onchange="chgxmltxt(this, 'user');"><br/>
            <br/>
            <?php echo('task::ftp:password') ?>
            <input type="password" name="password" style="width:400px;" onchange="chgxmltxt(this, 'password');"><br/>
            <br/>
            <?php echo('task::ftp:chemin distant') ?>
            <input type="text" name="ftppath" style="width:400px;" onchange="chgxmltxt(this, 'ftppath');"><br/>
            <br/>
            <?php echo('task::ftp:localpath') ?>
            <input type="text" name="localpath" style="width:400px;" onchange="chgxmltxt(this, 'localpath');"><br/>
            <br/>

            <input type="checkbox" name="passive" onchange="chgxmlck(this)">
            <?php echo _('task::ftp:mode passif') ?>
            <br/>
            <input type="checkbox" name="ssl" onchange="chgxmlck(this)">
            <?php echo _('task::ftp:utiliser SSL') ?>
            <br/>
            <?php echo('task::_common_:periodicite de la tache') ?>
            <input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');">
            &nbsp;<?php echo('task::_common_:minutes (unite temporelle)') ?><br/>
        </form>
        <?php
        $out = ob_get_clean();

        return $out;
    }

    public function saveChanges(connection_pdo $conn, $taskid, &$taskrow)
    {
        $request = http_request::getInstance();

        $parm = $request->get_parms(
            "xml", "name", "active", "proxy", "proxyport", "period"
            , "localpath", "ftppath", "port", "host", "user"
            , "password", "passive", "ssl", "debug"
        );

        if ($parm["xml"] === null) {
            // pas de xml 'raw' : on accepte les champs 'graphic view'
            if ($domTaskSettings = DOMDocument::loadXML($taskrow["settings"])) {
                $xmlchanged = false;
                foreach (array("proxy", "proxyport", "period", "host", "port", "user", "password", "ssl", "passive", "localpath", "ftppath") as $f) {
                    if ($parm[$f] !== NULL) {
                        if ($ns = $domTaskSettings->getElementsByTagName($f)->item(0)) {
                            // le champ existait dans le xml, on supprime son ancienne valeur (tout le contenu)
                            while (($n = $ns->firstChild))
                                $ns->removeChild($n);
                        } else {
                            // le champ n'existait pas dans le xml, on le cree
                            $domTaskSettings->documentElement->appendChild($domTaskSettings->createTextNode("\t"));
                            $ns = $domTaskSettings->documentElement->appendChild($domTaskSettings->createElement($f));
                            $domTaskSettings->documentElement->appendChild($domTaskSettings->createTextNode("\n"));
                        }
                        // on fixe sa valeur
                        $ns->appendChild($domTaskSettings->createTextNode($parm[$f]));
                        $xmlchanged = true;
                    }
                }
                if ($xmlchanged)
                    $parm["xml"] = $domTaskSettings->saveXML();
            }
        }

        // si on doit changer le xml, on verifie qu'il est valide
        if ($parm["xml"] && ! DOMDocument::loadXML($parm["xml"])) {
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

    protected function load_settings(SimpleXMLElement $sx_task_settings)
    {
        $this->proxy = (string) $sx_task_settings->proxy;
        $this->proxyport = (string) $sx_task_settings->proxyport;
        $this->host = (string) ($sx_task_settings->host);
        $this->port = (string) ($sx_task_settings->port);
        $this->user = (string) ($sx_task_settings->user);
        $this->password = (string) ($sx_task_settings->password);
        $this->ssl = ! ! ((string) ($sx_task_settings->ssl));
        $this->passive = ! ! ((string) ($sx_task_settings->passive));
        $this->ftppath = (string) ($sx_task_settings->ftppath);
        $this->localpath = (string) ($sx_task_settings->localpath);

        parent::load_settings($sx_task_settings);

        return $this;
    }

    protected function retrieve_content(appbox $appbox)
    {
        try {
            if ( ! is_dir($this->localpath) || ! system_file::mkdir($this->localpath))
                throw new Exception("$this->localpath is not writeable\n");

            if ( ! is_writeable($this->localpath))
                throw new Exception("$this->localpath is not writeable\n");

            $ftp = new ftpclient($this->host, $port, 90, $this->ssl, $this->proxy, $this->proxyport);
            $ftp->login($this->user, $this->password);
            $ftp->chdir($this->ftppath);
            $list_1 = $ftp->list_directory(true);

            $todo = count($list_1);
            $this->setProgress($done, $todo);

            if ($this->debug)
                echo "attente de 25sec pour avoir les fichiers froids...\n";
            sleep(25);

            $list_2 = $ftp->list_directory(true);

            foreach ($list_1 as $filepath => $timestamp) {
                $done ++;
                $this->setProgress($done, $todo);

                if ( ! isset($list_2[$filepath])) {
                    if ($this->debug)
                        echo "le fichier $filepath a disparu...\n";
                    continue;
                }
                if ($list_2[$filepath] !== $timestamp) {
                    if ($this->debug)
                        echo "le fichier $filepath a ete modifie depuis le dernier passage...\n";
                    continue;
                }

                $finalpath = p4string::addEndSlash($this->localpath) . ($filepath[0] == '/' ? mb_substr($filepath, 1) : $filepath);
                echo "Ok pour rappatriement de $filepath vers $finalpath\n";

                try {
                    if (file_exists($finalpath))
                        throw new Exception("Un fichier du meme nom ($finalpath) existe deja...");

                    system_file::mkdir(dirname($finalpath));

                    $ftp->get($finalpath, $filepath);
                    $ftp->delete($filepath);
                } catch (Exception $e) {
                    if ($this->debug)
                        echo "Erreur lors du rappatriement de $filepath : " . $e->getMessage() . "\n";
                }
            }

            $ftp->close();

            $this->setProgress(0, 0);
        } catch (Exception $e) {
            if (isset($ftp) && $ftp instanceof ftpclient)
                $ftp->close();
            echo $e->getMessage() . "\n";
        }
    }

    protected function process_one_content(appbox $appbox, Array $row)
    {

    }

    protected function post_process_one_content(appbox $appbox, Array $row)
    {

    }
}
