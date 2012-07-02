<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\HttpFoundation\File\File as SymfoFile;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();
$registry = $appbox->get_registry();
$request = http_request::getInstance();
$parm = $request->get_parms("act", "p0", // id de la base
                            "sta", // afficher les stats de base (1) ou non (0)
                            "srt", // trier les colonnes de stats par collection (col) ou objet (obj)
                            "nvn", // New ViewName ( lors de l'UPD
                            "othcollsel", "coll_id", "base_id"
);

if ( ! $parm["srt"])
    $parm["srt"] = "col";

$sbas_id = (int) $parm['p0'];
$databox = databox::get_instance($sbas_id);

phrasea::headers();

$printLogoUploadMsg = "";

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_manage')) {
    switch ($parm["act"]) {
        case "SENDLOGOPDF":
            if (isset($_FILES['newLogoPdf']) && $_FILES['newLogoPdf']['error'] == UPLOAD_ERR_OK) {
                if ($_FILES['newLogoPdf']['size'] < 65536) {
                    $appbox->write_databox_pic($databox, new SymfoFile($_FILES['newLogoPdf']["tmp_name"]), databox::PIC_PDF);
                    unlink($_FILES['newLogoPdf']["tmp_name"]);
                } else {
                    $printLogoUploadMsg = _('forms::erreur lors de l\'envoi du fichier');
                }
            } else {
                $printLogoUploadMsg = _('forms::erreur lors de l\'envoi du fichier');
            }
            break;
        case 'MOUNT':
            $appbox->get_connection()->beginTransaction();
            try {
                $base_id = collection::mount_collection($sbas_id, $parm['coll_id'], $user);
                if ( ! is_null($parm['othcollsel'])) {

                    $query = new User_Query($appbox);
                    $total = $query->on_base_ids(array($parm["othcollsel"]))->get_total();
                    $n = 0;
                    while ($n < $total) {
                        $results = $query->limit($n, 50)->execute()->get_results();
                        foreach ($results as $user) {
                            $user->ACL()->duplicate_right_from_bas($parm["othcollsel"], $base_id);
                        }
                        $n+=50;
                    }
                }
                $appbox->get_connection()->commit();
            } catch (Exception $e) {
                $appbox->get_connection()->rollBack();
            }
            break;
        case 'ACTIVATE':
            try {
                $collection = collection::get_from_base_id($parm['base_id']);
                $collection->enable($appbox);
            } catch (Exception $e) {

            }
            break;
    }
}
?>

        <script type="text/javascript">
