<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Metadata\Tag as PhraseaTag;
use Alchemy\Phrasea\Border\Attribute as BorderAttribute;
use MediaVorus\MediaVorus;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Metadata\MetadataBag;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class task_period_archive extends task_abstract
{
    /**
     * command line args specifics
     *
     * @var string
     */
    public $argt = array();

    /**
     *
     * @var <type>
     */
    protected $sxTaskSettings = null;

    /**
     *
     * @var string
     */
    protected $msg = "";

    /**
     *
     * @var boolean
     */
    protected $move_archived = true;

    /**
     *
     * @var boolean
     */
    protected $move_error = true;

    /**
     *
     * @var \Entities\LazaretSession
     */
    protected $lazaretSession;

    /**
     *
     * @return string
     */
    public function getName()
    {
        return(_('task::archive:Archivage'));
    }

    /**
     *
     * @param  string $oldxml
     * @return string
     */
    public function graphic2xml($oldxml)
    {
        $request = http_request::getInstance();

        $parm2 = $request->get_parms(
            "base_id"
            , "hotfolder"
            , "period"
            , "move_archived"
            , "move_error"
            , "copy_spe"
            , "delfolder"
            , 'cold'
        );
        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $dom->preserveWhiteSpace = false;
        if ($dom->loadXML($oldxml)) {
            $xmlchanged = false;
            // foreach($parm2 as $pname=>$pvalue)
            foreach (array("str:base_id", "str:hotfolder", "str:period", "boo:move_archived", "boo:move_error", "boo:delfolder", 'boo:copy_spe', 'str:cold') as $pname) {
                $ptype = substr($pname, 0, 3);
                $pname = substr($pname, 4);
                $pvalue = $parm2[$pname];
                if (($ns = $dom->getElementsByTagName($pname)->item(0)) != null) {
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

    /**
     * xml2graphic : must fill the graphic form (using js) from xml
     *
     * @param  string $xml
     * @param  string $form
     * @return Void
     */
    public function xml2graphic($xml, $form)
    {
        if (($sxml = simplexml_load_string($xml)) != FALSE) { // in fact XML IS always valid here...
            // ... but we could check for safe values (ex. 0 < period < 3600)
            if ((int) ($sxml->period) < 10) {
                $sxml->period = 10;
            } elseif ((int) ($sxml->period) > 3600) {
                $sxml->period = 3600;
            }
            if ((int) ($sxml->cold) < 5) {
                $sxml->cold = 5;
            } elseif ((int) ($sxml->cold) > 300) {
                $sxml->cold = 300;
            }
            ?>
            <script type="text/javascript">
                var i;
                var opts = <?php echo $form ?>.base_id.options;
                var basefound = 0;
                for (i=1; basefound==0 && i<opts.length; i++) {
                    if(opts[i].value == "<?php echo p4string::MakeString($sxml->base_id, "form") ?>")
                    basefound = i;
                }
                opts[basefound].selected = true;
            <?php echo $form ?>.hotfolder.value       = "<?php echo p4string::MakeString($sxml->hotfolder, "js", '"') ?>";
            <?php echo $form ?>.period.value          = "<?php echo p4string::MakeString($sxml->period, "js", '"') ?>";
            <?php echo $form ?>.cold.value            = "<?php echo p4string::MakeString($sxml->cold, "js", '"') ?>";
            <?php echo $form ?>.move_archived.checked = <?php echo p4field::isyes($sxml->move_archived) ? "true" : "false" ?>;
            <?php echo $form ?>.move_error.checked    = <?php echo p4field::isyes($sxml->move_error) ? "true" : "false" ?>;
            <?php echo $form ?>.delfolder.checked     = <?php echo p4field::isyes($sxml->delfolder) ? "true" : "false" ?>;
            <?php echo $form ?>.copy_spe.checked      = <?php echo p4field::isyes($sxml->copy_spe) ? "true" : "false" ?>;
            </script>
            <?php

            return("");
        } else { // ... so we NEVER come here

            // bad xml
            return("BAD XML");
        }
    }

    /**
     * printInterfaceJS() : generer le code js de l'interface 'graphic view'
     *
     * @return Void
     */
    public function printInterfaceJS()
    {
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

        return;
    }

    /**
     *  getInterfaceHTML(..) : retourner l'interface 'graphic view'
     *
     * @return Void
     */
    public function getInterfaceHTML()
    {
        $appbox = appbox::get_instance(\bootstrap::getCore());

        ob_start();
        ?>
        <form name="graphicForm" onsubmit="return(false);" method="post">
            <?php echo _('task::archive:archivage sur base/collection/') ?> :

            <select onchange="chgxmlpopup(this, 'base_id');" name="base_id">
                <option value="">...</option>
                <?php
                foreach ($appbox->get_databoxes() as $databox) {
                    foreach ($databox->get_collections() as $collection) {
                        print("<option value=\"" . $collection->get_base_id() . "\">" . $databox->get_viewname() . " / " . $collection->get_name() . "</option>");
                    }
                }
                ?>
            </select>
            <br/>
            <br/>
            <?php echo _('task::_common_:hotfolder') ?>
            <input type="text" name="hotfolder" style="width:400px;" onchange="chgxmltxt(this, 'hotfolder');" value=""><br/>
            <br/>
            <?php echo _('task::_common_:periodicite de la tache') ?>&nbsp;:&nbsp;
            <input type="text" name="period" style="width:40px;" onchange="chgxmltxt(this, 'period');" value="">&nbsp;<?php echo _('task::_common_:secondes (unite temporelle)') ?><br/>
            <br/>
            <?php echo _('task::archive:delai de \'repos\' avant traitement') ?>&nbsp;:&nbsp;
            <input type="text" name="cold" style="width:40px;" onchange="chgxmltxt(this, 'cold');" value="">&nbsp;<?php echo _('task::_common_:secondes (unite temporelle)') ?><br/>
            <br/>
            <input type="checkbox" name="move_archived" onchange="chgxmlck(this, 'move_archived');">&nbsp;<?php echo _('task::archive:deplacer les fichiers archives dans _archived') ?>
            &nbsp;&nbsp;&nbsp;&nbsp;
            <input type="checkbox" name="move_error" onchange="chgxmlck(this, 'move_error');">&nbsp;<?php echo _('task::archive:deplacer les fichiers non-archives dans _error') ?><br/>
            <br/>
            <input type="checkbox" name="copy_spe" onchange="chgxmlck(this, 'copy_spe');">&nbsp;<?php echo _('task::archive:copier les fichiers \'.phrasea.xml\' et \'.grouping.xml\' dans _archived') ?><br/>
            <br/>
            <input type="checkbox" name="delfolder" onchange="chgxmlck(this, 'delfolder');">&nbsp;<?php echo _('task::archive:supprimer les repertoires apres archivage') ?><br/>
        </form>
        <?php

        return ob_get_clean();
    }

    /**
     *
     * @return string
     */
    public function help()
    {
        return(_("task::archive:Archiving files found into a 'hotfolder'"));
    }
    protected $sbas_id;

    /**
     *
     * @return void
     */
    protected function run2()
    {
        $this->debug = FALSE;

        $core = \bootstrap::getCore();
        $conn = connection::getPDOConnection();

        $this->sxTaskSettings = simplexml_load_string($this->settings);

        $base_id = (int) ($this->sxTaskSettings->base_id);
        $this->sbas_id = phrasea::sbasFromBas($base_id);

        if ( ! $this->sbas_id) {
            $this->log('base_id unknown');

            return('tostop');
        }

        $databox = databox::get_instance($this->sbas_id);

        $this->TColls = array();
        $collection = null;
        foreach ($databox->get_collections() as $coll) {
            $this->TColls['c' . $coll->get_coll_id()] = $coll->get_coll_id();
            if ($base_id == $coll->get_base_id()) {
                $collection = $coll;
            }
        }
        $server_coll_id = $collection->get_coll_id();

        $this->tmask = array(); // mask(s) of accepted files
        $this->tmaskgrp = array();
        $this->period = 60;
        $this->cold = 30;

        if (($this->sxBasePrefs = simplexml_load_string($collection->get_prefs())) != FALSE) {
            $this->sxBasePrefs["id"] = $base_id;

            $this->period = (int) ($this->sxTaskSettings->period);
            if ($this->period <= 0 || $this->period >= 3600) {
                $this->period = 60;
            }

            $this->cold = (int) ($this->sxTaskSettings->cold);
            if ($this->cold <= 0 || $this->cold >= 300) {
                $this->cold = 30;
            }

            // check the data-repository exists
            $pathhd = (string) ($this->sxBasePrefs->path);

            if ($pathhd) {

                $core['file-system']->mkdir($pathhd, 0750);

                if ( ! is_dir($pathhd)) {
                    $this->log(sprintf(_('task::archive:Can\'t create or go to folder \'%s\''), $pathhd));
                    $this->running = false;

                    return;
                }
            }

            // load masks
            if ($this->sxTaskSettings->files && $this->sxTaskSettings->files->file) {
                foreach ($this->sxTaskSettings->files->file as $ft) {
                    $this->tmask[] = array(
                        "mask"    => (string) $ft["mask"]
                        , "caption" => (string) $ft["caption"]
                        , "accept"  => (string) $ft["accept"]
                    );
                }
            }
            if ($this->sxTaskSettings->files && $this->sxTaskSettings->files->grouping) {
                foreach ($this->sxTaskSettings->files->grouping as $ft) {
                    $this->tmaskgrp[] = array(
                        "mask"           => (string) $ft["mask"]
                        , "caption"        => (string) $ft["caption"]
                        , "representation" => (string) $ft["representation"]
                        , "accept"         => (string) $ft["accept"]
                    );
                }
            }
            if (count($this->tmask) == 0) {
                // no mask defined : accept all kind of files
                $this->tmask[] = array("mask"    => ".*", "caption" => "", "accept"  => "");
            }

            // main loop
            $this->running = true;
            $loop = 0;
            while ($this->running) {
                try {
                    $conn = connection::getPDOConnection();
                } catch (Exception $e) {
                    $this->log($e->getMessage());
                    if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                        $this->log(("Warning : abox connection lost, restarting in 10 min."));

                         // DON'T do sleep(600) because it prevents ticks !
                         for ($t = 60 * 10; $this->running && $t; $t -- ) {
                            sleep(1);
                        }
                        // because connection is lost we cannot change status to 'torestart'
                        // anyway the current status 'running' with no pid
                        // will enforce the scheduler to restart the task
                    } else {
                        $this->log(("Error : abox connection lost, quitting."));
                        // runner = manual : can't restart so simply quit
                    }
                    $this->running = FALSE;

                    return;
                }

                $path_in = (string) ($this->sxTaskSettings->hotfolder);
                if ( ! @is_dir($path_in)) {
                    if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                        $this->log(sprintf(('Warning : missing hotfolder \'%s\', restarting in 10 min.'), $path_in));

                         // DON'T do sleep(600) because it prevents ticks !
                        for ($t = 60 * 10; $this->running && $t; $t -- ) {
                            sleep(1);
                        }
                        if ($this->getState() === self::STATE_STARTED) {
                            $this->setState(self::STATE_TORESTART);
                        }
                    } else {
                        $this->log(sprintf(('Error : missing hotfolder \'%s\', stopping.'), $path_in));
                        // runner = manual : can't restart so simply quit
                        $this->setState(self::STATE_STOPPED);
                    }
                    $this->running = FALSE;

                    return;
                }

                $this->setLastExecTime();

                try {
                    if ( ! ($this->sxTaskSettings = @simplexml_load_string($this->getSettings()))) {
                        throw new Exception(sprintf('Error fetching or reading settings of the task \'%d\'', $this->getID()));
                    } else {
                        // copy settings to task, so it's easier to get later
                        $this->move_archived = p4field::isyes($this->sxTaskSettings->move_archived);
                        $this->move_error = p4field::isyes($this->sxTaskSettings->move_error);

                        $period = (int) ($this->sxTaskSettings->period);
                        if ($period <= 0 || $period >= 3600) {
                            $period = 60;
                        }
                        $cold = (int) ($this->sxTaskSettings->cold);
                        if ($cold <= 0 || $cold >= 300) {
                            $cold = 30;
                        }
                    }
                } catch (Exception $e) {
                    if ($this->getRunner() == self::RUNNER_SCHEDULER) {
                        $this->log(sprintf(('Warning : error fetching or reading settings of the task \'%d\', restarting in 10 min.'), $this->getID()));

                        $this->sleep(60 * 10);

                        if ($this->getState() === self::STATE_STARTED) {
                            $this->setState(self::STATE_TORESTART);
                        }
                    } else {
                        $this->log(sprintf(('Error : error fetching task \'%d\', stopping.'), $this->getID()));
                        // runner = manual : can't restart so simply quit
                        $this->setState(self::STATE_STOPPED);
                    }
                    $this->running = FALSE;

                    return;
                }

                $status = $this->getState();

                if ($status == self::STATE_TOSTOP) {
                    $this->running = FALSE;

                    return;
                }

                $duration = time();
                $r = $this->archiveHotFolder($server_coll_id);

                if ($loop > 10) {
                    $r = 'MAXLOOP';
                }

                switch ($r) {
                    case 'TOSTOP':
                        $this->setState(self::STATE_STOPPED);
                        $this->running = FALSE;
                        break;
                    case 'WAIT':
                        $this->setState(self::STATE_STOPPED);
                        $this->running = FALSE;
                        break;
                    case 'BAD':
                        $this->setState(self::STATE_STOPPED);
                        $this->running = FALSE;
                        break;
                    case 'NORECSTODO':
                        $duration = time() - $duration;
                        if ($duration < ($period + $cold)) {
                            for ($i = 0; $i < (($period + $cold) - $duration) && $this->running; $i ++ ) {
                                $s = $this->getState();
                                if ($s == self::STATE_TOSTOP) {
                                    $this->setState(self::STATE_STOPPED);
                                    $this->running = FALSE;
                                } else {
                                    sleep(1);
                                }
                            }
                        }
                        break;
                    case 'MAXRECSDONE':
                    case 'MAXMEMORY':
                    case 'MAXLOOP':
                        if ($status == self::STATE_STARTED && $this->getRunner() !== self::RUNNER_MANUAL) {
                            $this->setState(self::STATE_TORESTART);
                            $this->running = FALSE;
                        }
                        break;
                    default:
                        if ($status == self::STATE_STARTED) {
                            $this->setState(self::STATE_STOPPED);
                            $this->running = FALSE;
                        }
                        break;
                }
                $loop ++;
            }
        }
    }

    /**
     *
     * @param  int $server_coll_id
     * @return string
     */
    protected function archiveHotFolder($server_coll_id)
    {
        clearstatcache();

        connection::getPDOConnection();
        \databox::get_instance($this->sbas_id)->get_connection();

        $path_in = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
        if ( ! @is_file($path_in . "/.phrasea.xml")) {
            $this->log(sprintf(('NO .phrasea.xml AT ROOT v2 \'%s\' !'), $path_in));

            return('WAIT');
        }

        $path_archived = $path_error = null;
        if ($this->move_archived) {
            $path_archived = $path_in . '_archived';
            @mkdir($path_archived, 0755, true);
            if ( ! file_exists($path_archived)) {
                $this->log(sprintf(('Can\'t create folder \'%s\' !'), $path_archived));

                return('BAD');
            }
        }
        if ($this->move_error) {
            $path_error = $path_in . '_error';
            @mkdir($path_error, 0755, true);
            if ( ! file_exists($path_error)) {
                $this->log(sprintf(('archive:Can\'t create folder \'%s\' !'), $path_error));

                return('BAD');
            }
        }

        $dom = new DOMDocument();
        $dom->formatOutput = true;
        $root = $dom->appendChild($dom->createElement('root'));

        $this->movedFiles = 0;
        $this->archivedFiles = 0;
        $this->path_in = $path_in;

        $nnew = $this->listFilesPhase1($dom, $root, $path_in, $server_coll_id);
        if ($this->debug) {
            $this->log("=========== listFilesPhase1 ========== (returned " . $nnew . ")\n" . $dom->saveXML());
        }

        // special case : status has changed to TOSTOP while listing files
        if ($nnew === 'TOSTOP') {
            return('TOSTOP');
        }

        // wait for files to be cold
        $cold = (int) ($this->sxTaskSettings->cold);
        if ($cold <= 0 || $cold >= 300) {
            $cold = 30;
        }

        while ($this->running && $cold > 0) {
            $s = $this->getState();
            if ($s == self::STATE_TOSTOP) {
                return('TOSTOP');
            }
            sleep(2);
            $cold -= 2;
        }

        $nnew = $this->listFilesPhase2($dom, $root, $path_in);
        if ($this->debug) {
            $this->log("=========== listFilesPhase2 ========== : \n" . $dom->saveXML());
        }

        // special case : status has changed to TOSTOP while listing files
        if ($nnew === 'TOSTOP') {
            return('TOSTOP');
        }

        $this->makePairs($dom, $root, $path_in, $path_archived, $path_error);
        if ($this->debug) {
            $this->log("=========== makePairs ========== : \n" . $dom->saveXML());
        }

        $r = $this->removeBadGroups($dom, $root, $path_in, $path_archived, $path_error);
        if ($this->debug) {
            $this->log("=========== removeBadGroups ========== (returned " . ($r ? 'true' : 'false') . ") : \n" . $dom->saveXML());
        }

        $this->archive($dom, $root, $path_in, $path_archived, $path_error);
        if ($this->debug) {
            $this->log("=========== archive ========== : \n" . $dom->saveXML());
        }

        $this->bubbleResults($dom, $root, $path_in);
        if ($this->debug) {
            $this->log("=========== bubbleResults ========== : \n" . $dom->saveXML());
        }

        $r = $this->moveFiles($dom, $root, $path_in, $path_archived, $path_error);
        if ($this->debug) {
            $this->log("=========== moveFiles ========== (returned " . ($r ? 'true' : 'false') . ") : \n" . $dom->saveXML());
        }

        if ($this->movedFiles) {

            // something happened : a least one file has moved
            return('MAXRECSDONE');
        } elseif (memory_get_usage() >> 20 > 25) {

            return('MAXMEMORY');
        } else {

            return('NORECSTODO');
        }
    }

    /**
     *
     * @param  string $f
     * @return boolean
     */
    protected function isIgnoredFile($f)
    {
        $f = strtolower($f);

        return(($f[0] == '.' && $f != '.phrasea.xml' && $f != '.grouping.xml') || $f == 'thumbs.db' || $f == 'par-system');
    }

    /**
     * check if the file matches any mask, and flag the 'caption' file if found
     *
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @return void
     */
    protected function checkMatch($dom, $node)
    {
        $file = $node->getAttribute('name');

        foreach ($this->tmask as $mask) {
            $preg_mask = '/' . $mask['mask'] . '/';
            if (preg_match($preg_mask, $file)) {
                if ($mask['caption']) {
                    // caption in a linked file ?
                    $captionFileName = @preg_replace($preg_mask, $mask['caption'], $file);
                    $xpath = new DOMXPath($dom);
                    $dnl = $xpath->query('./file[@name="' . $captionFileName . '"]', $node->parentNode);
                    if ($dnl->length == 1) {
                        // the caption file exists
                        $node->setAttribute('match', $captionFileName);
                        $dnl->item(0)->setAttribute('match', '*');
                    } else {
                        // the caption file is missing
                        $node->setAttribute('match', '?');
                    }
                } else {
                    // self-described
                    $node->setAttribute('match', '.');
                }

                // first match is ok
                break;
            }
        }

        return;
    }

    /**
     * Phase 1 :
     *  list every file, all 'hot'
     *  read .phrasea.xml files
     *
     * @param  DOMDocument $dom
     * @param  DomElement $node
     * @param  string $path
     * @param  int $server_coll_id
     * @return int|string the number on found files or "TOSTOP" if task is to stop
     */
    private function listFilesPhase1($dom, $node, $path, $server_coll_id, $depth = 0)
    {
        static $time0 = null;
        if ($depth == 0) {
            $time0 = time();
        }

        $nnew = 0;

        try {
            $listFolder = new CListFolder($path);

            if (($sxDotPhrasea = @simplexml_load_file($path . '/.phrasea.xml')) != FALSE) {

                // test for magic file
                if (($magicfile = trim((string) ($sxDotPhrasea->magicfile))) != '') {
                    $magicmethod = strtoupper($sxDotPhrasea->magicfile['method']);
                    if ($magicmethod == 'LOCK' && file_exists($path . '/' . $magicfile)) {
                        return;
                    } elseif ($magicmethod == 'UNLOCK' && ! file_exists($path . '/' . $magicfile)) {
                        return;
                    }
                }

                // change collection ?
                if (($new_cid = $sxDotPhrasea['collection']) != '') {
                    if (isset($this->TColls['c' . $new_cid])) {
                        $server_coll_id = $new_cid;
                    } else {
                        $this->log(sprintf(('Unknown coll_id (%1$d) in "%2$s"'), (int) $new_cid, $path . '/.phrasea.xml'));
                        $server_coll_id = -1;
                    }
                }
                $node->setAttribute('pxml', '1');
            }

            $iloop = 0;
            $time0 = time();
            while (($file = $listFolder->read()) !== null) {
                 // each 2 secs, check the status of the task
                if (time() - $time0 >= 2) {
                    if ($this->getState() == self::STATE_TOSTOP) {
                        $nnew = 'TOSTOP'; // since we will return a string...
                        break;    // ...we can check it against numerical result
                    }
                    $time0 = time();
                }

                if (($iloop ++ % 100) == 0) {
                    usleep(1000);
                }

                if ($this->isIgnoredFile($file)) {
                    continue;
                }

                if (is_dir($path . '/' . $file)) {
                    $n = $node->appendChild($dom->createElement('file'));
                    $n->setAttribute('isdir', '1');
                    $n->setAttribute('name', $file);

                    $_nnew_ = $this->listFilesPhase1($dom, $n, $path . '/' . $file, $server_coll_id, $depth + 1);
                    if ($_nnew_ === 'TOSTOP') {
                        $nnew = 'TOSTOP'; // special case to quit recursion
                        break;
                    } else {
                        $nnew += $_nnew_; // normal case, _nnew_ is a number
                    }
                } else {
                    $n = $node->appendChild($dom->createElement('file'));
                    $n->setAttribute('name', $file);
                    $stat = stat($path . '/' . $file);
                    foreach (array("size", "ctime", "mtime") as $k) {
                        $n->setAttribute($k, $stat[$k]);
                    }
                    $nnew ++;
                }
                $n->setAttribute('cid', $server_coll_id);

                $n->setAttribute('temperature', 'hot');
            }
        } catch (Exception $e) {

        }

        return($nnew);
    }

    /**
     * Phase 2 :
     *   list again and flag dead files as 'cold'
     *
     * @staticvar int $iloop
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  int $depth
     * @return int|string the number of NEW files or "TOSTOP" if task is to stop
     */
    private function listFilesPhase2($dom, $node, $path, $depth = 0)
    {
        static $iloop = 0;
        static $time0 = NULL;
        if ($depth == 0) {
            $iloop = 0;
            $time0 = time();
        }

        $nnew = 0;

        try {
            $listFolder = new CListFolder($path);

            $xp = new DOMXPath($dom);

            if (($sxDotPhrasea = @simplexml_load_file($path . '/.phrasea.xml')) != FALSE) {

                // test magicfile
                if (($magicfile = trim((string) ($sxDotPhrasea->magicfile))) != '') {
                    $magicmethod = strtoupper($sxDotPhrasea->magicfile['method']);
                    if ($magicmethod == 'LOCK' && file_exists($path . '/' . $magicfile)) {
                        return 0;
                    } elseif ($magicmethod == 'UNLOCK' && ! file_exists($path . '/' . $magicfile)) {
                        return 0;
                    }
                }
            }

            while (($file = $listFolder->read()) !== null) {
                 // each 2 secs, check the status of the task
                if (time() - $time0 >= 2) {
                    if ($this->getState() == self::STATE_TOSTOP) {
                        $nnew = 'TOSTOP'; // since we will return a string...
                        break;    // ...we can check it against numerical result
                    }
                    $time0 = time();
                }

                if ($this->isIgnoredFile($file)) {
                    continue;
                }

                if (($iloop ++ % 100) == 0) {
                    usleep(500);
                }

                $dnl = @$xp->query('./file[@name="' . $file . '"]', $node);
                if ($dnl && $dnl->length == 0) {
                    if (is_dir($path . '/' . $file)) {
                        $n = $node->appendChild($dom->createElement('file'));
                        $n->setAttribute('isdir', '1');
                        $n->setAttribute('name', $file);

                        $_nnew_ = $this->listFilesPhase2($dom, $n, $path . '/' . $file, $depth + 1);
                        if ($_nnew_ === 'TOSTOP') {
                            $nnew = 'TOSTOP'; // special case to quit recursion
                            break;
                        } else {
                            $nnew += $_nnew_; // normal case, _nnew_ is a number
                        }
                    } else {
                        $n = $node->appendChild($dom->createElement('file'));
                        $n->setAttribute('name', $file);
                        $nnew ++;
                    }
                    $this->setBranchHot($dom, $n);
                } elseif ($dnl && $dnl->length == 1) {
                    $dnl->item(0)->setAttribute('temperature', 'cold');
                    // $dnl->item(0)->removeAttribute('hot');
                    if (is_dir($path . '/' . $file)) {
                        $this->listFilesPhase2($dom, $dnl->item(0), $path . '/' . $file, $depth + 1);
                    } else {
                        $stat = stat($path . '/' . $file);
                        foreach (array("size", "ctime", "mtime") as $k) {
                            if ($dnl->item(0)->getAttribute($k) != $stat[$k]) {
                                $this->setBranchHot($dom, $dnl->item(0));
                                break;
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {

        }

        return $nnew;
    }

    /**
     * makePairs :
     *  flag files to be archived (make pairs with linked caption when needed)
     *  flag grp folders and their linked files (caption, representation)
     *  declare uncomplete grp as error
     *
     * @staticvar int $iloop
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  boolean $inGrp
     * @param  int $depth
     * @return void
     */
    private function makePairs($dom, $node, $path, $path_archived, $path_error, $inGrp = false, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        if ($depth == 0 && ($node->getAttribute('temperature') == 'hot' || $node->getAttribute('cid') == '-1')) {
            return;
        }

        $xpath = new DOMXPath($dom); // useful

        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop ++ % 100) == 0) {
                usleep(1000);
            }

            // make xml lighter (free ram)
            foreach (array("size", "ctime", "mtime") as $k) {
                $n->removeAttribute($k);
            }

            if ($n->getAttribute('temperature') == 'hot' || $n->getAttribute('cid') == '-1') {
                continue;
            }

            $name = $n->getAttribute('name');
            if ($n->getAttribute('isdir') == '1') {
                if (($grpSettings = $this->getGrpSettings($name)) !== FALSE) { // get 'caption', 'representation'
                    // this is a grp folder, we check it
                    $dnl = $xpath->query('./file[@name=".grouping.xml"]', $n);
                    if ($dnl->length == 1) {
                        // this group is old (don't care about any linked files), just flag it
                        $n->setAttribute('grp', 'tocomplete');
                        $dnl->item(0)->setAttribute('match', '*');
                        // recurse only if group is ok
                        $this->makePairs($dom, $n, $path . '/' . $name, true, $depth + 1);
                    } else {
                        // this group in new (to be created)
                        // do we need one (or both) linked file ? (caption or representation)
                        $err = false;
                        $flink = array('caption'        => null, 'representation' => null);

                        foreach ($flink as $linkName => $v) {
                            if (isset($grpSettings[$linkName]) && $grpSettings[$linkName] != '') {
                                // we need this linked file, calc his real name
                                $f = preg_replace('/' . $grpSettings['mask'] . '/i', $grpSettings[$linkName], $name);

                                $dnl = $xpath->query('./file[@name="' . $f . '"]', $node);
                                if ($dnl->length == 1) {
                                    $flink[$linkName] = $dnl->item(0);  // it's here
                                } else {
                                    $this->log(sprintf(('missing linked file \'%1$s\' to group \'%2$s\''), $f, $name));
                                    $err = true;        // missing -> error
                                }
                            }
                        }

                        if ( ! $err) {
                            // the group is ok, flag it ...
                            $n->setAttribute('grp', 'tocreate');

                            // ... as the existing linked file(s) ...
                            foreach ($flink as $linkName => $v) {
                                if ($v) {  // this linked file exists
                                    $v->setAttribute('match', '*');
                                    $n->setAttribute('grp_' . $linkName, $v->getAttribute('name'));
                                }
                            }
                            // recurse only if group is ok
                            $this->makePairs($dom, $n, $path . '/' . $name
                                , $path_archived . '/' . $name
                                , $path_error . '/' . $name
                                , true, $depth + 1);
                        } else {
                            // something is missing, the whole group goes error, ...
                            $n->setAttribute('grp', 'todelete');

                            $this->setAllChildren($dom, $n, array('error' => '1'));

                            // bubble to the top
                            for ($nn = $n; $nn && $nn->nodeType == XML_ELEMENT_NODE; $nn = $nn->parentNode) {
                                $nn->setAttribute('error', '1');
                            }

                            // ... as the existing linked file(s) ...
                            foreach ($flink as $linkName => $v) {
                                if ($v)  // this linked file exists, it goes error also
                                    $v->setAttribute('error', '1');
                            }
                        }
                    }
                } else {
                    // not a grp folder, recurse
                    $this->makePairs($dom, $n, $path . '/' . $name
                        , $path_archived . '/' . $name
                        , $path_error . '/' . $name
                        , $inGrp, $depth + 1);
                }
            } else {
                // this is a file
                if ( ! $n->getAttribute('match')) { // because match can be set before
                    if ($name == '.phrasea.xml') {
                        $n->setAttribute('match', '*');  // special file(s) always ok
                    } else {
                        $this->checkMatch($dom, $n);
                    }
                }
            }
        }

        // scan again for unmatched files
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ( ! $n->getAttribute('isdir') == '1' && ! $n->getAttribute('match')) {
                // still no match, now it's an error (bubble to the top)
                for ($nn = $n; $nn && $nn->nodeType == XML_ELEMENT_NODE; $nn = $nn->parentNode) {
                    $nn->setAttribute('error', '1');
                }
            }
        }

        return;
    }

    /**
     * removeBadGroups :
     *   move files to archived or error dir
     *
     * @staticvar int $iloop
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  int $depth
     * @return void
     */
    private function removeBadGroups($dom, $node, $path, $path_archived, $path_error, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        $ret = false;

        // if root of hotfolder if hot, die...
        if ($depth == 0 && $node->getAttribute('temperature') == 'hot') {
            return($ret);
        }

        $nodesToDel = array();
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop ++ % 20) == 0) {
                usleep(1000);
            }

            if ($n->getAttribute('temperature') == 'hot') {
                continue; // do not move hotfiles
            }

            $name = $n->getAttribute('name');

            if ($n->getAttribute('isdir')) {
                // a dir
                $ret |= $this->removeBadGroups($dom, $n, $path . '/' . $name
                    , $path_archived . '/' . $name
                    , $path_error . '/' . $name
                    , $depth + 1);
                if ($n->getAttribute('grp') == 'todelete') {
                    $nodesToDel[] = $n;
                    @unlink($path . '/' . $name);
                }
            } else {
                // a file
                if ($n->getAttribute('error')) {
                    if ($this->move_error) {
                        $rootpath = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
                        $subpath = substr($path, strlen($rootpath));
                        $this->log(sprintf(('copy \'%s\' to \'error\''), $subpath . '/' . $name));

                        @mkdir($path_error, 0755, true);
                        @copy($path . '/' . $name, $path_error . '/' . $name);
                    }

                    $nodesToDel[] = $n;
                    @unlink($path . '/' . $name);

                    $this->movedFiles ++;
                }
            }
        }

        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }

        return;
    }

    /**
     * archive :
     *   archive files
     *   do special work on grp folders
     *
     * @staticvar int $iloop
     * @param  DOMDOcument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  int $depth
     * @return void
     */
    private function archive($dom, $node, $path, $path_archived, $path_error, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        if ($node->getAttribute('temperature') == 'hot') {
            return;
        }

        $nodesToDel = array();
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop ++ % 20) == 0) {
                usleep(1000);
            }

            if ($n->getAttribute('temperature') == 'hot') {
                continue;
            }

            if ($n->getAttribute('cid') == '-1') {
                $n->setAttribute('error', '1');
                continue;
            }

            if ($n->getAttribute('isdir') == '1') {
                if ($n->getAttribute('grp')) {
                    // a grp folder : special work
                    $this->ArchiveGrp($dom, $n, $path, $path_archived, $path_error, $nodesToDel);
                } else {
                    // ...normal subfolder : recurse
                    $name = $n->getAttribute('name');
                    $this->archive($dom, $n, $path . '/' . $name
                        , $path_archived . '/' . $name
                        , $path_error . '/' . $name
                        , $depth + 1);
                }
            } else {
                // a file
                $this->archiveFile($dom, $n, $path, $path_archived, $path_error, $nodesToDel, 0); // 0 = no grp
            }
        }
        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }

        // at the end of recursion, restore the magic file (create or delete it)
        if (($magicfile = $node->getAttribute('magicfile')) != '') {
            $magicmethod = $node->getAttribute('magicmethod');
            if ($magicmethod == 'LOCK') {
                file_put_contents($path . '/' . $magicfile, '');
            } elseif ($magicmethod == 'UNLOCK') {
                unlink($path . '/' . $magicfile);
            }
        }

        return;
    }

    /**
     * bubbleResults :
     *   bubble result attributes ('keep', 'archived', 'error') up to top,
     *   to help creation of result folders and cleaning of the hotfolder
     *
     * @staticvar int $iloop
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  int $depth
     * @return int  flags used only inside recursion
     */
    private function bubbleResults($dom, $node, $path, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        if ($node->getAttribute('temperature') == 'hot') {
            return;
        }

        $ret = 0;
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop ++ % 20) == 0) {
                usleep(1000);
            }

            if ($n->getAttribute('name') == '.phrasea.xml' || $n->getAttribute('name') == '.grouping.xml') {
                // special files stay in place AND are copied into 'archived'
                $n->setAttribute('keep', '1');
                if (p4field::isyes($this->sxTaskSettings->copy_spe)) {
                    $n->setAttribute('archived', '1');
                }
            }
            if ($n->getAttribute('keep') == '1') {
                $ret |= 1;
            }
            if ($n->getAttribute('archived') == '1') {
                $ret |= 2;
            }
            if ($n->getAttribute('error') == '1') {
                $ret |= 4;
            }
            if ($n->getAttribute('isdir') == '1') {
                $ret |= $this->bubbleResults($dom, $n, $path . '/' . $n->getAttribute('name'), $depth + 1);
            }
        }
        if ($ret & 1) {
            $node->setAttribute('keep', '1');
        }
        if ($ret & 2) {
            $node->setAttribute('archived', '1');
        }
        if ($ret & 4) {
            $node->setAttribute('error', '1');
        }

        return($ret);
    }

    /**
     * Phase 5 :
     *   move files to archived or error dir
     *
     * @staticvar int $iloop
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  int $depth
     * @return boolean  at least one file was moved
     */
    private function moveFiles($dom, $node, $path, $path_archived, $path_error, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        $ret = false;

        // if root of hotfolder if hot, die...
        if ($depth == 0 && $node->getAttribute('temperature') == 'hot') {
            return($ret);
        }

        $nodesToDel = array();
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop ++ % 20) == 0) {
                usleep(1000);
            }

            if ($n->getAttribute('temperature') == 'hot') { // do not move hotfiles
                continue;
            }

            $name = $n->getAttribute('name');

            if ($n->getAttribute('isdir')) {
                $ret |= $this->moveFiles($dom, $n, $path . '/' . $name
                    , $path_archived . '/' . $name
                    , $path_error . '/' . $name
                    , $depth + 1);

                if ( ! $n->firstChild) {
                    $nodesToDel[] = $n;
                }
// ----- JY 20100318 : DO NOT DELETE EMPTY FOLDERS ANYMORE, AS THEY MAY DISAPEAR TOO SOON -----
//        if(!$n->getAttribute('keep'))
//          @rmdir($path.'/'.$name);
// --------------------------------------------------------------------------------------------
            } else {
                $rootpath = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
                $subpath = substr($path, strlen($rootpath));

                if ($n->getAttribute('archived') && $this->move_archived) {
                    $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $name));

                    @mkdir($path_archived, 0755, true);
                    @copy($path . '/' . $name, $path_archived . '/' . $name);
                    if ( ! $n->getAttribute('keep')) { // do not count copy of special files as a real event
                        $nodesToDel[] = $n;
                        $ret = true;
                    }
                }

                if ($n->getAttribute('error') && $this->move_error) {
                    $this->log(sprintf(('copy \'%s\' to \'error\''), $subpath . '/' . $name));

                    @mkdir($path_error, 0755, true);
                    @copy($path . '/' . $name, $path_error . '/' . $name);
                    if ( ! $n->getAttribute('keep')) { // do not count copy of special files as a real event
                        $nodesToDel[] = $n;
                        $ret = true;
                    }
                }

                if ( ! $n->getAttribute('keep')) {
                    $this->log(sprintf(('delete \'%s\''), $subpath . '/' . $name));
                    if (@unlink($path . '/' . $name)) {
                        $this->movedFiles ++;
                    } else {
                        $this->log(sprintf(("Warning : can't delete '%s' from hotfolder, task is stopped"), $subpath . '/' . $name));
                    }
                }
            }
        }

        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }

        return($ret);
    }

    /**
     *
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @return void
     */
    private function setBranchHot($dom, $node)
    {
        for ($n = $node; $n; $n = $n->parentNode) {
            if ($n->nodeType == XML_ELEMENT_NODE) {
                $n->setAttribute('temperature', 'hot');
                if ($n->hasAttribute('pxml')) {
                    break;
                }
            }
        }

        return;
    }

    /**
     *
     * special work for a grp folder :
     *   create the grp if needed
     *   archive files
     *
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  array $nodesToDel    out, filled with deleted files
     * @return void
     */
    private function archiveGrp($dom, $node, $path, $path_archived, $path_error, &$nodesToDel)
    {
        $xpath = new DOMXPath($dom); // useful

        $node->setAttribute('keep', '1');  // grp folders stay in place
        $grpFolder = $node->getAttribute('name');

        $groupingFile = $path . '/' . $grpFolder . '/.grouping.xml';

        if ($node->getAttribute('grp') == 'tocreate') {
            $representationFileName = null;
            $representationFileNode = null;
            $captionFileName = null;
            $captionFileNode = null;
            $cid = $node->getAttribute('cid');
            $genericdoc = null;

            $rootpath = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
            $subpath = substr($path, strlen($rootpath));

            $this->log(sprintf(('created story \'%s\''), $subpath . '/' . $grpFolder));

            // if the .grp does not have a representative doc, let's use a generic file
            if ( ! ($rep = $node->getAttribute('grp_representation'))) {

                $registry = registry::get_instance();
                copy(p4string::addEndSlash($registry->get('GV_RootPath')) . 'www/skins/icons/substitution/regroup_doc.png', $genericdoc = ($path . '/group.jpg'));
                $representationFileName = 'group.jpg';
                $this->log((' (no representation file)'));
            } else {
                $dnl = $xpath->query('./file[@name="' . $rep . '"]', $node->parentNode);
                $representationFileNode = $dnl->item(0);
                $representationFileName = $rep;
                $node->removeAttribute('grp_representation');
                $this->log(sprintf(('representation from \'%s\''), $representationFileName));
            }

            if (($cap = $node->getAttribute('grp_caption')) != '') {
                $dnl = $xpath->query('./file[@name="' . $cap . '"]', $node->parentNode);
                $captionFileNode = $dnl->item(0);
                $captionFileName = $cap;
                $node->removeAttribute('grp_caption');
                $this->log(sprintf(('caption from \'%s\''), $captionFileName));
            }

            try {

                $databox = \databox::get_instance($this->sbas_id);
                $collection = collection::get_from_coll_id($databox, (int) $cid);
                if ($captionFileName === null) {
                    $story = $this->createStory($collection, $path . '/' . $representationFileName, null);
                } else {
                    $story = $this->createStory($collection, $path . '/' . $representationFileName, $path . '/' . $captionFileName);
                }

                $rid = $story->get_record_id();

                $this->log(sprintf('story %s created', $rid));
                $this->archivedFiles ++;

                if ($genericdoc) {
                    unlink($genericdoc);
                }

                file_put_contents($groupingFile, '<?xml version="1.0" encoding="ISO-8859-1" ?><record grouping="' . $rid . '" />');
                $n = $node->appendChild($dom->createElement('file'));
                $n->setAttribute('name', '.grouping.xml');
                $n->setAttribute('temperature', 'cold');
                $n->setAttribute('grp', '1');
                $n->setAttribute('match', '*');
                if ($this->move_archived) {
                    $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $grpFolder . '/.grouping.xml'));
                    @mkdir($path_archived . '/' . $grpFolder, 0755, true);
                    @copy($path . '/' . $grpFolder . '/.grouping.xml', $path_archived . '/' . $grpFolder . '/.grouping.xml');
                }

                if ($captionFileNode) {
                    $captionFileNode->setAttribute('archived', '1');
                    if ($this->move_archived) {
                        $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $captionFileName));

                        if ( ! is_dir($path_archived)) {
                            @mkdir($path_archived, 0755, true);
                        }
                        @copy($path . '/' . $captionFileName, $path_archived . '/' . $captionFileName);
                    }
                    @unlink($path . '/' . $captionFileName);
                    $nodesToDel[] = $captionFileNode;

                    $this->movedFiles ++;
                }
                if ($representationFileNode) {
                    $representationFileNode->setAttribute('archived', '1');
                    if ($this->move_archived) {
                        $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $representationFileName));

                        if ( ! is_dir($path_archived)) {
                            @mkdir($path_archived, 0755, true);
                        }
                        @copy($path . '/' . $representationFileName, $path_archived . '/' . $representationFileName);
                    }
                    @unlink($path . '/' . $representationFileName);
                    $nodesToDel[] = $representationFileNode;

                    $this->movedFiles ++;
                }
                //
                $node->setAttribute('grp', 'tocomplete');
            } catch (Exception $e) {
                $this->logger->addDebug($e->getMessage());
            }
        }

        // here the .grouping.xml should exists
        if (file_exists($groupingFile)) {
            // a .grouping.xml must stay in place
            // -- don't do, done in phase4
            //$xpath = new DOMXPath($dom);
            //$dnl = $xpath->query('./file[@name=".grouping.xml"]', $node);
            //if($dnl->length == 1)
            //  $dnl->item(0)->setAttribute('keep', '1');

            $sxGrouping = simplexml_load_file($groupingFile);
            $grp_rid = $sxGrouping['grouping'];

            $this->archiveFilesToGrp($dom, $node, $path . '/' . $grpFolder
                , $path_archived . '/' . $grpFolder
                , $path_error . '/' . $grpFolder
                , $grp_rid);
        }

        return;
    }

    /**
     * Create a story
     *
     * @todo pathfile should be optionnal
     *
     * @param  \collection     $collection  The destination collection
     * @param  sring           $pathfile    The base file
     * @param  string          $captionFile The optionnal Phrasea XML caption file
     * @return \record_adapter
     */
    public function createStory(\collection $collection, $pathfile, $captionFile = null)
    {
        $stat0 = $stat1 = "0";

        if (isset($this->sxBasePrefs)) {
            if ($this->sxBasePrefs->status) {
                $stat0 = (string) ($this->sxBasePrefs->status);
            }
            if ($this->sxTaskSettings->status) {
                $stat1 = (string) ($this->sxTaskSettings->status);
            }
        }

        if ( ! $stat0) {
            $stat0 = '0';
        }
        if ( ! $stat1) {
            $stat1 = '0';
        }

        $core = \bootstrap::getCore();
        $media = $core['mediavorus']->guess(new \SplFileInfo($pathfile));

        $databox = $collection->get_databox();
        $metadatasStructure = $databox->get_meta_structure();

        $metadatas = $this->getIndexByFieldName($metadatasStructure, $media->getEntity()->getMetadatas());

        if ($captionFile !== null && file_exists($captionFile)) {
            $caption = $this->readXMLForDatabox($metadatasStructure, $captionFile);
            $captionStatus = $this->parseStatusBit(simplexml_load_file($captionFile));

            if ($captionStatus) {
                $status = databox_status::operation_or($status, $captionStatus);
            }

            $metadatas = $this->mergeForDatabox($metadatasStructure, $metadatas, $caption);
        }

        $metas = $this->bagToArray($metadatasStructure, $metadatas);

        $story = record_adapter::createStory($collection);
        $story->substitute_subdef('document', $media);

        $story->set_metadatas($metas, true);
        $story->set_binary_status(databox_status::operation_or($stat0, $stat1));
        $story->rebuild_subdefs();
        $story->reindex();

        $media = $databox = null;

        return $story;
    }

    /**
     * Creates a record
     *
     * @param  \collection  $collection  The destination collection
     * @param  string       $pathfile    The file to archive
     * @param  string|null  $captionFile The Phrasea XML caption file or null if no caption file
     * @param  integer      $grp_rid     Add the record to a story
     * @param  integer      $force       Force lazaret or record ; use \Alchemy\Phrasea\Border\Manager::FORCE_* constants
     * @return null
     */
    public function createRecord(\collection $collection, $pathfile, $captionFile, $grp_rid, $force = null)
    {
        $stat0 = $stat1 = "0";

        if (isset($this->sxBasePrefs)) {
            if ($this->sxBasePrefs->status) {
                $stat0 = (string) ($this->sxBasePrefs->status);
            }
            if ($this->sxTaskSettings->status) {
                $stat1 = (string) ($this->sxTaskSettings->status);
            }
        }

        if ( ! $stat0) {
            $stat0 = '0';
        }
        if ( ! $stat1) {
            $stat1 = '0';
        }

        $core = \bootstrap::getCore();
        $status = databox_status::operation_or($stat0, $stat1);

        $media = $core['mediavorus']->guess(new \SplFileInfo($pathfile));

        $databox = $collection->get_databox();
        $metadatasStructure = $databox->get_meta_structure();

        $metadatas = $this->getIndexByFieldName($metadatasStructure, $media->getEntity()->getMetadatas());

        if ($captionFile !== null && file_exists($captionFile)) {
            $caption = $this->readXMLForDatabox($metadatasStructure, $captionFile);
            $captionStatus = $this->parseStatusBit(simplexml_load_file($captionFile));

            if ($captionStatus) {
                $status = databox_status::operation_or($status, $captionStatus);
            }

            $metadatas = $this->mergeForDatabox($metadatasStructure, $metadatas, $caption);
        }
        $file = new \Alchemy\Phrasea\Border\File($media, $collection);

        $file->addAttribute(new BorderAttribute\Status($status));

        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfFilepath(), new \PHPExiftool\Driver\Value\Mono($media->getFile()->getRealPath()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfDirname(), new \PHPExiftool\Driver\Value\Mono(dirname($media->getFile()->getRealPath())))));

        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfAtime(), new \PHPExiftool\Driver\Value\Mono($media->getFile()->getATime()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfMtime(), new \PHPExiftool\Driver\Value\Mono($media->getFile()->getMTime()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfCtime(), new \PHPExiftool\Driver\Value\Mono($media->getFile()->getCTime()))));

        foreach ($metadatas as $meta) {
            $file->addAttribute(new BorderAttribute\Metadata($meta));
        }

        if ($grp_rid) {
            $file->addAttribute(new BorderAttribute\Story(new record_adapter($databox->get_sbas_id(), $grp_rid)));
        }

        $record = null;

        $postProcess = function($element, $visa, $code) use(&$record) {
                $record = $element;
            };

        $core['border-manager']->process($this->getLazaretSession(), $file, $postProcess, $force);

        return $record;
    }

    /**
     *
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  int $grp_rid
     * @return void
     */
    private function archiveFilesToGrp($dom, $node, $path, $path_archived, $path_error, $grp_rid)
    {
        $nodesToDel = array();
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if ($n->getAttribute('isdir') == '1') {
                // in a grp, all levels goes in the same grp
                $node->setAttribute('archived', '1');  // the main grp folder is 'keep'ed, but not subfolders
                $this->archiveFilesToGrp($dom, $n, $path . '/' . $n->getAttribute('name')
                    , $path_archived . '/' . $n->getAttribute('name')
                    , $path_error . '/' . $n->getAttribute('name')
                    , $grp_rid);
            } else {
                // a file
                $this->archiveFile($dom, $n, $path, $path_archived, $path_error, $nodesToDel, $grp_rid);
            }
        }
        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }

        return;
    }

    /**
     * Archive File
     *
     * @param  DOMDocument $dom
     * @param  DOMElement $node
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  array $nodesToDel    out, filled with files to delete
     * @param  <type> $grp_rid
     * @return void
     */
    private function archiveFile($dom, $node, $path, $path_archived, $path_error, &$nodesToDel, $grp_rid = 0)
    {
        $match = $node->getAttribute('match');

        if ($match == '*') {
            return;
        }

        $file = $node->getAttribute('name');
        $cid = $node->getAttribute('cid');
        $captionFileNode = null;

        $rootpath = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
        $subpath = substr($path, strlen($rootpath));


        if ( ! $match) {
            // the file does not match on any mask
            $this->log(sprintf(("File '%s' does not match any mask"), $subpath . '/' . $file));
            $node->setAttribute('error', '1');

            return;
        } elseif ($match == '?') {
            // the caption file is missing
            $this->log(sprintf(("Caption of file '%s' is missing"), $subpath . '/' . $file));
            $node->setAttribute('error', '1');

            return;
        } elseif ($match != '.') {  // match='.' : the file does not have a separate caption
            $xpath = new DOMXPath($dom);
            $dnl = $xpath->query('./file[@name="' . $match . '"]', $node->parentNode);
            // in fact, xquery has been done in checkMatch, setting match='?' if caption does not exists...
            if ($dnl->length == 1) {
                // ...so we ALWAYS come here
                $captionFileNode = $dnl->item(0);
            } else {
                // ...so we should NEVER come here
                $node->setAttribute('error', '1');

                return;
            }
        }

        $this->archiveFileAndCaption($dom, $node, $captionFileNode, $path, $path_archived, $path_error, $grp_rid, $nodesToDel);
    }

    /**
     *
     * @param  DOMDOcument $dom
     * @param  DOMElement $node
     * @param  DOMElement $captionFileNode
     * @param  string $path
     * @param  string $path_archived
     * @param  string $path_error
     * @param  int $grp_rid
     * @param  array $nodesToDel    out, filled with files to delete
     * @return void
     */
    private function archiveFileAndCaption($dom, $node, $captionFileNode, $path, $path_archived, $path_error, $grp_rid, &$nodesToDel)
    {
        $ret = false;

        $core = \bootstrap::getCore();

        $file = $node->getAttribute('name');
        $cid = $node->getAttribute('cid');
        $captionFileName = $captionFileNode ? $captionFileNode->getAttribute('name') : null;

        $rootpath = p4string::delEndSlash(trim((string) ($this->sxTaskSettings->hotfolder)));
        $subpath = substr($path, strlen($rootpath));

        $this->log(sprintf(("Archiving file '%s'"), $subpath . '/' . $file));
        if ($captionFileName !== null) {
            $this->log(sprintf(' ' . (" (caption in '%s')"), $captionFileName));
        }
        if ($grp_rid !== 0) {
            $this->log(sprintf(' ' . (" into GRP rid=%s"), $grp_rid));
        }

        $stat0 = $stat1 = "0";
        if ($this->sxBasePrefs->status) {
            $stat0 = (string) ($this->sxBasePrefs->status);
        }
        if ($this->sxTaskSettings->status) {
            $stat1 = (string) ($this->sxTaskSettings->status);
        }
        if ( ! $stat0) {
            $stat0 = '0';
        }
        if ( ! $stat1) {
            $stat1 = '0';
        }

        try {
            $databox = \databox::get_instance($this->sbas_id);
            $collection = collection::get_from_coll_id($databox, (int) $cid);

            if ($captionFileName === null) {
                $record = $this->createRecord($collection, $path . '/' . $file, null, $grp_rid);
            } else {
                $record = $this->createRecord($collection, $path . '/' . $file, $path . '/' . $captionFileName, $grp_rid);
            }

            $node->setAttribute('archived', '1');

            if ($captionFileNode) {
                $captionFileNode->setAttribute('archived', '1');
            }

            $this->archivedFiles ++;
        } catch (\Exception $e) {

            $this->log(("Error : can't insert record : " . $e->getMessage()));

            $node->setAttribute('error', '1');

            if ($captionFileNode) {
                $captionFileNode->setAttribute('error', '1');
            }
        }

        if ($node->getAttribute('archived') && $this->move_archived) {
            $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $file));

            @mkdir($path_archived, 0755, true);
            @copy($path . '/' . $file, $path_archived . '/' . $file);
            if ($captionFileName != $file) {
                $this->log(sprintf(('copy \'%s\' to \'archived\''), $subpath . '/' . $captionFileName));
                @copy($path . '/' . $captionFileName, $path_archived . '/' . $captionFileName);
            }
            if ( ! $node->getAttribute('keep')) // do not count copy of special files as a real event
                $ret = true;
        }

        if ($node->getAttribute('error') && $this->move_error) {
            $this->log(sprintf(('copy \'%s\' to \'error\''), $subpath . '/' . $file));

            @mkdir($path_error, 0755, true);
            @copy($path . '/' . $file, $path_error . '/' . $file);
            if ($captionFileName != $file) {
                $this->log(sprintf(('copy \'%s\' to \'error\''), $subpath . '/' . $captionFileName));
                @copy($path . '/' . $captionFileName, $path_error . '/' . $captionFileName);
            }
            // do not count copy of special files as a real event
            if ( ! $node->getAttribute('keep')) {
                $ret = true;
            }
        }

        if ( ! $node->getAttribute('keep')) {
            $file = $node->getAttribute('name');
            @unlink($path . '/' . $file);
            $nodesToDel[] = $node;

            $this->movedFiles ++;
        }

        if ($captionFileNode && ! $captionFileNode->getAttribute('keep')) {
            $file = $captionFileNode->getAttribute('name');
            @unlink($path . '/' . $file);
            $nodesToDel[] = $captionFileNode;

            $this->movedFiles ++;
        }

        return;
    }

    /**
     * xml facility : set attributes to a node and all children
     *
     * @staticvar int $iloop
     * @param DOMDocument $dom
     * @param DOMElement $node
     * @param array $attributes associative name=>value
     * @param int $depth
     */
    private function setAllChildren($dom, $node, $attributes, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        foreach ($attributes as $a => $v) {
            $node->setAttribute($a, $v);
        }

        if (($iloop ++ % 100) == 0) {
            usleep(1000);
        }

        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            $this->setAllChildren($dom, $n, $attributes, $depth + 1);
        }
    }

    /**
     *
     * @param  string $file
     * @return array
     */
    private function getGrpSettings($file)
    {
        $matched = FALSE;
        foreach ($this->tmaskgrp as $maskgrp) {
            //$attachments = null;
            //$attachments["representation"] = null;
            //$attachments["caption"] = null;

            $preg_maskgrp = "/" . $maskgrp["mask"] . "/";
            if (preg_match($preg_maskgrp, $file)) {
                $matched = $maskgrp;
            }
            if ($matched) {
                break;
            }
        }

        return($matched);
    }

    /**
     * Return a LazaretSession
     *
     * @return \Entities\LazaretSession
     */
    protected function getLazaretSession()
    {
        if ($this->lazaretSession) {
            return $this->lazaretSession;
        }

        $core = \bootstrap::getCore();

        $lazaretSession = new \Entities\LazaretSession();

        $core['EM']->persist($lazaretSession);
        $core['EM']->flush();

        $this->lazaretSession = $lazaretSession;

        return $this->lazaretSession;
    }

    /**
     * Map a Bag of metadatas indexed by **Tagnames** to a bag of metadatas
     * indexed by **FieldNames**
     *
     * @param  \databox_descriptionStructure            $metadatasStructure The databox structure related
     * @param  MetadataBag                              $bag                The metadata bag
     * @return \PHPExiftool\Driver\Metadata\MetadataBag
     */
    protected function getIndexByFieldName(\databox_descriptionStructure $metadatasStructure, MetadataBag $bag)
    {
        $ret = new MetadataBag();

        foreach ($metadatasStructure as $databox_field) {
            if ($bag->containsKey($databox_field->get_tag()->getTagname())) {
                $ret->set($databox_field->get_name(), $bag->get($databox_field->get_tag()->getTagname()));
            }
        }

        return $ret;
    }

    /**
     * Map a bag of metadatas indexed by **FieldNames** to an array ready for
     * \record_adapter metadatas submission
     *
     * @param  \databox_descriptionStructure $metadatasStructure The databox structure related
     * @param  MetadataBag                   $metadatas          The metadata bag
     * @return array
     */
    protected function bagToArray(\databox_descriptionStructure $metadatasStructure, MetadataBag $metadatas)
    {
        $metas = array();
        $unicode = new \unicode();

        foreach ($metadatasStructure as $databox_field) {
            if ($metadatas->containsKey($databox_field->get_tag()->getTagname())) {

                if ($databox_field->is_multi()) {

                    $values = $metadatas->get($databox_field->get_tag()->getTagname())->getValue()->asArray();

                    $tmp = array();

                    foreach ($values as $value) {
                        foreach (\caption_field::get_multi_values($value, $databox_field->get_separator()) as $v) {
                            $tmp[] = $v;
                        }
                    }

                    $values = array_unique($tmp);

                    foreach ($values as $value) {

                        $value = $unicode->substituteCtrlCharacters($value, ' ');
                        $value = $unicode->toUTF8($value);
                        if ($databox_field->get_type() == 'date') {
                            $value = $unicode->parseDate($value);
                        }

                        $metas[] = array(
                            'meta_struct_id' => $databox_field->get_id(),
                            'value'          => $value,
                            'meta_id'        => null
                        );
                    }
                } else {
                    $value = $metadatas->get($databox_field->get_tag()->getTagname())->getValue()->asString();

                    $value = $unicode->substituteCtrlCharacters($value, ' ');
                    $value = $unicode->toUTF8($value);
                    if ($databox_field->get_type() == 'date') {
                        $value = $unicode->parseDate($value);
                    }

                    $metas[] = array(
                        'meta_struct_id' => $databox_field->get_id(),
                        'value'          => $metadatas->get($databox_field->get_tag()->getTagname())->getValue()->asString(),
                        'meta_id'        => null
                    );
                }
            }
        }

        $unicode = null;

        return $metas;
    }

    /**
     * Merge two bags of metadatas indexed by **FieldNames**
     * Return a bag indexed by **FieldNames**
     *
     * @param  \databox_descriptionStructure            $metadatasStructure The databox structure related
     * @param  MetadataBag                              $bag1               The first metadata bag
     * @param  MetadataBag                              $bag2               The second metadata bag
     * @return \PHPExiftool\Driver\Metadata\MetadataBag
     */
    protected function mergeForDatabox(\databox_descriptionStructure $metadatasStructure, MetadataBag $bag1, MetadataBag $bag2)
    {
        $metadatasBag = new MetadataBag();

        foreach ($metadatasStructure as $databox_field) {

            $value = array();

            $tag = $databox_field->get_tag();

            foreach (array($bag1, $bag2) as $bag) {

                if ( ! $bag->containsKey($databox_field->get_name())) {
                    continue;
                }

                if ($databox_field->is_multi()) {
                    $value = array_unique(array_merge($value, $bag->get($databox_field->get_name())->getValue()->asArray()));
                } else {
                    $value = $bag->get($databox_field->get_name())->getValue()->asString();
                }
            }

            if ( ! $value) {
                continue;
            }

            if ($databox_field->is_multi()) {
                $value = new \PHPExiftool\Driver\Value\Multi($value);
            } else {
                $value = new \PHPExiftool\Driver\Value\Mono($value);
            }

            $metadatasBag->set($databox_field->get_name(), new PHPExiftool\Driver\Metadata\Metadata($tag, $value));
        }

        return $metadatasBag;
    }

    /**
     * Read a Phrasea XML file for Phrasea metadatas
     * Returns a MetadataBag indexed by **FieldNames**
     *
     * @param  \databox_descriptionStructure            $metadatasStructure The databox structure related
     * @param  type                                     $pathfile           The path file to the XML
     * @return \PHPExiftool\Driver\Metadata\MetadataBag
     *
     * @throws \InvalidArgumentException When the file is invalid or missing
     */
    protected function readXMLForDatabox(\databox_descriptionStructure $metadatasStructure, $pathfile)
    {
        if ( ! file_exists($pathfile)) {
            throw new \InvalidArgumentException(sprintf('file %s does not exists', $pathfile));
        }

        $sxcaption = @simplexml_load_file($pathfile);

        if ( ! $sxcaption) {
            throw new \InvalidArgumentException(sprintf('Invalid XML file %s', $pathfile));
        }

        /**
         * @todo update with metafield, ensure that metafield primes on metadata
         */
        $metadataBag = new MetadataBag();

        foreach ($sxcaption->description->children() as $tagname => $field) {
            $field = trim($field);

            $meta = $metadatasStructure->get_element_by_name(trim($tagname));
            if ( ! $meta) {
                continue;
            }

            $tag = $meta->get_tag();

            if ($meta->is_multi()) {
                $fields = caption_field::get_multi_values($field, $meta->get_separator());

                if ( ! $metadataBag->containsKey($meta->get_name())) {
                    $values = new \PHPExiftool\Driver\Value\Multi($fields);
                } else {
                    $values = $metadataBag->get($meta->get_name())->getValue();

                    foreach ($fields as $f) {
                        $values->addValue($f);
                    }
                }

                /**
                 * fail if not tagname defined
                 */
                $metadataBag->set($meta->get_name(), new \PHPExiftool\Driver\Metadata\Metadata($tag, $values));
            } else {
                $metadataBag->set($meta->get_name(), new \PHPExiftool\Driver\Metadata\Metadata($tag, new \PHPExiftool\Driver\Value\Mono($field)));
            }
        }

        return $metadataBag;
    }

    /**
     * Parse a Phrasea XML to find status tag
     *
     * @param  \SimpleXMLElement $sxcaption The SimpleXML related to the XML
     * @return string
     */
    protected function parseStatusBit(\SimpleXMLElement $sxcaption)
    {
        $statBit = null;

        if ('' !== $inStatus = (string) $sxcaption->status) {
            $statBit = $inStatus;
        }

        return $statBit;
    }
}

class CListFolder
{
    /**
     *
     * @var Array
     */
    protected $list;

    /**
     *
     * @param string  $path
     * @param boolean $sorted
     */
    public function __construct($path, $sorted = true)
    {
        $this->list = array();
        if ($hdir = opendir($path)) {
            while (false !== ($file = readdir($hdir))) {
                $this->list[] = $file;
            }
            closedir($hdir);
            if ($sorted) {
                natcasesort($this->list);
            }
        }
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        unset($this->list);
    }

    /**
     *
     * @return string
     */
    public function read()
    {
        return(array_shift($this->list));
    }
}
