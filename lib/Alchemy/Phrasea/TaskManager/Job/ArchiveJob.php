<?php

/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\TaskManager\Job;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Border\File;
use Alchemy\Phrasea\Border\Manager as borderManager;
use Alchemy\Phrasea\TaskManager\Editor\ArchiveEditor;
use Alchemy\Phrasea\Metadata\Tag as PhraseaTag;
use Alchemy\Phrasea\Border\Attribute as BorderAttribute;
use Alchemy\Phrasea\Border\MetadataBag;
use Alchemy\Phrasea\Border\MetaFieldsBag;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Alchemy\Phrasea\WorkerManager\Event\RecordsWriteMetaEvent;
use Alchemy\Phrasea\WorkerManager\Event\WorkerEvents;
use PHPExiftool\Driver\Metadata\MetadataBag as ExiftoolMetadataBag;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
use Symfony\Component\Filesystem\Exception\IOException;


class ArchiveJob extends AbstractJob
{
    const MINCOLD = 5;
    const MAXCOLD = 300;

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->translator->trans('task::archive:Archivage');
    }

    /**
     * {@inheritdoc}
     */
    public function getJobId()
    {
        return 'Archive';
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return $this->translator->trans("task::archive:Archiving files found into a 'hotfolder'");
    }

    /**
     * {@inheritdoc}
     */
    public function getEditor()
    {
        return new ArchiveEditor($this->translator);
    }

    /**
     * {@inheritdoc}
     */
    protected function doJob(JobData $data)
    {
        $app = $data->getApplication();

        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();

        $task = $data->getTask();

        $settings = simplexml_load_string($task->getSettings());

        $baseId = (string) $settings->base_id;
        $sbasId = \phrasea::sbasFromBas($app, $baseId);

        if (!$sbasId) {
            throw new RuntimeException('Invalid collection base_id.');
        }

        $databox = $app->findDataboxById($sbasId);

        // quick fix to reconnect if mysql is lost
        $databox->get_connection();

        $TColls = [];
        $collection = null;
        foreach ($databox->get_collections() as $coll) {
            $TColls['c' . $coll->get_coll_id()] = $coll->get_coll_id();
            if ($baseId == $coll->get_base_id()) {
                $collection = $coll;
            }
        }
        $server_coll_id = $collection->get_coll_id();

        // mask(s) of accepted files
        $tmask = [];
        $tmaskgrp = [];
        $cold = min(max((int) $settings->cold, self::MINCOLD), self::MAXCOLD);

        $stat0 = $stat1 = "0";
        if (false !== $sxBasePrefs = simplexml_load_string($collection->get_prefs())) {
            if ($sxBasePrefs->status) {
                $stat0 = (string) $sxBasePrefs->status;
            }
        }
        if ($settings->status) {
            $stat1 = (string) $settings->status;
        }

        if (!$stat0) {
            $stat0 = '0';
        }
        if (!$stat1) {
            $stat1 = '0';
        }

        $filesystem = $app['filesystem'];

        // load masks
        if ($settings->files && $settings->files->file) {
            foreach ($settings->files->file as $ft) {
                $tmask[] = [
                    "mask" => (string)$ft["mask"],
                    "caption" => (string)$ft["caption"],
                    "accept" => (string)$ft["accept"],
                ];
            }
        }
        if ($settings->files && $settings->files->grouping) {
            foreach ($settings->files->grouping as $ft) {
                $tmaskgrp[] = [
                    "mask" => (string)$ft["mask"],
                    "caption" => (string)$ft["caption"],
                    "representation" => (string)$ft["representation"],
                    "accept" => (string)$ft["accept"],
                ];
            }
        }
        if (count($tmask) == 0) {
            // no mask defined : accept all kind of files
            $tmask[] = ["mask"    => ".*", "caption" => "", "accept"  => ""];
        }

        while ($this->isStarted()) {
            $path_in = rtrim((string) $settings->hotfolder, '\\/');
            $path_in = $filesystem->exists($path_in) ? realpath($path_in) : $path_in;

            if (!@is_dir($path_in)) {
                throw new RuntimeException(sprintf('Error : missing hotfolder \'%s\', stopping.', $path_in));
            }

            // copy settings to task, so it's easier to get later
            $moveArchived = \p4field::isyes($settings->move_archived);
            $moveError = \p4field::isyes($settings->move_error);

            clearstatcache();

            if (false === $filesystem->exists($path_in . "/.phrasea.xml")) {
                throw new RuntimeException(sprintf('NO .phrasea.xml AT ROOT \'%s\' !', $path_in));
            }

            $path_archived = $path_error = null;
            if ($moveArchived) {
                $path_archived = $path_in . '_archived';
                $filesystem->mkdir($path_archived, 0755);
            }
            if ($moveError) {
                $path_error = $path_in . '_error';
                $filesystem->mkdir($path_error, 0755);
            }

            $dom = new \DOMDocument();
            $dom->formatOutput = true;
            /** @var \DOMElement $root */
            $root = $dom->appendChild($dom->createElement('root'));

            $nnew = $this->listFilesPhase1($app, $dom, $root, $path_in, $server_coll_id, 0, $TColls);
            if ($app['debug']) {
                $this->log('debug', "== listFilesPhase1 returned " . $nnew . ")\n" . $dom->saveXML());
            }

            if (!$this->isStarted()) {
                return;
            }

            // wait for files to be cold
            for($i=0; $i<($cold*2); $i++) {
                if (!$this->isStarted()) {
                    return;
                }
                $this->pause(0.5);
            }

            $this->listFilesPhase2($app, $dom, $root, $path_in, 0);
            if ($app['debug']) {
                $this->log('debug', "== listFilesPhase2\n" . $dom->saveXML());
            }

            if (!$this->isStarted()) {
                return;
            }

            $this->makePairs($dom, $root, $path_in, $path_archived, $path_error, false, 0, $tmask, $tmaskgrp);
            if ($app['debug']) {
                $this->log('debug', "== makePairs\n" . $dom->saveXML());
            }

            if (!$this->isStarted()) {
                return;
            }

            $this->removeBadGroups($app, $dom, $root, $path_in, $path_archived, $path_error, 0, $moveError);
            if ($app['debug']) {
                $this->log('debug', "== removeBadGroups\n" . $dom->saveXML());
            }

            if (!$this->isStarted()) {
                return;
            }

            $this->archive($app, $databox, $dom, $root, $path_in, $path_archived, $path_error, 0, $moveError, $moveArchived, $stat0, $stat1);
            if ($app['debug']) {
                $this->log('debug', "== archive\n" . $dom->saveXML());
            }

            $this->bubbleResults($dom, $root, $path_in, 0, \p4field::isyes($settings->copy_spe));
            if ($app['debug']) {
                $this->log('debug', "== bubbleResults\n" . $dom->saveXML());
            }

            $moved = $this->moveFiles($app, $dom, $root, $path_in, $path_archived, $path_error, 0, $moveArchived, $moveError);
            if ($app['debug']) {
                $this->log('debug', "== moveFiles returned " . ($moved ? 'true' : 'false') . "\n" . $dom->saveXML());
            }
        }
    }

    private function listFilesPhase1(Application $app, \DOMDocument $dom, \DomElement $node, $path, $server_coll_id, $depth, &$TColls)
    {
        $nnew = 0;

        $magicfile = $magicmethod = null;

        if (($sxDotPhrasea = @simplexml_load_file($path . '/.phrasea.xml')) !== false) {

            // test for magic file
            if (($magicfile = trim((string) ($sxDotPhrasea->magicfile))) != '') {
                $magicmethod = strtoupper($sxDotPhrasea->magicfile['method']);
                if ($magicmethod == 'LOCK' && ($app['filesystem']->exists($path . '/' . $magicfile) === true)) {
                    return 0;
                } elseif ($magicmethod == 'UNLOCK' && ($app['filesystem']->exists($path . '/' . $magicfile) === false)) {
                    return 0;
                }
            }

            // change collection ?
            if (($new_cid = $sxDotPhrasea['collection']) != '') {
                if (isset($TColls['c' . $new_cid])) {
                    $server_coll_id = $new_cid;
                } else {
                    $this->log('debug', sprintf('Unknown coll_id (%1$d) in "%2$s"', (int) $new_cid, $path . '/.phrasea.xml'));
                    $server_coll_id = -1;
                }
            }
            $node->setAttribute('pxml', '1');
        }

        foreach ($this->listFolder($path) as $file) {
            if (!$this->isStarted()) {
                break;
            }

            usleep(10);

            if ($this->isIgnoredFile($file)) {
                continue;
            }

            /** @var \DOMElement $n */
            if (is_dir($path . '/' . $file)) {
                $n = $node->appendChild($dom->createElement('file'));
                $n->setAttribute('isdir', '1');
                $n->setAttribute('name', $file);
                $nnew += $this->listFilesPhase1($app, $dom, $n, $path . '/' . $file, $server_coll_id, $depth + 1, $TColls);
                if (!$this->isStarted()) {
                    break;
                }
            } else {
                $n = $node->appendChild($dom->createElement('file'));
                $n->setAttribute('name', $file);
                $stat = stat($path . '/' . $file);
                foreach (["size", "ctime", "mtime"] as $k) {
                    $n->setAttribute($k, $stat[$k]);
                }
                // special file
                if($file == '.phrasea.xml') {
                    $n->setAttribute('match', '*');
                }
                // special file
                if($file === $magicfile) {
                    $n->setAttribute('match', '*');
                    $node->setAttribute('magicfile', $magicfile);
                    $node->setAttribute('magicmethod', $magicmethod);
                }
                $nnew++;
            }
            $n->setAttribute('cid', $server_coll_id);
            $n->setAttribute('temperature', 'hot');
        }

        return $nnew;
    }

    private function listFilesPhase2(Application $app, \DOMDocument $dom, \DOMElement $node, $path, $depth)
    {
        $nnew = 0;

        $xp = new \DOMXPath($dom);

        if (false !== $sxDotPhrasea = @simplexml_load_file($path . '/.phrasea.xml')) {
            // test magicfile
            if ('' !== $magicfile = trim((string) ($sxDotPhrasea->magicfile))) {
                $magicmethod = strtoupper($sxDotPhrasea->magicfile['method']);
                if ($magicmethod == 'LOCK' && true === $app['filesystem']->exists($path . '/' . $magicfile)) {
                    return 0;
                }
                if ($magicmethod == 'UNLOCK' && false === $app['filesystem']->exists($path . '/' . $magicfile)) {
                    return 0;
                }
            }
        }

        foreach ($this->listFolder($path) as $file) {
            if (!$this->isStarted()) {
                break;
            }

            if ($this->isIgnoredFile($file)) {
                continue;
            }

            usleep(10);

            $dnl = @$xp->query('./file[@name="' . $file . '"]', $node);
            if ($dnl && $dnl->length == 0) {
                if (is_dir($path . '/' . $file)) {
                    /** @var \DOMElement $n */
                    $n = $node->appendChild($dom->createElement('file'));
                    $n->setAttribute('isdir', '1');
                    $n->setAttribute('name', $file);

                    $nnew += $this->listFilesPhase2($app, $dom, $n, $path . '/' . $file, $depth + 1);
                } else {
                    /** @var \DOMElement $n */
                    $n = $node->appendChild($dom->createElement('file'));
                    $n->setAttribute('name', $file);
                    $nnew++;
                }
                $this->setBranchHot($n);
            } elseif ($dnl && $dnl->length == 1) {
                /** @var \DOMElement $n */
                $n = $dnl->item(0);
                $n->setAttribute('temperature', 'cold');

                if (is_dir($path . '/' . $file)) {
                    $this->listFilesPhase2($app, $dom, $n, $path . '/' . $file, $depth + 1);
                } else {
                    $stat = stat($path . '/' . $file);
                    foreach (["size", "ctime", "mtime"] as $k) {
                        if ($n->getAttribute($k) != $stat[$k]) {
                            $this->setBranchHot($n);
                            break;
                        }
                    }
                }
            }
        }

        return $nnew;
    }

    private function makePairs(\DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, $inGrp, $depth, &$tmask, $tmaskgrp)
    {
        if ($depth == 0 && ($node->getAttribute('temperature') == 'hot' || $node->getAttribute('cid') == '-1')) {
            return;
        }

        $xpath = new \DOMXPath($dom);

        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            usleep(10);

            if (!$this->isStarted()) {
                break;
            }

            // make xml lighter (free ram)
            foreach (["size", "ctime", "mtime"] as $k) {
                $n->removeAttribute($k);
            }

            if ($n->getAttribute('temperature') == 'hot' || $n->getAttribute('cid') == '-1') {
                continue;
            }

            $name = $n->getAttribute('name');
            if ($n->getAttribute('isdir') == '1') {
                // get 'caption', 'representation'
                if (($grpSettings = $this->getGrpSettings($name, $tmaskgrp)) !== false) {
                    // this is a grp folder, we check it
                    $dnl = $xpath->query('./file[@name=".grouping.xml"]', $n);
                    if ($dnl->length == 1) {
                        // this group is old (don't care about any linked files), just flag it
                        $n->setAttribute('grp', 'tocomplete');
                        /** @var \DOMElement $_n */
                        $_n = $dnl->item(0);
                        $_n->setAttribute('match', '*');
                        // recurse only if group is ok
                        $this->makePairs($dom, $n, $path . '/' . $name, $path_archived, $path_error, true, $depth + 1, $tmask, $tmaskgrp);
                    } else {
                        // this group in new (to be created)
                        // do we need one (or both) linked file ? (caption or representation)
                        $err = false;
                        /** @var \DOMElement[] $flink */
                        $flink = ['caption'        => null, 'representation' => null];

                        foreach ($flink as $linkName => $v) {
                            if (isset($grpSettings[$linkName]) && $grpSettings[$linkName] != '') {
                                // we need this linked file, calc his real name
                                $f = preg_replace('/' . $grpSettings['mask'] . '/i', $grpSettings[$linkName], $name);

                                $dnl = $xpath->query('./file[@name="' . $f . '"]', $node);
                                if ($dnl->length == 1) {
                                    // it's here
                                    $flink[$linkName] = $dnl->item(0);
                                } else {
                                    $this->log('debug', sprintf('missing linked file \'%1$s\' to group \'%2$s\'', $f, $name));
                                    // missing -> error
                                    $err = true;
                                }
                            }
                        }

                        if (!$err) {
                            // the group is ok, flag it ...
                            $n->setAttribute('grp', 'tocreate');

                            // ... as the existing linked file(s) ...
                            foreach ($flink as $linkName => $v) {
                                // this linked file exists
                                if ($v) {
                                    $v->setAttribute('match', '*');
                                    $n->setAttribute('grp_' . $linkName, $v->getAttribute('name'));
                                }
                            }
                            // recurse only if group is ok
                            $this->makePairs($dom, $n, $path . '/' . $name
                                , $path_archived . '/' . $name
                                , $path_error . '/' . $name
                                , true, $depth + 1, $tmask, $tmaskgrp);
                        } else {
                            // something is missing, the whole group goes error, ...
                            $n->setAttribute('grp', 'todelete');

                            $this->setAllChildren($dom, $n, ['error' => '1']);

                            // bubble to the top
                            for ($nn = $n; $nn && $nn->nodeType == XML_ELEMENT_NODE; $nn = $nn->parentNode) {
                                $nn->setAttribute('error', '1');
                            }

                            // ... as the existing linked file(s) ...
                            foreach ($flink as $linkName => $v) {
                                // this linked file exists, it goes error also
                                if ($v) {
                                    $v->setAttribute('error', '1');
                                }
                            }
                        }
                    }
                } else {
                    // not a grp folder, recurse
                    $this->makePairs($dom, $n, $path . '/' . $name
                        , $path_archived . '/' . $name
                        , $path_error . '/' . $name
                        , $inGrp, $depth + 1, $tmask, $tmaskgrp);
                }
            } else {
                // this is a file
                if (!$n->getAttribute('match')) {
                    // because match can be set before
//                    if ($name == '.phrasea.xml') {
//                        // special file(s) always ok
//                        $n->setAttribute('match', '*');
//                    } else {
                        $this->checkMatch($dom, $n, $tmask);
//                    }
                }
            }
        }

        // scan again for unmatched files
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (!$this->isStarted()) {
                break;
            }
            if (!$n->getAttribute('isdir') == '1' && !$n->getAttribute('match')) {
                // still no match, now it's an error (bubble to the top)
                for ($nn = $n; $nn && $nn->nodeType == XML_ELEMENT_NODE; $nn = $nn->parentNode) {
                    $nn->setAttribute('error', '1');
                }
            }
        }
    }

    private function removeBadGroups(Application $app, \DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, $depth, $moveError)
    {
        $ret = false;

        // if root of hotfolder if hot, die...
        if ($depth == 0 && $node->getAttribute('temperature') == 'hot') {
            return;
        }

        $nodesToDel = [];
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            usleep(10);

            if (!$this->isStarted()) {
                break;
            }

            if ($n->getAttribute('temperature') == 'hot') {
                // do not move hotfiles
                continue;
            }

            $name = $n->getAttribute('name');

            if ($n->getAttribute('isdir')) {
                 $this->removeBadGroups($app, $dom, $n, $path . '/' . $name
                    , $path_archived . '/' . $name
                    , $path_error . '/' . $name
                    , $depth + 1, $moveError);
                if ($n->getAttribute('grp') == 'todelete') {
                    $nodesToDel[] = $n;

                    try {
                        $app['filesystem']->remove($path . '/' . $name);
                    } catch (IOException $e) {
                        $this->log('error', $e->getMessage());
                    }
                }
            } else {
                if ($n->getAttribute('error')) {
                    if ($moveError) {
                        $this->log('debug', sprintf('copy \'%s\' to \'error\'', $path . '/' . $name));

                        try {
                            $app['filesystem']->mkdir($path_error, 0755);
                        } catch (IOException $e) {
                            $this->log('error', $e->getMessage());
                        }

                        try {
                            $app['filesystem']->copy($path . '/' . $name, $path_error . '/' . $name, true);
                        } catch (IOException $e) {
                            $this->log('error', $e->getMessage());
                        }
                    }

                    $nodesToDel[] = $n;

                    try {
                        $app['filesystem']->remove($path . '/' . $name);
                    } catch (IOException $e) {
                        $this->log('error', $e->getMessage());
                    }
                }
            }
        }

        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }
    }

    private function archive(Application $app, \databox $databox, \DOMDOcument $dom, \DOMElement $node, $path, $path_archived, $path_error, $depth, $moveError, $moveArchived, $stat0, $stat1)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $databox->get_connection();

        if ($node->getAttribute('temperature') == 'hot') {
            return;
        }

        $nodesToDel = [];
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            usleep(10);

            if (!$this->isStarted()) {
                break;
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
                    $this->archiveGrp($app, $databox, $dom, $n, $path, $path_archived, $path_error, $nodesToDel, $moveError, $moveArchived, $stat0, $stat1);
                } else {
                    // ...normal subfolder : recurse
                    $name = $n->getAttribute('name');
                    $this->archive($app, $databox, $dom, $n, $path . '/' . $name
                        , $path_archived . '/' . $name
                        , $path_error . '/' . $name
                        , $depth + 1, $moveError, $moveArchived, $stat0, $stat1);
                }
            } else {
                // a file,  0 = no grp
                $this->archiveFile($app, $databox, $dom, $n, $path, $path_archived, $path_error, $nodesToDel, 0, $stat0, $stat1, $moveError, $moveArchived);
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
                try {
                    $app['filesystem']->remove($path . '/' . $magicfile);
                } catch (IOException $e) {
                    $this->log('debug', $e->getMessage());
                }
            }
        }

        return;
    }

    private function bubbleResults(\DOMDocument $dom, \DOMElement $node, $path, $depth, $copySpe)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        if ($node->getAttribute('temperature') == 'hot') {
            return 0;
        }

        $ret = 0;
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (($iloop++ % 20) == 0) {
                usleep(1000);
            }

            if ($n->getAttribute('name') == '.phrasea.xml' || $n->getAttribute('name') == '.grouping.xml') {
                // special files stay in place AND are copied into 'archived'
                $n->setAttribute('keep', '1');
                if ($copySpe) {
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
                $ret |= $this->bubbleResults($dom, $n, $path . '/' . $n->getAttribute('name'), $depth + 1, $copySpe);
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

        return $ret;
    }

    private function moveFiles(Application $app, \DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, $depth, $moveArchived, $moveError)
    {
        $ret = false;

        // if root of hotfolder if hot, die...
        if ($depth == 0 && $node->getAttribute('temperature') == 'hot') {
            return $ret;
        }

        $nodesToDel = [];
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            usleep(10);

            if ($n->getAttribute('temperature') == 'hot') { // do not move hotfiles
                continue;
            }

            $name = $n->getAttribute('name');

            if ($n->getAttribute('isdir')) {
                $ret |= $this->moveFiles($app, $dom, $n, $path . '/' . $name
                    , $path_archived . '/' . $name
                    , $path_error . '/' . $name
                    , $depth + 1, $moveArchived, $moveError);

                if (!$n->firstChild) {
                    $nodesToDel[] = $n;
                }
                /**
                 * Do not remove empty folders yet
                 */
            } else {
                if ($n->getAttribute('archived') && $moveArchived) {
                    $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $name));

                    try {
                        $app['filesystem']->mkdir($path_archived);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    try {
                        $app['filesystem']->copy($path . '/' . $name, $path_archived . '/' . $name, true);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    if (!$n->getAttribute('keep')) { // do not count copy of special files as a real event
                        $nodesToDel[] = $n;
                        $ret = true;
                    }
                }

                if ($n->getAttribute('error') && $moveError) {
                    $this->log('debug', sprintf('copy \'%s\' to \'error\'', $path . '/' . $name));

                    try {
                        $app['filesystem']->mkdir($path_error);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    try {
                        $app['filesystem']->copy($path . '/' . $name, $path_error . '/' . $name, true);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    if (!$n->getAttribute('keep')) { // do not count copy of special files as a real event
                        $nodesToDel[] = $n;
                        $ret = true;
                    }
                }

                if (!$n->getAttribute('keep') && !$n->getAttribute('match')) {
                    $this->log('debug', sprintf('delete \'%s\'', $path . '/' . $name));

                    try {
                        $app['filesystem']->remove($path . '/' . $name);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }
                }
            }
        }

        foreach ($nodesToDel as $n) {
            $n->parentNode->removeChild($n);
        }

        return $ret;
    }

    protected function checkMatch(\DOMDocument $dom, \DOMElement $node, &$tmask)
    {
        $file = $node->getAttribute('name');

        foreach ($tmask as $mask) {
            $preg_mask = '/' . $mask['mask'] . '/';
            if (preg_match($preg_mask, $file)) {
                if ($mask['caption']) {
                    // caption in a linked file ?
                    $captionFileName = @preg_replace($preg_mask, $mask['caption'], $file);
                    $xpath = new \DOMXPath($dom);
                    $dnl = $xpath->query('./file[@name="' . $captionFileName . '"]', $node->parentNode);
                    if ($dnl->length == 1) {
                        // the caption file exists
                        $node->setAttribute('match', $captionFileName);
                        /** @var \DOMElement $n */
                        $n = $dnl->item(0);
                        $n->setAttribute('match', '*');
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

    protected function isIgnoredFile($f)
    {
        $f = strtolower($f);

        return ($f[0] == '.' && $f != '.phrasea.xml' && $f != '.grouping.xml') || $f == 'thumbs.db' || $f == 'par-system';
    }

    private function setBranchHot(\DOMElement $node)
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

    private function archiveGrp(Application $app, \databox $databox, \DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, array &$nodesToDel, $moveError, $moveArchived, $stat0, $stat1)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $databox->get_connection();

        $xpath = new \DOMXPath($dom);

        // grp folders stay in place
        $node->setAttribute('keep', '1');
        $grpFolder = $node->getAttribute('name');

        $groupingFile = $path . '/' . $grpFolder . '/.grouping.xml';

        if ($node->getAttribute('grp') == 'tocreate') {
            $representationFileName = null;
            /** @var \DOMElement $representationFileNode */
            $representationFileNode = null;
            $captionFileName = null;
            /** @var \DOMElement $captionFileNode */
            $captionFileNode = null;
            $cid = $node->getAttribute('cid');
            $genericdoc = null;

            $this->log('debug', sprintf('created story \'%s\'', $path . '/' . $grpFolder));

            // if the .grp does not have a representative doc, let's use a generic file
            if (!($rep = $node->getAttribute('grp_representation'))) {

                try {
                    $app['filesystem']->copy($app['root.path'] . '/www/assets/common/images/icons/substitution/regroup_doc.png', $genericdoc = ($path . '/group.jpg'), true);
                } catch (IOException $e) {
                    $this->log('debug', $e->getMessage());
                }

                $representationFileName = 'group.jpg';
                $this->log('debug', ' (no representation file)');
            } else {
                $dnl = $xpath->query('./file[@name="' . $rep . '"]', $node->parentNode);
                $representationFileNode = $dnl->item(0);
                $representationFileName = $rep;
                $node->removeAttribute('grp_representation');
                $this->log('debug', sprintf('representation from \'%s\'', $representationFileName));
            }

            if (($cap = $node->getAttribute('grp_caption')) != '') {
                $dnl = $xpath->query('./file[@name="' . $cap . '"]', $node->parentNode);
                $captionFileNode = $dnl->item(0);
                $captionFileName = $cap;
                $node->removeAttribute('grp_caption');
                $this->log('debug', sprintf('caption from \'%s\'', $captionFileName));
            }

            try {
                $collection = \collection::getByCollectionId($app, $databox, (int) $cid);
                if ($captionFileName === null) {
                    $story = $this->createStory($app, $collection, $path . '/' . $representationFileName, null, $stat0, $stat1);
                } else {
                    $story = $this->createStory($app, $collection, $path . '/' . $representationFileName, $path . '/' . $captionFileName, $stat0, $stat1);
                }

                $rid = $story->getRecordId();

                $this->log('info', sprintf('story %s created', $rid));

                if ($genericdoc) {
                    try {
                        $app['filesystem']->remove($genericdoc);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }
                }

                file_put_contents($groupingFile, '<?xml version="1.0" encoding="ISO-8859-1" ?><record grouping="' . $rid . '" />');
                /** @var \DOMElement $n */
                $n = $node->appendChild($dom->createElement('file'));
                $n->setAttribute('name', '.grouping.xml');
                $n->setAttribute('temperature', 'cold');
                $n->setAttribute('grp', '1');
                $n->setAttribute('match', '*');
                if ($moveArchived) {
                    $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $grpFolder . '/.grouping.xml'));

                    try {
                        $app['filesystem']->mkdir($path_archived . '/' . $grpFolder, 0755);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    try {
                        $app['filesystem']->copy($path . '/' . $grpFolder . '/.grouping.xml', $path_archived . '/' . $grpFolder . '/.grouping.xml', true);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }
                }

                if ($captionFileNode) {
                    $captionFileNode->setAttribute('archived', '1');
                    if ($moveArchived) {
                        $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $captionFileName));

                        try {
                            $app['filesystem']->mkdir($path_archived, 0755);
                        } catch (IOException $e) {
                            $this->log('debug', $e->getMessage());
                        }

                        try {
                            $app['filesystem']->copy($path . '/' . $captionFileName, $path_archived . '/' . $captionFileName, true);
                        } catch (IOException $e) {
                            $this->log('debug', $e->getMessage());
                        }
                    }

                    try {
                        $app['filesystem']->remove($path . '/' . $captionFileName);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }

                    $nodesToDel[] = $captionFileNode;
                }
                if ($representationFileNode) {
                    $representationFileNode->setAttribute('archived', '1');
                    if ($moveArchived) {
                        $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $representationFileName));

                        try {
                            $app['filesystem']->mkdir($path_archived, 0755);
                        } catch (IOException $e) {
                            $this->log('debug', $e->getMessage());
                        }

                        try {
                            $app['filesystem']->copy($path . '/' . $representationFileName, $path_archived . '/' . $representationFileName, true);
                        } catch (IOException $e) {
                            $this->log('debug', $e->getMessage());
                        }
                    }

                    try {
                        $app['filesystem']->remove($path . '/' . $representationFileName);
                    } catch (IOException $e) {
                        $this->log('debug', $e->getMessage());
                    }
                    $nodesToDel[] = $representationFileNode;

                }
                $node->setAttribute('grp', 'tocomplete');
            } catch (\Exception $e) {
                $this->log('debug', $e->getMessage());
            }
        }

        // here the .grouping.xml should exists
        if ($app['filesystem']->exists($groupingFile)) {
            // a .grouping.xml must stay in place
            // -- don't do, done in phase4

            $sxGrouping = @simplexml_load_file($groupingFile);
            $grp_rid = $sxGrouping['grouping'];

            $this->archiveFilesToGrp($app, $databox, $dom, $node, $path . '/' . $grpFolder
                , $path_archived . '/' . $grpFolder
                , $path_error . '/' . $grpFolder
                , $grp_rid, $stat0, $stat1, $moveError, $moveArchived);
        }

        return;
    }

    public function createStory(Application $app, \collection $collection, $pathfile, $captionFile, $stat0, $stat1)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $collection->get_connection();

        $status = \databox_status::operation_or($stat0, $stat1);

        $media = $app->getMediaFromUri($pathfile);

        $databox = $collection->get_databox();
        $metadatasStructure = $databox->get_meta_structure();

        $metadatas = $this->getIndexByFieldName($metadatasStructure, $media->getMetadatas());
        $metaFields = null;

        if ($captionFile !== null && true === $app['filesystem']->exists($captionFile)) {
            $metaFields = $this->readXMLForDatabox($app, $metadatasStructure, $captionFile);
            $captionStatus = $this->parseStatusBit(@simplexml_load_file($captionFile));

            if ($captionStatus) {
                $status = \databox_status::operation_mask($status, $captionStatus);
            }
        }

        $story = \record_adapter::createStory($app, $collection);

        $story->setStatus($status);
        $app['subdef.substituer']->substituteDocument($story, $media);

        $story->set_metadatas($metadatas->toMetadataArray($metadatasStructure), true);

        if ($metaFields) {
            $story->set_metadatas($metaFields->toMetadataArray($metadatasStructure), true);
        }

        // order to write meta in file
        $this->dispatcher->dispatch(WorkerEvents::RECORDS_WRITE_META,
            new RecordsWriteMetaEvent([$story->getRecordId()], $story->getDataboxId()));

        $story->setStatus(\databox_status::operation_or($stat0, $stat1));

        $app['dispatcher']->dispatch(RecordEvents::SUBDEFINITION_CREATE, new SubdefinitionCreateEvent($story));

        unset($media);

        return $story;
    }

    /**
     * Creates a record
     *
     * @param  \collection     $collection  The destination collection
     * @param  string          $pathfile    The file to archive
     * @param  string|null     $captionFile The Phrasea XML caption file or null if no caption file
     * @param  integer         $grp_rid     Add the record to a story
     * @param  integer         $force       Force lazaret or record ; use \Alchemy\Phrasea\Border\Manager::FORCE_* constants
     * @return \record_adapter
     */
    public function createRecord(Application $app, \collection $collection, $pathfile, $captionFile, $grp_rid, $force, $stat0, $stat1)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $collection->get_connection();

        $status = \databox_status::operation_or($stat0, $stat1);

        $media = $app->getMediaFromUri($pathfile);

        $databox = $collection->get_databox();
        $metadatasStructure = $databox->get_meta_structure();

        $metadatas = $this->getIndexByFieldName($metadatasStructure, $media->getMetadatas());

        $metaFields = [];
        if ($captionFile !== null && true === $app['filesystem']->exists($captionFile)) {
            $metaFields = $this->readXMLForDatabox($app, $metadatasStructure, $captionFile);
            $captionStatus = $this->parseStatusBit(@simplexml_load_file($captionFile));

            if ($captionStatus) {
                $status = \databox_status::operation_mask($status, $captionStatus);
            }
        }
        $file = new File($app, $media, $collection);

        $file->addAttribute(new BorderAttribute\Status($app, $status));

        /** @var \MediaVorus\File $mediaFile */
        $mediaFile = $media->getFile();
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfFilepath(), new MonoValue($mediaFile->getRealPath()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfDirname(), new MonoValue(dirname($mediaFile->getRealPath())))));

        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfAtime(), new MonoValue($mediaFile->getATime()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfMtime(), new MonoValue($mediaFile->getMTime()))));
        $file->addAttribute(new BorderAttribute\Metadata(new Metadata(new PhraseaTag\TfCtime(), new MonoValue($mediaFile->getCTime()))));

        foreach ($metadatas as $meta) {
            $file->addAttribute(new BorderAttribute\Metadata($meta));
        }

        foreach ($metaFields as $metaField) {
            $file->addAttribute($metaField);
        }

        if ($grp_rid) {
            $file->addAttribute(new BorderAttribute\Story(new \record_adapter($app, $databox->get_sbas_id(), $grp_rid)));
        }

        $record = null;

        $postProcess = function ($element, $visa, $code) use (&$record) {
            $r = isset($visa);  // one way to avoid "variable not used" with phpstorm 10. ugly.
            unset($r);          //
            $r = isset($code);  // one way to avoid "variable not used" with phpstorm 10. ugly.
            unset($r);          //

            $record = $element;
        };

        /** @var borderManager $borderManager */
        $borderManager = $app['border-manager'];
        $borderManager->process($this->getLazaretSession($app), $file, $postProcess, $force);

        return $record;
    }

    /**
     *
     * @param \DOMDocument $dom
     * @param \DOMElement  $node
     * @param string       $path
     * @param string       $path_archived
     * @param string       $path_error
     * @param integer      $grp_rid
     */
    private function archiveFilesToGrp(Application $app, \databox $databox, \DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, $grp_rid, $stat0, $stat1, $moveError, $moveArchived)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $databox->get_connection();

        $nodesToDel = [];
        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            if (!$this->isStarted()) {
                break;
            }
            if ($n->getAttribute('isdir') == '1') {
                // in a grp, all levels goes in the same grp
                $node->setAttribute('archived', '1');  // the main grp folder is 'keep'ed, but not subfolders
                $this->archiveFilesToGrp($app, $databox, $dom, $n, $path . '/' . $n->getAttribute('name')
                    , $path_archived . '/' . $n->getAttribute('name')
                    , $path_error . '/' . $n->getAttribute('name')
                    , $grp_rid, $stat0, $stat1, $moveError, $moveArchived);
            } else {
                // a file
                $this->archiveFile($app, $databox, $dom, $n, $path, $path_archived, $path_error, $nodesToDel, $grp_rid, $stat0, $stat1, $moveError, $moveArchived);
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
     * @param \DOMDocument $dom
     * @param \DOMElement  $node
     * @param string       $path
     * @param string       $path_archived
     * @param string       $path_error
     * @param array        $nodesToDel    out, filled with files to delete
     * @param integer      $grp_rid
     */
    private function archiveFile(Application $app, \databox $databox, \DOMDocument $dom, \DOMElement $node, $path, $path_archived, $path_error, array &$nodesToDel, $grp_rid, $stat0, $stat1, $moveError, $moveArchived)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $databox->get_connection();

        $match = $node->getAttribute('match');

        if ($match == '*') {
            return;
        }

        $file = $node->getAttribute('name');
        $captionFileNode = null;

        if (!$match) {
            // the file does not match on any mask
            $this->log('debug', sprintf("File '%s' does not match any mask", $path . '/' . $file));
            $node->setAttribute('error', '1');

            return;
        }
        if ($match == '?') {
            // the caption file is missing
            $this->log('debug', sprintf("Caption of file '%s' is missing", $path . '/' . $file));
            $node->setAttribute('error', '1');

            return;
        }
        if ($match != '.') {  // match='.' : the file does not have a separate caption
            $xpath = new \DOMXPath($dom);
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

        $this->archiveFileAndCaption($app, $databox, $node, $captionFileNode, $path, $path_archived, $path_error, $grp_rid, $nodesToDel, $stat0, $stat1, $moveError, $moveArchived);
    }

    /**
     *
     * @param Application $app
     * @param \databox $databox
     * @param \DOMElement  $node
     * @param \DOMElement  $captionFileNode
     * @param string       $path
     * @param string       $path_archived
     * @param string       $path_error
     * @param integer      $grp_rid
     * @param array        $nodesToDel      out, filled with files to delete
     * @param $stat0
     * @param $stat1
     * @param $moveError
     * @param $moveArchived
     */
    private function archiveFileAndCaption(Application $app, \databox $databox, \DOMElement $node, \DOMElement $captionFileNode = null, $path, $path_archived, $path_error, $grp_rid, array &$nodesToDel, $stat0, $stat1, $moveError, $moveArchived)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();
        $databox->get_connection();

        $file = $node->getAttribute('name');
        $cid = $node->getAttribute('cid');
        $captionFileName = $captionFileNode ? $captionFileNode->getAttribute('name') : null;

        $this->log('info', sprintf("Archiving file '%s'", $path . '/' . $file));
        if ($captionFileName !== null) {
            $this->log('info', sprintf(' ' . (" (caption in '%s')"), $captionFileName));
        }
        if ($grp_rid !== 0) {
            $this->log('info', sprintf(' ' . (" into GRP rid=%s"), $grp_rid));
        }

        try {
            $collection = \collection::getByCollectionId($app, $databox, (int) $cid);

            if ($captionFileName === null) {
                $this->createRecord($app, $collection, $path . '/' . $file, null, $grp_rid, null, $stat0, $stat1);
            } else {
                $this->createRecord($app, $collection, $path . '/' . $file, $path . '/' . $captionFileName, $grp_rid, null, $stat0, $stat1);
            }

            $node->setAttribute('archived', '1');

            if ($captionFileNode) {
                $captionFileNode->setAttribute('archived', '1');
            }
        } catch (\Exception $e) {
            $this->log('debug', "Error : can't insert record : " . $e->getMessage());
            $node->setAttribute('error', '1');

            if ($captionFileNode) {
                $captionFileNode->setAttribute('error', '1');
            }
        }

        if ($node->getAttribute('archived') && $moveArchived) {
            $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $file));

            try {
                $app['filesystem']->mkdir($path_archived);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            try {
                $app['filesystem']->copy($path . '/' . $file, $path_archived . '/' . $file, true);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            if ($captionFileName != $file && $captionFileName) {
                $this->log('debug', sprintf('copy \'%s\' to \'archived\'', $path . '/' . $captionFileName));

                try {
                    $app['filesystem']->copy($path . '/' . $captionFileName, $path_archived . '/' . $captionFileName, true);
                } catch (IOException $e) {
                    $this->log('debug', $e->getMessage());
                }
            }
        }

        if ($node->getAttribute('error') && $moveError) {
            $this->log('debug', sprintf('copy \'%s\' to \'error\'', $path . '/' . $file));

            try {
                $app['filesystem']->mkdir($path_error);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            try {
                $app['filesystem']->copy($path . '/' . $file, $path_error . '/' . $file, true);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            if ($captionFileName != $file && $captionFileName) {
                $this->log('debug', sprintf('copy \'%s\' to \'error\'', $path . '/' . $captionFileName));

                try {
                    $app['filesystem']->copy($path . '/' . $captionFileName, $path_error . '/' . $captionFileName, true);
                } catch (IOException $e) {
                    $this->log('debug', $e->getMessage());
                }
            }
        }

        if (!$node->getAttribute('keep')) {
            $file = $node->getAttribute('name');

            try {
                $app['filesystem']->remove($path . '/' . $file);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            $nodesToDel[] = $node;
        }

        if ($captionFileNode && !$captionFileNode->getAttribute('keep')) {
            $file = $captionFileNode->getAttribute('name');

            try {
                $app['filesystem']->remove($path . '/' . $file);
            } catch (IOException $e) {
                $this->log('debug', $e->getMessage());
            }

            $nodesToDel[] = $captionFileNode;
        }

        return;
    }

    /**
     * xml facility : set attributes to a node and all children
     *
     * @staticvar integer $iloop
     * @param \DOMDocument $dom
     * @param \DOMElement  $node
     * @param array        $attributes An associative array of attributes
     * @param integer      $depth
     */
    private function setAllChildren(\DOMDocument $dom, \DOMElement $node, array $attributes, $depth = 0)
    {
        static $iloop = 0;
        if ($depth == 0) {
            $iloop = 0;
        }

        foreach ($attributes as $a => $v) {
            $node->setAttribute($a, $v);
        }

        if (($iloop++ % 100) == 0) {
            usleep(1000);
        }

        for ($n = $node->firstChild; $n; $n = $n->nextSibling) {
            $this->setAllChildren($dom, $n, $attributes, $depth + 1);
        }
    }

    /**
     * Return the story settings
     *
     * @param  string $file
     * @return array
     */
    private function getGrpSettings($file, $tmaskgrp)
    {
        $matched = false;

        foreach ($tmaskgrp as $maskgrp) {
            $preg_maskgrp = "/" . $maskgrp["mask"] . "/";
            if (preg_match($preg_maskgrp, $file)) {
                $matched = $maskgrp;
            }
            if ($matched) {
                break;
            }
        }

        return $matched;
    }

    /**
     * Return a LazaretSession
     *
     * @return LazaretSession
     */
    protected function getLazaretSession(Application $app)
    {
        // quick fix to reconnect if mysql is lost
        $app->getApplicationBox()->get_connection();

        $lazaretSession = new LazaretSession();

        $app['orm.em']->persist($lazaretSession);
        $app['orm.em']->flush();

        return $lazaretSession;
    }

    /**
     * Map a Bag of metadatas indexed by **Tagnames** to a bag of metadatas
     * indexed by **FieldNames**
     *
     * @param  \databox_descriptionStructure $metadatasStructure The databox structure related
     * @param  ExiftoolMetadataBag           $bag                The metadata bag
     * @return MetadataBag
     */
    protected function getIndexByFieldName(\databox_descriptionStructure $metadatasStructure, ExiftoolMetadataBag $bag)
    {
        $ret = new MetadataBag();

        /** @var \databox_field $databox_field */
        foreach ($metadatasStructure as $databox_field) {
            if ($bag->containsKey($databox_field->get_tag()->getTagname())) {
                $ret->set($databox_field->get_name(), $bag->get($databox_field->get_tag()->getTagname()));
            }
        }

        return $ret;
    }

    protected function readXMLForDatabox(Application $app, \databox_descriptionStructure $metadatasStructure, $pathfile)
    {
        if (false === $app['filesystem']->exists($pathfile)) {
            throw new \InvalidArgumentException(sprintf('file %s does not exists', $pathfile));
        }

        if (false === $sxcaption = @simplexml_load_file($pathfile)) {
            throw new \InvalidArgumentException(sprintf('Invalid XML file %s', $pathfile));
        }

        $metadataBag = new MetaFieldsBag();

        foreach ($sxcaption->description->children() as $tagname => $value) {
            $value = trim($value);

            $meta = $metadatasStructure->get_element_by_name(trim($tagname));
            if (!$meta) {
                continue;
            }

            if ($meta->is_multi()) {
                $fields = \caption_field::get_multi_values($value, $meta->get_separator());

                if (!$metadataBag->containsKey($meta->get_name())) {
                    $values = $fields;
                } else {
                    $values = array_merge($metadataBag->get($meta->get_name())->getValue(), $fields);
                }

                $metadataBag->set($meta->get_name(), new BorderAttribute\MetaField($meta, $values));
            } else {
                $metadataBag->set($meta->get_name(), new BorderAttribute\MetaField($meta, [$value]));
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
    protected function parseStatusBit($sxcaption)
    {
        if (!$sxcaption instanceof \SimpleXMLElement) {
            return null;
        }

        if ('' !== $inStatus = (string) $sxcaption->status) {
            return $inStatus;
        }

        return null;
    }

    private function listFolder($path)
    {
        $list = [];
        if ($hdir = opendir($path)) {
            while (false !== $file = readdir($hdir)) {
                $list[] = $file;
            }
            closedir($hdir);
            natcasesort($list);
        }

        return $list;
    }
}
