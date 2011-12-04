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
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
$appbox = appbox::get_instance();
$session = $appbox->get_session();


//SPECIAL ZINO
ini_set('display_errors', 'off');
ini_set('display_startup_errors', 'off');
ini_set('log_errors', 'off');
//SPECIAL ZINO
?>
<html lang="<?php echo $session->get_I18n(); ?>">
  <head>
    <META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=ISO-8859-1">
    <style>
      * {
        font-family:courier;
        font-size:14px;
      }
      H1 {
        font-size:20px;
        background-color:#e0e0e0;
        margin-top:20px;
      }
    </style>
  </head>
  <body>



    <h1/>identification</h1>

  <form action="./login/login.php" target="RESULT">
        ./login/login.php
    <input type="checkbox" name="debug" />debug
    <br/>
    <textarea name="p" cols="80" rows="6">
<?php
echo htmlentities(
        '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<parms action="OPEN_SESSION">
    <login>api</login>
    <pwd>api</pwd>
</parms>');
?>
    </textarea>
    <input type="submit" />
  </form>



  <h1/>preparer un batch</h1>

<form action="./upload/upload.php" target="RESULT_UPL">
        ./upload/upload.php
  &nbsp;&nbsp;
        ses_id : <input type="text" value="<?php echo $session->get_ses_id() ?>" name="ses_id" />
      &nbsp;&nbsp;
            usr_id : <input type="text" value="<?php echo $session->get_usr_id() ?>" name="usr_id" />
      &nbsp;&nbsp;
      <input type="checkbox" name="debug" />debug<br/>
      <textarea name="p" cols="80" rows="6">
<?php
      echo htmlentities(
              '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<parms action="PREPARE_UPLOAD">
    <base_id>2</base_id>
    <nfiles>2</nfiles>
</parms>');
?>
    </textarea>
    <input type="submit" />
  </form>



  <h1/>envoi par form</h1>

  <form enctype="multipart/form-data" action="./upload/upload.php" target="RESULT_UPL" method="POST">
          ./upload/upload.php
    &nbsp;&nbsp;
          ses_id : <input type="text" value="<?php echo $session->get_ses_id() ?>" name="ses_id" />
      &nbsp;&nbsp;
            usr_id : <input type="text" value="<?php echo $session->get_usr_id() ?>" name="usr_id" />
      &nbsp;&nbsp;
      <input type="checkbox" name="debug" />debug<br/>
            file : <input type="file" name="file" /><br/>
      <textarea name="p" cols="80" rows="10">
  <?php
      echo htmlentities(
              '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<parms action="UPLOAD">
    <upload_batch_id>???</upload_batch_id>
    <index>1</index>
    <filename></filename>
    <filesize></filesize>
    <md5></md5>
</parms>');
  ?>
    </textarea>
    <input type="submit" />
  </form>



  <h1/>envoi simul&eacute; par curl (size, md5,... calcul&eacute;s)</h1>

  <form action="./test_upl.php" target="RESULT_UPL" method="POST">
          ./test_upl.php
    &nbsp;&nbsp;
          ses_id : <input type="text" value="<?php echo $session->get_ses_id() ?>" name="ses_id" />
      &nbsp;&nbsp;
            usr_id : <input type="text" value="<?php echo $session->get_usr_id() ?>" name="usr_id" />
      <br/>
            upload_batch_id : <input type="text" value="" name="upload_batch_id" />
      &nbsp;&nbsp;
            index : <input type="text" value="1" name="index" />
      &nbsp;&nbsp;
      <input type="checkbox" name="debug" />debug<br/>
<?php
      if (($o = opendir('./testfiles')))
      {
        $nf = 0;
        while (($f = readdir($o)))
        {
          if (substr($f, -4) != '.jpg')
            continue;
          echo('<input type="radio" ' . ($nf == 0 ? 'checked' : '') . ' name="file" value="./testfiles/' . $f . '"/>testfiles/' . $f . '<br/>');
          $nf++;
        }
        closedir($o);
      }
?>
      <input type="submit" />
    </form>



    <h1/>finir un batch</h1>

    <form action="./upload/upload.php" target="RESULT_UPL">
            ./upload/upload.php
      &nbsp;&nbsp;
            ses_id : <input type="text" value="<?php echo $session->get_ses_id() ?>" name="ses_id" />
      &nbsp;&nbsp;
            usr_id : <input type="text" value="<?php echo $session->get_usr_id() ?>" name="usr_id" />
      &nbsp;&nbsp;
      <input type="checkbox" name="debug" />debug<br/>
      <textarea name="p" cols="80" rows="6">
<?php
      echo htmlentities(
              '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<parms action="END_UPLOAD">
    <upload_batch_id></upload_batch_id>
</parms>');
?>
  </textarea>
  <input type="submit" />
</form>




</body>
</html>
