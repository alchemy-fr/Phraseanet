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
/* @var $Core \Alchemy\Phrasea\Core */
$Core = require_once __DIR__ . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "act", "p0", "send"
);

$sbas_id = (int) $parm['p0'];
$databox = $appbox->get_databox($sbas_id);
if (is_null($parm['p0']))
    phrasea::headers(400);

$user = User_Adapter::getInstance($session->get_usr_id(), $appbox);
if ( ! $user->ACL()->has_right_on_sbas($parm['p0'], 'bas_modify_struct')) {
    phrasea::headers(403);
}

phrasea::headers();
?>

<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />

<?php
$update = false;
echo "<H1>", sprintf(_('admin::base: reglage des ordres des collection de la base %s'), phrasea::sbas_names($parm['p0'])), "</h1>";

if ($parm["act"] == "APPLY") {
    $newOrder = NULL;
    $change = "<change>" . $parm["send"] . "</change>";
    $xml = simplexml_load_string($change);
    foreach ($xml->children() as $name => $val) {
        $nodename = (string) $name;
        $nodeval = (string) $val;
        if (substr($nodename, 0, 3) == "ord") {
            $idx = substr($nodename, 3) * 10;
            $newOrder[$idx] = $nodeval;
        }
    }
    foreach ($newOrder as $ord => $base_id) {
        $collection = collection::get_from_base_id($base_id);
        $appbox->set_collection_order($collection, $ord);
        unset($collection);
        $update = true;
    }
}
else
    echo "<br><br>";
?>

        <script type="text/javascript">
            function activeButtons()
            {
                if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1)
                {
                    if(document.getElementById("coll_ord").selectedIndex==0)
                        document.getElementById("upbutton").disabled = true;
                    else
                        document.getElementById("upbutton").disabled = false;

                    if( (document.getElementById("coll_ord").selectedIndex+1)==document.getElementById("coll_ord").length)
                        document.getElementById("downbutton").disabled = true;
                    else
                        document.getElementById("downbutton").disabled = false;
                }
            }
            function upcoll()
            {
                if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1 )
                {
                    var old_idx   = document.getElementById("coll_ord").selectedIndex;
                    var old_value = document.getElementById("coll_ord")[old_idx].value;
                    var old_html  = document.getElementById("coll_ord")[old_idx].innerHTML;

                    var new_idx   = old_idx-1;

                    document.getElementById("coll_ord")[old_idx].value     = document.getElementById("coll_ord")[new_idx].value;
                    document.getElementById("coll_ord")[old_idx].innerHTML = document.getElementById("coll_ord")[new_idx].innerHTML;

                    document.getElementById("coll_ord")[new_idx].value     = old_value;
                    document.getElementById("coll_ord")[new_idx].innerHTML = old_html;

                    document.getElementById("coll_ord").selectedIndex = new_idx;
                    activeButtons();
                }
            }

            function downcoll()
            {
                if( document.getElementById("coll_ord")!=null && document.getElementById("coll_ord").selectedIndex!=-1 )
                {
                    var old_idx   = document.getElementById("coll_ord").selectedIndex;
                    var old_value = document.getElementById("coll_ord")[old_idx].value;
                    var old_html  = document.getElementById("coll_ord")[old_idx].innerHTML;

                    var new_idx   = old_idx+1;

                    document.getElementById("coll_ord")[old_idx].value     = document.getElementById("coll_ord")[new_idx].value;
                    document.getElementById("coll_ord")[old_idx].innerHTML = document.getElementById("coll_ord")[new_idx].innerHTML;

                    document.getElementById("coll_ord")[new_idx].value     = old_value;
                    document.getElementById("coll_ord")[new_idx].innerHTML = old_html;

                    document.getElementById("coll_ord").selectedIndex = new_idx;
                    activeButtons();
                }
            }
            function applychange()
            {
                var send = "";
                if( document.getElementById("coll_ord")!=null )
                {
                    for(i=0; i<document.getElementById("coll_ord").length;i++)
                    {
                        send += "<ord" + i + ">" + document.getElementById("coll_ord")[i].value + "</ord" + i + ">";
                    }
                }
                document.forms["formcollorder"].act.value = "APPLY";
                document.forms["formcollorder"].send.value = send;
                document.forms["formcollorder"].submit();
            }
            function alphaOrder()
            {
                if( document.getElementById("coll_ord")!=null )
                {
                    document.getElementById("coll_ord").selectedIndex =-1 ;
<?php
$temp = array();
foreach ($databox->get_collections() as $collection) {
    $temp[$collection->get_base_id()] = $collection->get_name();
}
natcasesort($temp);
$i = 0;
foreach ($temp as $base_id => $name) {
    echo "\t\tdocument.getElementById(\"coll_ord\")[" . $i . "].value     = \"" . $base_id . "\";\n";
    echo "\t\tdocument.getElementById(\"coll_ord\")[" . $i . "].innerHTML =  \"" . $name . "\";\n";
    $i ++;
}
?>
            }
        }
        </script>
    </head>
    <body>
<?php
if ($update) {
    ?>
            <span style="color:#00BB00"><?php echo _('admin::base: mise a jour de l\'ordre des collections OK'); ?></span>
            <script type="text/javascript">

                parent.reloadTree('base:<?php echo $parm['p0']; ?>');
            </script>
    <?php
}
?>
        <table style="position:relative; left:10px;">
            <tr>
                <td>
                    <select size=16 name="coll_ord" id="coll_ord" style="width:140px" onclick="activeButtons();">
<?php
foreach ($databox->get_collections() as $collection) {
    echo "\t\t\t\t<option value='" . $collection->get_base_id() . "' >" . $collection->get_name() . "\n";
}
?>
                    </select>
                </td>
                <td>
                    <input type="submit" value="<?php echo _('admin::base:collorder: monter') ?>" disabled style="width:120px" onclick="upcoll();"   id="upbutton"   name="upbutton">
                    <br>
                    <input type="submit" value="<?php echo _('admin::base:collorder: descendre') ?>" disabled style="width:120px" onclick="downcoll();" id="downbutton" name="downbutton">
                    <br>
                    <br>
            <center><a href="javascript:void();" onclick="alphaOrder();return(false);" style="color:#000000; text-decoration:none" ><b><?php echo _('admin::base:collorder: reinitialiser en ordre alphabetique') ?></b></a></center>
        </td>
    </tr>

    <tr>
        <td colspan="2" style="height:20px" />
    </tr>
    <tr>
        <td colspan="2" style="text-align:center"><a href="javascript:void();" onclick="applychange();return(false);" style="color:#000000; text-decoration:none" ><b><?php echo _('boutton::valider') ?></b></a></td>
    </tr>
</table>
<form method="post" name="formcollorder" id="formcollorder" action="./collorder.php" onsubmit="return(false);">
    <input type="hidden" name="act" value="" />
    <input type="hidden" name="send" value="" />
    <input type="hidden" name="p0" value="<?php echo $parm["p0"] ?>" />
</form>
</body>
</html>
