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
$appbox = appbox::get_instance($Core);
$session = $appbox->get_session();

$usr_id = $session->get_usr_id();

$user = User_Adapter::getInstance($usr_id, $appbox);

phrasea::headers();
?>
<html lang="<?php echo $session->get_I18n(); ?>">
    <head>
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/common/main.css" />
        <link type="text/css" rel="stylesheet" href="/include/minify/f=skins/admin/admincolor.css" />
        <style type="text/css">
            BODY
            {
                text-align:left;
            }
            A
            { COLOR : #000000; font-weight:900; TEXT-DECORATION: none}
            A:hover
            { COLOR : #000000; font-weight:900; TEXT-DECORATION: underline}
            A:link
            { COLOR : #000000; font-weight:900; TEXT-DECORATION: none}
            A:visited
            { COLOR : #000000; font-weight:900; TEXT-DECORATION: none}
            A:active
            { COLOR : #000000; font-weight:900; TEXT-DECORATION: none}

        </style>


        <script type="text/javascript">
            function gostep3()
            {
                document.forms['importform2'].act.value = "STEP3";
                document.forms['importform2'].submit();
            }
            function gostep1()
            {
                document.forms['importform2'].act.value = "";
                document.forms['importform2'].submit();
            }

            function rloadusr()
            {
                parent.imp0rloadusr();
            }
        </script>
    </head>
    <body>

        <?php

        function read_csv($filename)
        {
            $separateur = ",";

            // pb sinon qd venant de mac
            ini_set("auto_detect_line_endings", true);
            if ($FILE = fopen($filename, "r")) {
                $test1 = fgetcsv($FILE, 1024, ",");
                rewind($FILE);
                $test2 = fgetcsv($FILE, 1024, ";");
                rewind($FILE);
                if (count($test1) == 1 || ( count($test2) > count($test1) && count($test2) < 20))
                    $separateur = ";";


                while ($ARRAY[] = fgetcsv($FILE, 1024, $separateur));
                fclose($FILE);
                array_pop($ARRAY);

                return $ARRAY;
            }
        }
        $request = http_request::getInstance();
        $parm = $request->get_parms("act", "modelToAplly", "sr");

        $conn = $appbox->get_connection();

        $models = null;

        if ($parm["act"] == "STEP2" || $parm["act"] == "STEP3") {
            $admBasid = array_keys($user->ACL()->get_granted_base(array('manage')));
            $admBasid = implode(', ', $admBasid);

            if ($parm["act"] == "STEP2") {
                $sql = "SELECT usr.usr_id,usr.usr_login
                FROM usr
                  INNER JOIN basusr
                    ON (basusr.usr_id=usr.usr_id)
                WHERE usr.model_of = :usr_id
                  AND base_id in($admBasid)
                  AND usr_login not like '(#deleted_%)'
                GROUP BY usr_id";

                $stmt = $conn->prepare($sql);
                $stmt->execute(array(':usr_id' => $usr_id));
                $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $stmt->closeCursor();
            }
        }

        $equivalenceToMysqlField['civilite'] = 'usr_sexe';
        $equivalenceToMysqlField['gender'] = 'usr_sexe';
        $equivalenceToMysqlField['usr_sexe'] = 'usr_sexe';
        $equivalenceToMysqlField['nom'] = 'usr_nom';
        $equivalenceToMysqlField['name'] = 'usr_nom';
        $equivalenceToMysqlField['last name'] = 'usr_nom';
        $equivalenceToMysqlField['last_name'] = 'usr_nom';
        $equivalenceToMysqlField['usr_nom'] = 'usr_nom';
        $equivalenceToMysqlField['first name'] = 'usr_prenom';
        $equivalenceToMysqlField['first_name'] = 'usr_prenom';
        $equivalenceToMysqlField['prenom'] = 'usr_prenom';
        $equivalenceToMysqlField['usr_prenom'] = 'usr_prenom';
        $equivalenceToMysqlField['identifiant'] = 'usr_login';
        $equivalenceToMysqlField['login'] = 'usr_login';
        $equivalenceToMysqlField['usr_login'] = 'usr_login';
        $equivalenceToMysqlField['usr_password'] = 'usr_password';
        $equivalenceToMysqlField['password'] = 'usr_password';
        $equivalenceToMysqlField['mot de passe'] = 'usr_password';
        $equivalenceToMysqlField['usr_mail'] = 'usr_mail';
        $equivalenceToMysqlField['email'] = 'usr_mail';
        $equivalenceToMysqlField['mail'] = 'usr_mail';
        $equivalenceToMysqlField['adresse'] = 'adresse';
        $equivalenceToMysqlField['adress'] = 'adresse';
        $equivalenceToMysqlField['address'] = 'adresse';
        $equivalenceToMysqlField['ville'] = 'ville';
        $equivalenceToMysqlField['city'] = 'ville';
        $equivalenceToMysqlField['zip'] = 'cpostal';
        $equivalenceToMysqlField['zipcode'] = 'cpostal';
        $equivalenceToMysqlField['zip_code'] = 'cpostal';
        $equivalenceToMysqlField['cpostal'] = 'cpostal';
        $equivalenceToMysqlField['cp'] = 'cpostal';
        $equivalenceToMysqlField['code_postal'] = 'cpostal';
        $equivalenceToMysqlField['tel'] = 'tel';
        $equivalenceToMysqlField['telephone'] = 'tel';
        $equivalenceToMysqlField['phone'] = 'tel';
        $equivalenceToMysqlField['fax'] = 'fax';
        $equivalenceToMysqlField['job'] = 'fonction';
        $equivalenceToMysqlField['fonction'] = 'fonction';
        $equivalenceToMysqlField['function'] = 'fonction';
        $equivalenceToMysqlField['societe'] = 'societe';
        $equivalenceToMysqlField['company'] = 'societe';
        $equivalenceToMysqlField['activity'] = 'activite';
        $equivalenceToMysqlField['activite'] = 'activite';
        $equivalenceToMysqlField['pays'] = 'pays';
        $equivalenceToMysqlField['country'] = 'pays';

        $equivalenceToMysqlField['ftp_active'] = 'activeFTP';
        $equivalenceToMysqlField['compte_ftp_actif'] = 'activeFTP';
        $equivalenceToMysqlField['ftpactive'] = 'activeFTP';
        $equivalenceToMysqlField['activeftp'] = 'activeFTP';
        $equivalenceToMysqlField['ftp_adress'] = 'addrFTP';
        $equivalenceToMysqlField['adresse_du_serveur_ftp'] = 'addrFTP';
        $equivalenceToMysqlField['addrftp'] = 'addrFTP';
        $equivalenceToMysqlField['ftpaddr'] = 'addrFTP';
        $equivalenceToMysqlField['loginftp'] = 'loginFTP';
        $equivalenceToMysqlField['ftplogin'] = 'loginFTP';
        $equivalenceToMysqlField['ftppwd'] = 'pwdFTP';
        $equivalenceToMysqlField['pwdftp'] = 'pwdFTP';
        $equivalenceToMysqlField['destftp'] = 'destFTP';
        $equivalenceToMysqlField['destination_folder'] = 'destFTP';
        $equivalenceToMysqlField['dossier_de_destination'] = 'destFTP';
        $equivalenceToMysqlField['passive_mode'] = 'passifFTP';
        $equivalenceToMysqlField['mode_passif'] = 'passifFTP';
        $equivalenceToMysqlField['passifftp'] = 'passifFTP';
        $equivalenceToMysqlField['retry'] = 'retryFTP';
        $equivalenceToMysqlField['nombre_de_tentative'] = 'retryFTP';
        $equivalenceToMysqlField['retryftp'] = 'retryFTP';
        $equivalenceToMysqlField['by_default__send'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['by_default_send'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['envoi_par_defaut'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['defaultftpdatasent'] = 'defaultftpdatasent';
        $equivalenceToMysqlField['prefix_creation_folder'] = 'prefixFTPfolder';
        $equivalenceToMysqlField['prefix_de_creation_de_dossier'] = 'prefixFTPfolder';
        $equivalenceToMysqlField['prefixFTPfolder'] = 'prefixFTPfolder';

        if ($parm["act"] == "STEP2" && ( ! isset($_FILES['fileusers']) || (isset($_FILES['fileusers']) && $_FILES['fileusers']['error']))) {
            print("<br /><font color=\"#FF0000\">ERROR : " . $_FILES['fileusers']['error'] . "</font><br />");
            $parm["act"] = "";
        }
        if ($parm["act"] == "STEP3") {
            $nbcreation = 0;

            $ARRAY = unserialize($parm["sr"]);
            $nblignes = sizeof($ARRAY);  // compte le nombre de ligne
            $nbcol = sizeof($ARRAY[0]);  // nombre de colonne par ligne

            for ($i = 1; $i < $nblignes; $i ++ ) { // pour chaque ligne....
                $curUser = NULL;
                for ($j = 0; $j < $nbcol; $j ++ ) {           // affiche colonne par colonne
                    if ( ! isset($equivalenceToMysqlField[$ARRAY[0][$j]]))
                        continue;
                    if ($equivalenceToMysqlField[$ARRAY[0][$j]] == "usr_sexe" && isset($ARRAY[$i][$j])) {
                        switch ($ARRAY[$i][$j]) {
                            case "Mlle":
                            case "Mlle.":
                            case "mlle":
                            case "Miss":
                            case "miss":
                            case "0":
                                $curUser[$equivalenceToMysqlField[$ARRAY[0][$j]]] = 0;
                                break;

                            case "Mme":
                            case "Madame":
                            case "Ms":
                            case "Ms.":
                            case "1":
                                $curUser[$equivalenceToMysqlField[$ARRAY[0][$j]]] = 1;
                                break;

                            case "M":
                            case "M.":
                            case "Mr":
                            case "Mr.":
                            case "Monsieur":
                            case "Mister":
                            case "2":
                                $curUser[$equivalenceToMysqlField[$ARRAY[0][$j]]] = 2;
                                break;
                        }
                    } else {
                        if (isset($ARRAY[$i][$j]))
                            $curUser[$equivalenceToMysqlField[$ARRAY[0][$j]]] = trim($ARRAY[$i][$j]);
                    }
                }


                # on va cree ici le user et ses droits
                # on verifie juste le login et le password
                if (isset($curUser['usr_login']) && trim($curUser['usr_login']) !== '' && isset($curUser['usr_password']) && trim($curUser['usr_password']) !== "") {
                    $loginNotExist = ! User_Adapter::get_usr_id_from_login($curUser['usr_login']);

                    if ($loginNotExist) {
                        $NewUser = User_Adapter::create($appbox, $curUser['usr_login'], $curUser['usr_password'], $curUser['usr_mail'], false);

                        $newid = $NewUser->get_id();

                        $admBasid = array_keys($user->ACL()->get_granted_base(array('manage')));
                        $template_user = User_Adapter::getInstance($parm["modelToAplly"], $appbox);
                        $NewUser->ACL()->apply_model($template_user, $admBasid);

                        $nbcreation ++;
                    }
                }
            }
            ?>
            <div style="position:relative;top:20px">
                <center>
            <?php echo $nbcreation ?> users was created.
                    <br>
                    <br>
                    <script type="text/javascript">
                        parent.needrefresh = true;
                    </script>

                    <a href="javascript:self.close();"  >Close</a>
                </center>
            </div>
            <?php
        } elseif ($parm["act"] == "STEP2" && isset($_FILES['fileusers'])) {

            ######### STEP 2 ##########################
            ?>
            <div style="position:relative;"><a href="javascript:void(return false);" onclick="gostep1();return(false);"><< Back</a></div>
            <small><br></small><center>
    <?php
    if ($_FILES['fileusers']['error'] == UPLOAD_ERR_OK)
        $filename = $_FILES['fileusers']["tmp_name"];
    $ARRAY = read_csv("$filename");

    // on verifie les noms de colones
    $logindefined = false;
    $pwddefined = false;
    $loginNew = NULL;
    $out = "";
    $nbusrToadd = 0;
    for ($j = 0; $j < sizeof($ARRAY[0]); $j ++ ) {           // affiche colonne par colonne
        $ARRAY[0][$j] = mb_strtolower($ARRAY[0][$j]);
        if ( ! isset($equivalenceToMysqlField[$ARRAY[0][$j]])) {
            $out .= "<br> - Row \"" . $ARRAY[0][$j] . "\" will be ignored";
        } else {
            if (($equivalenceToMysqlField[$ARRAY[0][$j]]) == 'usr_login')
                $logindefined = true;
            if (($equivalenceToMysqlField[$ARRAY[0][$j]]) == 'usr_password')
                $pwddefined = true;
        }
    }
    $outTmp = "";
    if ( ! $logindefined)
        $outTmp.= "<br> - Row \"login\" is missing, script has stopped !";
    if ( ! $pwddefined)
        $outTmp.= "<br> - Row \"password\" is missing, script has stopped !";

    if ($out != "")
        $out.="\n<br> ";


    if ( ! $logindefined || ! $pwddefined)
        $out = $outTmp;
    else {
        // On continu les tests !!
        // on verifie (pour chacun) que le login n'existe pas deja et aussi que les mots de passe sont pas vides
        $nblignes = sizeof($ARRAY);   // nombre de ligne
        $nbcol = sizeof($ARRAY[0]);  // nombre de colonne par ligne

        for ($i = 1; $i < $nblignes; $i ++ ) { // pour chaque ligne....
            $out2 = "";
            $hasVerifLogin = false;
            $hasVerifPwd = false;

            for ($j = 0; $j < $nbcol; $j ++ ) {           //  colonne par colonne
                $ARRAY[$i][$j] = trim($ARRAY[$i][$j]);
                if ( ! isset($equivalenceToMysqlField[$ARRAY[0][$j]]))
                    continue;
                // verif du login
                if (($equivalenceToMysqlField[$ARRAY[0][$j]]) == 'usr_login') {
                    $loginToadd = trim($ARRAY[$i][$j]);
                    if ($loginToadd == "")
                        $out2.= " login is empty.";
                    elseif (isset($loginNew[$loginToadd]))
                        $out2.= " Le login \"<i><b>" . $loginToadd . "</b></i>\" is already defined in the file (line " . $loginNew[$loginToadd] . ").";
                    else {
                        if (User_Adapter::get_usr_id_from_login($loginToadd)) {
                            $out2.= " Login \"<i><b>" . $loginToadd . "</b></i>\" already exists in database.";
                        } else {
                            $loginNew[$loginToadd] = ($i + 1);
                        }
                    }
                    $hasVerifLogin = true;
                }

                // verif du pwd
                if (($equivalenceToMysqlField[$ARRAY[0][$j]]) == 'usr_password') {

                    if (trim($ARRAY[$i][$j]) == "") {
                        $out2.= " password is empty .";
                    }
                    $hasVerifPwd = true;
                }

                if ($hasVerifLogin && $hasVerifPwd)
                    $j = $nbcol;

                if (($j + 1) >= $nbcol) {
                    if ($out2 != "") {
                        $out .= "<br>Line " . ( $i + 1) . " :";
                        $out .= "$out2<br>";
                    }
                    else
                        $nbusrToadd ++;
                }
            }
        }
    }

    if ($out != "") { // on affiche les erreurs
        ?>

                <div style="color:#ffffff;background-color:#FF0000;font-size:11px;width:100px;"><b>&nbsp;Warning&nbsp;</b></div>
                <div style="width:488px;height:120px; overflow:auto; border:#FF0000 1px solid;font-size:12px;padding:4px;text-align:left;">
                <?php echo $out ?>
                </div>
                <?php
            } else {

            }


            // le nombre d'ajout (non en erreur) et choix de quel "model" appliquer sur eux
            echo "<br>Number of users who's ready to be create  : $nbusrToadd";
            /* ------------- ON ALLEGE LE TABLEAU ----------------  */
            for ($i = 1; $i < sizeof($ARRAY); $i ++ )
                for ($j = 0; $j < sizeof($ARRAY[0]); $j ++ )
                    if ((isset($ARRAY[$i][$j]) && trim($ARRAY[$i][$j]) == "") || ( ! isset($equivalenceToMysqlField[$ARRAY[0][$j]])))
                        unset($ARRAY[$i][$j]);
            /* --------------------------------------------------  */
            ?>
            <br>
            <form method="post" name="importform2" action="./import.php?u=<?php echo mt_rand() ?>" onsubmit="return(false);" ENCTYPE="multipart/form-data" >
                <input type="hidden" name="act" value="" />
                <textarea style="display:none;" name="sr"><?php echo serialize($ARRAY) ?></textarea>
            <?php
            if ($nbusrToadd > 0 && count($models) > 0) {
                echo "<br>Select a model to apply on users :";
                echo " <select name=\"modelToAplly\" >";
                foreach ($models as $oneMod)
                    echo "<option value=\"" . $oneMod["usr_id"] . "\">" . $oneMod["usr_login"];
                echo " </select>";
                ?>
                    <br><br><a href="javascript:self.close();"  >Cancel</a> &nbsp;&nbsp;&nbsp;&nbsp; <a href="javascript:gostep3();">Add <?php echo $nbusrToadd ?> users</a>
                <?php
            } elseif (count($models) == 0) {
                ?>
                    <br>
                    <font color="#FF0000">you need define a model before importing a list of users.</font>
                    <br>
                    <br>
                    <a href="javascript:self.close();"  >Close</a>
            </center>
                <?php
            } else {
                ?>
            <br>
            <a href="javascript:self.close();"  >Close</a>
        </center>
                <?php
            }
            ?>
    </form>
            <?php
        } else {
            // On propose l'upload
            ?>
    <center>
        Upload a "csv" file CSV for users creation

        <br>
        <small>you can <a href="./exampleImportUsers.csv" target="_blank">download an example by clicking here</a><br />and <a href="./Fields.rtf" target="_blank">his documentation here</a></small>
        <br><br>
        <form method="post" name="importform" target="_self" action="./import.php?u=<?php echo mt_rand() ?>" onsubmit="return(false);" ENCTYPE="multipart/form-data" >
            <input type="hidden" name="act" value="STEP2" />
            User's file : <input name="fileusers" type="file" />
            <br>
            <br>
            <br>
            <br>
            <a href="javascript:void();return(false);" onclick="document.forms['importform'].submit();return(false);">Send this file</a>
        </form>
    </center>
                <?php
            }
            ?>
</body>
</html>