<?php
if ($parm['act']) {
    print("reloadTree('base:" . $parm['p0'] . "');");
}
?>

        function sendLogopdf()
        {
            document.forms["flpdf"].target = "";
            document.forms["flpdf"].act.value = "SENDLOGOPDF";
            document.forms["flpdf"].submit();
        }
        function deleteLogoPdf()
        {
            if(confirm("<?php echo _('admin::base: Supprimer le logo pour impression') ?>"))
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php",
                    dataType: 'json',
                    data: { action:"DELLOGOPDF", p0:<?php echo $sbas_id ?>},
                    success: function(data){
                        $("#printLogoDIV_OK").hide();
                        $("#printLogoDIV_NONE").show();
                    }
                });
            }
        }
        function reindex()
        {
            if(confirm('<?php echo str_replace("'", "\'", _('Confirmez-vous la re-indexation de la base ?')); ?>'))
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php",
                    dataType: 'json',
                    data: { action:"REINDEX", sbas_id:<?php echo $sbas_id ?>},
                    success: function(data){
                    }
                });
            }
        }

        function makeIndexable(el)
        {
            $.ajax({
                type: "POST",
                url: "/admin/adminFeedback.php",
                dataType: 'json',
                data: { action:"MAKEINDEXABLE", sbas_id:<?php echo $sbas_id ?>, INDEXABLE:(el.checked?'1':'')  },
                success: function(data){
                }
            });
        }

        var __viewname = "";    // global will be updated by refreshContent
        function chgViewName()
        {
            if( (newAlias = prompt("<?php echo(_('admin::base: Alias')) ?> :", __viewname)) != null)
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php",
                    dataType: 'json',
                    data: { action:"CHGVIEWNAME", sbas_id:<?php echo $sbas_id ?>, viewname:newAlias},
                    success: function(data){
                    }
                });
            }
        }

        function emptyBase()
        {
            if(confirm("<?php echo _('admin::base: Confirmer le vidage complet de la base') ?>"))
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php?action=EMPTYBASE",
                    dataType: 'json',
                    data: { sbas_id:<?php echo $sbas_id ?>  },
                    success: function(data){
                        alert(data.message);
                    }
                });
            }
        }

        function refreshContent()
        {
            $.ajax({
                type: "POST",
                url: "/admin/adminFeedback.php",
                dataType: 'json',
                data: { action:"P_BAR_INFO", sbas_id:"<?php echo $sbas_id ?>"},
                success: function(data){
                    __viewname = data.viewname;  // global
                    if(data.viewname == '')
                        $("#viewname").html("<i><?php echo(_('admin::base: aucun alias')) ?></i>");
                    else
                        $("#viewname").html("<b>"+data.viewname+"</b>");
                    $("#nrecords").text(data.records);
                    $("#is_indexable").attr('checked', data.indexable);
                    $("#xml_indexed").text(data.xml_indexed);
                    $("#thesaurus_indexed").text(data.thesaurus_indexed);
                    if(data.records > 0)
                    {
                        var p;
                        p = 100*data.xml_indexed/data.records;
                        $("#xml_indexed_bar").width(Math.round(2*p));  // 0..200px
                        $("#xml_indexed_percent").text((Math.round(p*100)/100)+" %");
                        p = 100*data.thesaurus_indexed/data.records;
                        $("#thesaurus_indexed_bar").width(Math.round(2*p));
                        $("#thesaurus_indexed_percent").text((Math.round(p*100)/100)+" %");
                    }
                    if(data.printLogoURL)
                    {
                        $("#printLogo").attr("src", data.printLogoURL);
                        $("#printLogoDIV_NONE").hide();
                        $("#printLogoDIV_OK").show();
                    }
                    else
                    {
                        $("#printLogoDIV_OK").hide();
                        $("#printLogoDIV_NONE").show();
                    }
                }
            });
        }

        function deleteBase()
        {
            $.ajax({
                type: "POST",
                url: "/admin/adminFeedback.php",
                dataType: 'json',
                data: { action:"P_BAR_INFO", sbas_id:<?php echo $sbas_id ?> },
                success: function(data){
                    if(data.records > 0)
                    {
                        alert("<?php echo(_('admin::base: vider la base avant de la supprimer')) ?>");
                    }
                    else
                    {
                        if(confirm("<?php echo _('admin::base: Confirmer la suppression de la base') ?>"))
                        {
                            $.ajax({
                                type: "POST",
                                url: "/admin/adminFeedback.php",
                                dataType: 'json',
                                data: { action:"DELETEBASE", sbas_id:<?php echo $sbas_id ?> },
                                success: function(data){
                                    if(data.err == 0)    // ok
                                    {
                                        parent.$("#TREE_DATABASES").trigger('click');
                                        parent.reloadTree("bases");
                                    }
                                    else
                                    {
                                        if(data.errmsg)
                                            alert(data.errmsg);
                                    }
                                }
                            });
                        }
                    }
                }
            });
        }
        function clearAllLog()
        {
            if(confirm("<?php echo _('admin::base: Confirmer la suppression de tous les logs') ?>"))
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php",
                    dataType: 'json',
                    data: { action:"CLEARALLLOG", sbas_id:<?php echo $sbas_id ?>
                    },
                    success: function(data){
                    }
                });
            }
        }

        function mountColl()
        {
            $('#mount_coll').toggle();
        }

        function activateColl()
        {
            $('#activate_coll').toggle();
        }

        function umountBase()
        {
            if(confirm("<?php echo _('admin::base: Confirmer vous l\'arret de la publication de la base') ?>"))
            {
                $.ajax({
                    type: "POST",
                    url: "/admin/adminFeedback.php",
                    dataType: 'json',
                    data: { action:"UNMOUNTBASE", sbas_id:<?php echo $sbas_id ?>
                    },
                    success: function(data){
                        parent.$("#TREE_DATABASES").trigger('click');
                    }
                });
            }
        }

        function showDetails(sta)
        {
            document.forms["manageDatabase"].target = "";
            document.forms["manageDatabase"].act.value = "";
            document.forms["manageDatabase"].sta.value = sta;
            document.forms["manageDatabase"].submit();
        }
        function chgOrd(srt)
        {
            document.forms["manageDatabase"].target = "";
            document.forms["manageDatabase"].act.value = "";
            document.forms["manageDatabase"].sta.value = "1";
            document.forms["manageDatabase"].srt.value = srt;
            document.forms["manageDatabase"].submit();
        }
        $(document).ready(function(){
            refreshContent();
        });
        </script>

        <style type="text/css">
            .logo_boxes
            {
                margin:5px 5px 5px 10px;
                padding-top:5px;
                border-top:2px solid black;
            }
        </style>
