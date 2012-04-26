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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
phrasea::headers(200, true);
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bid"
    , 'piv'
    , "id"
    , 't'
    , 'dlg'
);

set_time_limit(300);

$imported = false;
$err = '';

if ($parm["bid"] !== null) {
    $loaded = false;
    try {
        $databox = databox::get_instance((int) $parm['bid']);
        $connbas = connection::getPDOConnection($parm['bid']);

        $dom = $databox->get_dom_thesaurus();

        if ($dom) {
            $err = '';
            if ($parm['id'] == '') {
                // on importe un theaurus entier
                $node = $dom->documentElement;
                while ($node->firstChild)
                    $node->removeChild($node->firstChild);

                $err = importFile($dom, $node);
            } else {
                // on importe dans une branche
                $err = 'not implemented';
            }

            if ( ! $err) {
                $imported = true;
                $databox->saveThesaurus($dom);
            }
        }

        if ( ! $err) {
            $meta_struct = $databox->get_meta_structure();

            foreach ($meta_struct->get_elements() as $meta_field) {
                $meta_field->set_tbranch('')->save();
            }

            $dom = $databox->get_dom_cterms();
            if ($dom) {
                $node = $dom->documentElement;
                while ($node->firstChild)
                    $node->removeChild($node->firstChild);

                $databox->saveCterms($dom);
            }

            $sql = 'UPDATE RECORD SET status=status & ~3';
            $stmt = $connbas->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }
    } catch (Exception $e) {

    }
}

if ($parm["dlg"]) {
    $opener = "window.dialogArguments.win";
} else {
    $opener = "opener";
}
?>

<html lang="<?php echo $session->get_I18n(); ?>">
    <body onload='parent.importDone("<?php print(p4string::MakeString($err, 'js')); ?>");'>
    </body>
</html>

<?php

function checkEncoding($string, $string_encoding)
{
    $fs = $string_encoding == 'UTF-8' ? 'UTF-32' : $string_encoding;

    $ts = $string_encoding == 'UTF-32' ? 'UTF-8' : $string_encoding;

    return $string === mb_convert_encoding(mb_convert_encoding($string, $fs, $ts), $ts, $fs);
}

function importFile($dom, $node)
{
    global $parm;
    $err = '';

    $unicode = new unicode();

    $cbad = array();
    $cok = array();
    for ($i = 0; $i < 32; $i ++ ) {
        $cbad[] = chr($i);
        $cok[] = '_';
    }

    if (($fp = fopen($_FILES['fil']['tmp_name'], 'rb'))) {
        $iline = 0;
        $curdepth = -1;
        $tid = array(-1    => -1, 0     => -1);
        while ( ! $err && ! feof($fp) && ($line = fgets($fp)) !== FALSE) {
            $iline ++;
            if (trim($line) == '')
                continue;
            for ($depth = 0; $line != '' && $line[0] == "\t"; $depth ++ )
                $line = substr($line, 1);
            if ($depth > $curdepth + 1) {
                $err = sprintf(_("over-indent at line %s"), $iline);
                continue;
            }

            $line = trim($line);

            if ( ! checkEncoding($line, 'UTF-8')) {
                $err = sprintf(_("bad encoding at line %s"), $iline);
                continue;
            }

            $line = str_replace($cbad, $cok, ($oldline = $line));
            if ($line != $oldline) {
                $err = sprintf(_("bad character at line %s"), $iline);
                continue;
            }

            while ($curdepth >= $depth) {
                $curdepth --;
                $node = $node->parentNode;
            }
            $curdepth = $depth;

            $nid = (int) ($node->getAttribute('nextid'));
            $id = $node->getAttribute('id') . '.' . $nid;
            $pid = $node->getAttribute('id');

            $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

            $node->setAttribute('nextid', (string) ($nid + 1));

            $te = $node->appendChild($dom->createElement('te'));
            $te->setAttribute('id', $te_id);

            $node = $te;

            $tsy = explode(';', $line);
            $nsy = 0;
            foreach ($tsy as $syn) {
                $lng = $parm['piv'];
                $hit = '';
                $kon = '';

                if (($ob = strpos($syn, '[')) !== false) {
                    if (($cb = strpos($syn, ']', $ob)) !== false) {
                        $lng = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                        $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                    } else {
                        $lng = trim(substr($syn, $ob + 1));
                        $syn = substr($syn, 0, $ob);
                    }

                    if (($ob = strpos($syn, '[')) !== false) {
                        if (($cb = strpos($syn, ']', $ob)) !== false) {
                            $hit = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                            $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                        } else {
                            $hit = trim(substr($syn, $ob + 1));
                            $syn = substr($syn, 0, $ob);
                        }
                    }
                }
                if (($ob = strpos($syn, '(')) !== false) {
                    if (($cb = strpos($syn, ')', $ob)) !== false) {
                        $kon = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                        $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                    } else {
                        $kon = trim(substr($syn, $ob + 1));
                        $syn = substr($syn, 0, $ob);
                    }
                }

                $syn = trim($syn);

                $sy = $node->appendChild($dom->createElement('sy'));
                $sy->setAttribute('id', $te_id . '.' . $nsy);
                $v = $syn;
                if ($kon)
                    $v .= ' (' . $kon . ')';
                $sy->setAttribute('v', $v);
                $sy->setAttribute('w', $unicode->remove_indexer_chars($syn));
                if ($kon)
                    $sy->setAttribute('k', $unicode->remove_indexer_chars($kon));

                $sy->setAttribute('lng', $lng);

                $nsy ++;
            }

            $te->setAttribute('nextid', (string) $nsy);
        }

        fclose($fp);
    }

    return($err);
}

