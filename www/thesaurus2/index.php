<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */

require_once __DIR__ . "/../../vendor/autoload.php";
$app = new Application();

$request = http_request::getInstance();
$parm = $request->get_parms(
    "bas", "res"
);

$conn = $app['phraseanet.appbox']->get_connection();

phrasea::headers();

// on liste les bases dont on peut éditer le thésaurus
// todo : ajouter 'bas_edit_thesaurus' dans sbasusr. pour l'instant on simule avec bas_edit_thesaurus=bas_bas_modify_struct
$sql = "SELECT
 sbas.sbas_id,

 (sbasusr.bas_manage) AS bas_manage,
 (sbasusr.bas_modify_struct) AS bas_modify_struct,
 (sbasusr.bas_modif_th) AS bas_edit_thesaurus
FROM
 (usr INNER JOIN sbasusr ON usr.usr_id = :usr_id AND usr.usr_id=sbasusr.usr_id AND model_of=0)
 INNER JOIN sbas ON sbas.sbas_id=sbasusr.sbas_id
HAVING bas_edit_thesaurus>0
ORDER BY sbas.ord";
?>
<html lang="<?php echo $app['locale.I18n']; ?>">
    <head>
        <meta http-equiv="X-UA-Compatible" content="chrome=1">
        <title><?php echo $app['phraseanet.registry']->get('GV_homeTitle'); ?> - <?php echo p4string::MakeString(_('phraseanet:: thesaurus')) ?></title>

        <link rel="shortcut icon" type="image/x-icon" href="/thesaurus2/favicon.ico">
        <link REL="stylesheet" TYPE="text/css" HREF="./thesaurus.css?u=<?php echo mt_rand() ?>" />


    </head>

    <body onload="ckok();">
        <br/>
        <br/>
        <br/>
    <center>
<?php
$select_bases = "";
$nbases = 0;
$last_base = null;
$usr_id = $app['phraseanet.user']->get_id();

$stmt = $conn->prepare($sql);
$stmt->execute(array(':usr_id' => $app['phraseanet.user']->get_id()));
$rs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->closeCursor();

foreach ($rs as $row) {
    try {
        $connbas = connection::getPDOConnection($app, $row['sbas_id']);
    } catch (Exception $e) {
        continue;
    }
    $name = phrasea::sbas_names($row['sbas_id'], $app);
    $select_bases .= "<option value=\"" . $row["sbas_id"] . "\">" . $name . "</option>\n";
    $last_base = array("sbid" => $row["sbas_id"], "name" => $name);
    $nbases ++;
}

if ($nbases > 0) {
    ?>

            <form name="fBase" action="./thesaurus.php" method="post">
                <input type="hidden" name="res" value="<?php echo $parm["res"] ?>" />
                <input type="hidden" name="uid" value="<?php echo $usr_id ?>" />
            <?php echo p4string::MakeString(_('thesaurus:: Editer le thesaurus')) ?>
            <?php
            if ($nbases == 1) {
                printf("\t<input type=\"hidden\" name=\"bid\" value=\"%s\"><b>%s</b><br/>\n", $last_base["sbid"], $last_base["name"]);
                ?>
                    <script type="text/javascript">
                        function ckok()
                        {
                            ck = false;
                            fl = document.getElementsByName("piv");
                            for(i=0; !ck && i<fl.length; i++)
                                ck = fl[i].checked;
                            document.getElementById("button_ok").disabled = !ck;
                        }
                    </script>
        <?php
    } else {
        ?>
                    <select name="bid" onchange="ckok();return(true);">
                        <option value=""><?php echo p4string::MakeString(_('phraseanet:: choisir')) /* Editer le thesaurus de la base : */ ?></option>
        <?php echo $select_bases ?>
                    </select>
                    <?php ?>
                    <br/>
                    <script type="text/javascript">
                        function ckok()
                        {
                            ck = false;
                            fl = document.getElementsByName("piv");
                            for(i=0; !ck && i<fl.length; i++)
                                ck = fl[i].checked;
                            ck &= document.forms[0].bid.selectedIndex > 0;
                            document.getElementById("button_ok").disabled = !ck;
                        }
                    </script>

                    <br/>
                    <table>
        <?php
    }

    $nf = 0;
    foreach (Application::getAvailableLanguages() as $lng_code => $lng) {
        $lng_code = explode('_', $lng_code);
        $lng_code = $lng_code[0];
        printf("<tr><td>%s</td>", $nf == 0 ? p4string::MakeString(_('thesaurus:: langue pivot')) /* Langue pivot : */ : "");
        print("<td style=\"text-align:left\"><input type='radio' onclick=\"ckok();return(true);\" value='$lng_code' name='piv'><img src='/skins/lng/" . $lng_code . "_flag_18.gif' />&nbsp;(" . $lng_code . ")</td></tr>\n");
        $nf ++;
    }
    ?>
                </table>
                <br/>
                <br/>
                <input id="button_ok" type="submit" style="width:80px;" value="<?php echo p4string::MakeString(_('boutton::valider')) ?>" /><br/>
            </form>
                    <?php
                } else {
                    ?>
                    <?php echo p4string::MakeString(_('thesaurus:: Vous n\'avez acces a aucune base')) ?>
            <script type="text/javascript">
                function ckok()
                {
                }
            </script>
    <?php
}
?>
    </center>
</body>
</html>