<?php
$out = "";
?>
        <div style='margin:3px 0 3px 10px;'>
            <h2>
<?php echo $databox->get_serialized_server_info(); ?>
            </h2>
        </div>
        <div style='margin:3px 0 3px 10px;'>
            ID : <?php echo($sbas_id) ?>
        </div>

        <div style='margin:3px 0 3px 10px;'>
<?php echo(_('admin::base: Alias')) ?> : <span id="viewname"></span>
<?php
if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_manage')) {
    ?>
                <img src='/skins/icons/edit_0.gif' onclick="chgViewName();return(false);" style='vertical-align:middle'/>
            <?php
        }
        ?>
        </div>
                <?php
                $nrecords = $databox->get_record_amount();


                // stats sur la base distante
                $out .= "<div style='margin:3px 0 3px 10px;'>";
                $out .= _('admin::base: nombre d\'enregistrements sur la base :') . '<span id="nrecords"></span> ';

                if ((int) $parm["sta"] < 1) {
                    $out .= " (<a href=\"javascript:void(0);\" onclick=\"showDetails(1);return(false);\">" . _('phraseanet:: details') . "</a>)";
                } else {
                    $unique_keywords = $databox->get_unique_keywords();

                    $out .= ", &nbsp;&nbsp;";
                    $out .= _('admin::base: nombre de mots uniques sur la base : ') . ' ' . $unique_keywords;

                    $indexes = $databox->get_index_amount();

                    $out .= ", &nbsp;&nbsp;";
                    $out .= _('admin::base: nombre de mots indexes sur la base') . ' ' . $indexes;

                    if ($registry->get('GV_thesaurus')) {
                        $thits = $databox->get_thesaurus_hits();

                        $out .= ", &nbsp;&nbsp;";
                        $out .= _('admin::base: nombre de termes de Thesaurus indexes :') . ' ' . $thits;
                    }

                    $out .= " (<a href=\"javascript:void(0);\" onclick=\"showDetails(0);return(false);\">" . _('admin::base: masquer les details') . "</a>)<br />\n";


                    $trows = $databox->get_record_details($parm['srt']);

                    $out .= "<table class=\"ulist\"><col width=180px><col width=100px><col width=60px><col width=80px><col width=70px>\n";
                    $out .= "<thead> <tr>";
                    $out .= "<th onClick=\"chgOrd('col');\">";
                    if ($parm["srt"] == "col")
                        $out .= "<img src=\"/skins/icons/tsort_desc.gif\">&nbsp;";
                    $out .= _('phraseanet:: collection') . "</th>";

                    $out .= "<th onClick=\"chgOrd('obj');\">";
                    if ($parm["srt"] == "obj")
                        $out .= "<img src=\"/skins/icons/tsort_desc.gif\">&nbsp;";
                    $out .= _('admin::base: objet') . "</th>";

                    $out .= "<th>" . _('admin::base: nombre') . "</th>";
                    $out .= "<th>" . _('admin::base: poids') . " (Mo)</th>";
                    $out .= "<th>" . _('admin::base: poids') . " (Go)</th>";
                    $out .= "</tr> </thead><tbody>";
                    $totobj = 0;
                    $totsiz = "0";  // les tailles de fichiers sont calculees avec bcmath
                    foreach ($trows as $kgrp => $vgrp) {
                        // ksort($vgrp);
                        $midobj = 0;
                        $midsiz = "0";
                        $last_k1 = $last_k2 = null;
                        foreach ($vgrp as $krow => $vrow) {
                            if ($last_k1 !== $vrow["coll_id"]) {

                            }
                            if ($vrow["n"] > 0 || $last_k1 !== $vrow["coll_id"]) {
                                $midobj += $vrow["n"];
                                if (extension_loaded("bcmath"))
                                    $midsiz = bcadd($midsiz, $vrow["siz"], 0);
                                else
                                    $midsiz += $vrow["siz"];
                                $out .= "<tr>\n";
                                if ($last_k1 !== $vrow["coll_id"]) {
                                    if ((int) $vrow["lostcoll"] <= 0) {
                                        $out .= "<td>" . $vrow["asciiname"] . "</td>\n";
                                    } else {
                                        $out .= "<td style=\"color:red\"><i>" . _('admin::base: enregistrements orphelins') . " </i>" . sprintf("(coll_id=%s)", $vrow["coll_id"]) . "</td>";
                                    }
                                    $last_k1 = $vrow["coll_id"];
                                } else {
                                    $out .= "<td></td>\n";
                                }
                                if ($last_k2 !== $vrow["name"])
                                    $out .= "<td>" . ($last_k2 = $vrow["name"]) . "</td>\n";
                                else
                                    $out .= "<td></td>\n";
                                $out .= "<td style=\"text-align:right\">&nbsp;" . $vrow["n"] . "&nbsp;</td>\n";
                                if (extension_loaded("bcmath"))
                                    $mega = bcdiv($vrow["siz"], 1024 * 1024, 5);
                                else
                                    $mega = $vrow["siz"] / (1024 * 1024);
                                if (extension_loaded("bcmath"))
                                    $giga = bcdiv($vrow["siz"], 1024 * 1024 * 1024, 5);
                                else
                                    $giga = $vrow["siz"] / (1024 * 1024 * 1024);
                                $out .= "<td style=\"text-align:right\">&nbsp;" . sprintf("%.2f", $mega) . "&nbsp;</td>\n";
                                $out .= "<td style=\"text-align:right\">&nbsp;" . sprintf("%.2f", $giga) . "&nbsp;</td>\n";
                                $out .= "</tr>\n";
                            }
                            // $last_k1 = null;
                        }
                        $totobj += $midobj;
                        if (extension_loaded("bcmath"))
                            $totsiz = bcadd($totsiz, $midsiz, 0);
                        else
                            $totsiz += $midsiz;
                        $out .= "<tr>\n";
                        $out .= "<td></td>\n";
                        $out .= "<td style=\"text-align:right\"><i>" . _('report:: total') . "</i></td>\n";
                        $out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . $midobj . "&nbsp;</td>\n";
                        if (extension_loaded("bcmath"))
                            $mega = bcdiv($midsiz, 1024 * 1024, 5);
                        else
                            $mega = $midsiz / (1024 * 1024);

                        if (extension_loaded("bcmath"))
                            $giga = bcdiv($midsiz, 1024 * 1024 * 1024, 5);
                        else
                            $giga = $midsiz / (1024 * 1024 * 1024);
                        $out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . sprintf("%.2f", $mega) . "&nbsp;</td>\n";
                        $out .= "<td style=\"text-align:right; TEXT-DECORATION:overline\">&nbsp;" . sprintf("%.2f", $giga) . "&nbsp;</td>\n";
                        $out .= "</tr>\n";
                        $out .= "<tr><td colspan=\"5\"><hr /></td></tr>\n";
                    }
                    $out .= "<tr>\n";
                    $out .= "<td colspan=\"2\" style=\"text-align:right\"><b>" . _('report:: total') . "</b></td>\n";
                    $out .= "<td style=\"text-align:right;\">&nbsp;<b>" . $totobj . "</b>&nbsp;</td>\n";
                    if (extension_loaded("bcmath"))
                        $mega = bcdiv($totsiz, 1024 * 1024, 5);
                    else
                        $mega = $totsiz / (1024 * 1024);
                    if (extension_loaded("bcmath"))
                        $giga = bcdiv($totsiz, 1024 * 1024 * 1024, 5);
                    else
                        $giga = $totsiz / (1024 * 1024 * 1024);
                    $out .= "<td style=\"text-align:right;\">&nbsp;<b>" . sprintf("%.2f", $mega) . "</b>&nbsp;</td>\n";
                    $out .= "<td style=\"text-align:right;\">&nbsp;<b>" . sprintf("%.2f", $giga) . "</b>&nbsp;</td>\n";
                    $out .= "</tr>\n";

                    $out .= "</tbody></table>";
                }
                $out .= "</div>";

                print($out);
                ?>

        <div style='margin:3px 0 3px 10px;'>
            <div id='INDEX_P_BAR'>
                <div style='height:30px;'>
                    <div>
        <?php echo(_('admin::base: document indexes en utilisant la fiche xml')); ?> :
                        <span id='xml_indexed'></span>
                    </div>
                    <div id='xml_indexed_bar' style='position:absolute;width:0px;height:15px;background:#d4d0c9;z-index:6;'>
                    </div>
                    <div id='xml_indexed_percent' style='position:absolute;width:198px;height:13px;text-align:center;border:1px solid black;z-index:10;'>
                    </div>
                </div>
                <div style='height:30px;'>
                    <div>
        <?php echo(_('admin::base: document indexes en utilisant le thesaurus')); ?> :
                        <span id='thesaurus_indexed'></span>
                    </div>
                    <div id='thesaurus_indexed_bar' style='position:absolute;width:0px;height:15px;background:#d4d0c9;z-index:6;'>
                    </div>
                    <div id='thesaurus_indexed_percent' style='position:absolute;width:198px;height:13px;text-align:center;border:1px solid black;z-index:10;'>
                    </div>
                </div>
            </div>
        <?php
        if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_manage')) {
            ?>
                <div style='margin:15px 5px 0px 0px;'>
                    <input type='checkbox'  id='is_indexable' onclick='makeIndexable(this)'/>
                    <label for='is_indexable<?php echo($parm["p0"]); ?>'>
            <?php echo(_('admin::base: Cette base est indexable')); ?>
                    </label>
                    <div style='display:none' id='make_indexable_ajax_status'>&nbsp;</div>
                </div>

                <div>
                    <a href="javascript:void(0);return(false);" onclick="reindex();return(false);">
                            <?php echo(_('base:: re-indexer')); ?>
                    </a>
                </div>
            </div>

            <div style='margin:20px 0 3px 10px;'>
                <a href="newcoll.php?act=GETNAME&p0=<?php echo($parm["p0"]); ?>">
                    <img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
    <?php echo(_('admin::base:collection: Creer une collection')); ?>
                </a>
            </div>
                            <?php
                            $mountable_colls = $databox->get_mountable_colls();

                            if (count($mountable_colls) > 0) {
                                ?>
                <div style='margin:20px 0 3px 10px;'>
                    <a href="#" onclick="mountColl();">
                        <img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
                    <?php echo(_('admin::base:collection: Monter une collection')); ?>
                    </a>
                </div>
                <div id="mount_coll" style="display:none;">
                    <form method="post" action="database.php" target="_self">
                        <select name="coll_id">
        <?php
        foreach ($mountable_colls as $coll_id => $name) {
            ?>
                                <option value="<?php echo $coll_id ?>"><?php echo $name ?></option>
            <?php
        }
        ?>
                        </select>
                            <?php
                            $colls = $user->ACL()->get_granted_base(array('canadmin'));
                            if (count($colls) > 0) {
                                ?>
                            <span>
            <?php echo _('admin::base:collection: Vous pouvez choisir une collection de reference pour donenr des acces ') ?>
                            </span>
                            <select name="othcollsel" >
                                <option><?php echo _('choisir') ?></option>
                            <?php
                            foreach ($colls as $base_id => $collection)
                                echo "<option value='" . $base_id . "'>" . $collection->get_name() . '</option>';
                            ?>
                            </select>
                    <?php
                }
                ?>
                        <input type="hidden" name="p0" value="<?php echo $sbas_id; ?>"/>
                        <input type="hidden" name="act" value="MOUNT"/>
                        <button type="submit"><?php echo _('Monter'); ?></button>
                    </form>
                </div>
        <?php
    }

    $activable_colls = $databox->get_activable_colls();

    if (count($activable_colls) > 0) {
        ?>
                <div style='margin:20px 0 3px 10px;'>
                    <a href="#" onclick="activateColl();">
                        <img src='/skins/icons/create_coll.png' style='vertical-align:middle'/>
                            <?php echo(_('Activer une collection')); ?>
                    </a>
                </div>
                <div id="activate_coll" style="display:none;">
                    <form method="post" action="database.php" target="_self">
                        <select name="base_id">
                        <?php
                        foreach ($activable_colls as $base_id) {
                            ?>
                                <option value="<?php echo $base_id ?>"><?php echo phrasea::bas_names($base_id) ?></option>
                                <?php
                            }
                            ?>
                        </select>
                        <input type="hidden" name="p0" value="<?php echo $sbas_id; ?>"/>
                        <input type="hidden" name="act" value="ACTIVATE"/>
                        <button type="submit"><?php echo _('Activer'); ?></button>
                    </form>
                </div>
                        <?php
                    }
                    ?>
            <div style='margin:20px 0 3px 10px;'>
                <a href="javascript:void(0);return(false);" onclick="clearAllLog();return(false);">
                    <img src='/skins/icons/clearLogs.png' style='vertical-align:middle'/>
    <?php echo(_('admin::base: supprimer tous les logs')); ?>
                </a>
            </div>
            <div style='margin:20px 0 13px 10px;'>
                <a href="javascript:void(0);return(false);" onclick="umountBase();return(false);">
                    <img src='/skins/icons/db-remove.png' style='vertical-align:middle'/>
            <?php echo(_('admin::base: arreter la publication de la base')); ?>
                </a>
            </div>
            <div style='margin:3px 0 3px 10px;'>
                <a href="javascript:void(0);return(false);" onclick="emptyBase();return(false);">
                    <img src='/skins/icons/trash.png' style='vertical-align:middle'/>
                    <?php echo(_('admin::base: vider la base')); ?>
                </a>
            </div>
            <div style='margin:3px 0 3px 10px;'>
                <a href="javascript:void(0);return(false);" onclick="deleteBase();return(false);">
                    <img src='/skins/icons/delete.gif' style='vertical-align:middle'/>
                        <?php echo(_('admin::base: supprimer la base')); ?>
                </a>
            </div>
                        <?php
                    }
                    ?>

        <!-- minilogo pour print pdf -->
        <div class='logo_boxes'>
            <div style="font-size:11px;font-weight:bold;margin:0px 3px 10px 0px;">