function no_dof($dom, $node)
{
    global $parm;
    $unicode = new unicode();

    $t = $parm['t'];


    $t = preg_replace('/\\r|\\n/', '£', $t);
    $t = preg_replace('/££*/', '£', $t);
    $t = preg_replace('/£\\s*;/', ' ;', $t);
    $tlig = explode('£', $t);

    $mindepth = 999999;
    foreach ($tlig as $lig) {
//    echo('.');
//    flush();

        if (trim($lig) == '')
            continue;
        for ($depth = 0; $lig != '' && $lig[$depth] == "\t"; $depth ++ )
            ;
        if ($depth < $mindepth)
            $mindepth = $depth;
    }

    $curdepth = -1;
    $tid = array(-1 => -1, 0  => -1);
    foreach ($tlig as $lig) {
//    echo('-');
//    flush();

        $lig = substr($lig, $mindepth);
        if (trim($lig) == '')
            continue;
        for ($depth = 0; $lig != '' && $lig[0] == "\t"; $depth ++ )
            $lig = substr($lig, 1);

//    printf("curdepth=%s, depth=%s : %s\n", $curdepth, $depth, $lig);

        if ($depth > $curdepth + 1) {
            // error
//      print('<span style="color:#ff0000">error over-indent at</span> \'' . $lig . "'\n");
            continue;
        }

        while ($curdepth >= $depth) {
            $curdepth --;
            $node = $node->parentNode;
        }
        $curdepth = $depth;

        $nid = (int) ($node->getAttribute('nextid'));
        $id = $node->getAttribute('id') . '.' . $nid;
        $pid = $node->getAttribute('id');

// print("pid=".$pid);

        $te_id = ($pid ? ($pid . '.') : 'T') . $nid;

        $node->setAttribute('nextid', (string) ($nid + 1));

        $te = $node->appendChild($dom->createElement('te'));
        $te->setAttribute('id', $te_id);

        $node = $te;

        $tsy = explode(';', $lig);
        $nsy = 0;
        foreach ($tsy as $syn) {
            $lng = $parm['piv'];
            $hit = '';
            $kon = '';

            if (($ob = strpos($syn, '[')) !== false) {
                if (($cb = strpos($syn, ']', $ob)) !== false) {
                    $lng = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                    $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                } else {
                    $lng = trim(substr($syn, $ob + 1));
                    $syn = substr($syn, 0, $ob);
                }

                if (($ob = strpos($syn, '[')) !== false) {
                    if (($cb = strpos($syn, ']', $ob)) !== false) {
                        $hit = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                        $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                    } else {
                        $hit = trim(substr($syn, $ob + 1));
                        $syn = substr($syn, 0, $ob);
                    }
                }
            }
            if (($ob = strpos($syn, '(')) !== false) {
                if (($cb = strpos($syn, ')', $ob)) !== false) {
                    $kon = trim(substr($syn, $ob + 1, $cb - $ob - 1));
                    $syn = substr($syn, 0, $ob) . substr($syn, $cb + 1);
                } else {
                    $kon = trim(substr($syn, $ob + 1));
                    $syn = substr($syn, 0, $ob);
                }
            }
            /*
             */
            $syn = trim($syn);

            //    for($id='T',$i=0; $i<=$curdepth; $i++)
            //      $id .= '.' . $tid[$i];
//  $id = '?';
//      printf("depth=%s (%s) ; sy='%s', kon='%s', lng='%s', hit='%s' \n", $depth, $id, $syn, $kon, $lng, $hit);

            /*
              $nid = (int)($node->getAttribute('nextid'));
              $pid = $node->getAttribute('id');

              $id = ($pid ? ($pid.'.'):'T') . $nid ;
              $node->setAttribute('nextid', (string)($nid+1));

             */
            $sy = $node->appendChild($dom->createElement('sy'));
            $sy->setAttribute('id', $te_id . '.' . $nsy);
            $v = $syn;
            if ($kon)
                $v .= ' (' . $kon . ')';
            $sy->setAttribute('v', $v);
            $sy->setAttribute('w', $unicode->remove_indexer_chars($syn));
            if ($kon)
                $sy->setAttribute('k', $unicode->remove_indexer_chars($kon));

            $sy->setAttribute('lng', $lng);

            $nsy ++;
        }

        $te->setAttribute('nextid', (string) $nsy);
    }
}