<?php echo(_('admin::base: logo impression PDF')) ?>
            </div>

<?php echo($printLogoUploadMsg) ?>

            <div id='printLogoDIV_OK' style='margin:0 0 5px 0; display:none'>
                <img id='printLogo' src="/print/<?php echo $sbas_id ?>" />

<?php
if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_manage')) {
    ?>
                    <a href="javascript:void();return(false);" onclick="deleteLogoPdf();return(false);">
    <?php echo(_('admin::base:collection: supprimer le logo')) ?>
                    </a>
    <?php
}
?>
            </div>

            <div id='printLogoDIV_NONE' style='margin:0 0 5px 0; display:none'>
<?php echo(_('admin::base:collection: aucun fichier (minilogo, watermark ...)')) ?>

                <form method="post" name="flpdf" action="./database.php" target="???" onsubmit="return(false);" ENCTYPE="multipart/form-data">
                    <input type="hidden" name="p0"  value="<?php echo($parm["p0"]); ?>" />
                    <input type="hidden" name="sta" value="\" />
                           <input type="hidden" name="srt" value="" />
                           <input type="hidden" name="act" value="" />
                    <input type="hidden" name="tid" value="" />
                <?php
                if ($user->ACL()->has_right_on_sbas($sbas_id, 'bas_manage')) {
                    ?>
                        <input name="newLogoPdf" type="file" />
                        <input type='button' value='<?php echo(_('boutton::envoyer')); ?>' onclick='sendLogopdf();'/>
                        <br/>
            <?php echo(_('admin::base: envoyer un logo (jpeg 35px de hauteur max)')); ?>
    <?php
}
?>
                </form>
            </div>

        </div>
        <form method="post" name="manageDatabase" action="./database.php" target="???" onsubmit="return(false);">
            <input type="hidden" name="p0"  value="<?php echo($parm["p0"]) ?>" />
            <input type="hidden" name="sta" value="0" />
            <input type="hidden" name="srt" value="" />
            <input type="hidden" name="act" value="???" />
            <input type="hidden" name="tid" value="???" />
        </form>
