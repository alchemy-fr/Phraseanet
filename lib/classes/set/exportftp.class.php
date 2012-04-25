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
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class set_exportftp extends set_export
{

  /**
   *
   * @param Int $usr_to
   * @param String $host
   * @param String $login
   * @param String $password
   * @param Int $ssl
   * @param Int $retry
   * @param Int $passif
   * @param String $destfolder
   * @param String $makedirectory
   * @return boolean
   */
  public function export_ftp($usr_to, $host, $login, $password, $ssl, $retry, $passif, $destfolder, $makedirectory, $logfile)
  {
    $appbox  = appbox::get_instance(\bootstrap::getCore());
    $session = $appbox->get_session();
    $user_f  = User_Adapter::getInstance($session->get_usr_id(), $appbox);
    $conn    = $appbox->get_connection();

    $email_dest = '';
    if ($usr_to)
    {
      $user_t     = User_Adapter::getInstance($usr_to, $appbox);
      $email_dest = $user_t->get_email();
    }


    $text_mail_receiver = "Bonjour,\n"
      . "L'utilisateur "
      . $user_f->get_display_name() . " (login : " . $user_f->get_login() . ") "
      . "a fait un transfert FTP sur le serveur ayant comme adresse \""
      . $host . "\" avec le login \"" . $login . "\"  "
      . "et pour repertoire de destination \""
      . $destfolder . "\"\n";

    $text_mail_sender = "Bonjour,\n"
      . "Vous avez fait un export FTP  avec les caracteristiques "
      . "de connexion suivantes\n"
      . "- adresse du serveur : \"" . $host . "\"\n"
      . "- login utilisé \"" . $login . "\"\n"
      . "- repertoire de destination \"" . $destfolder . "\"\n"
      . "\n";

    $fn = "id";
    $fv = "null";
    $fn .= ",crash";
    $fv .= ",0";
    $fn .= ",nbretry";
    $fv .= ",:nbretry";
    $fn .= ",mail";
    $fv .= ",:mail";
    $fn .= ",addr";
    $fv .= ",:addr";
    $fn .= ",login";
    $fv .= ",:login";
    $fn .= ",`ssl`";
    $fv .= ",:ssl";
    $fn .= ",pwd";
    $fv .= ",:pwd";
    $fn .= ",passif";
    $fv .= ",:passif";
    $fn .= ",destfolder";
    $fv .= ",:destfolder";
    $fn .= ",sendermail";
    $fv .= ",:sendermail";
    $fn .= ",text_mail_receiver";
    $fv .= ",:text_mail_receiver";
    $fn .= ",text_mail_sender";
    $fv .= ",:text_mail_sender";
    $fn .= ",usr_id";
    $fv .= ",:usr_id";
    $fn .= ",date";
    $fv .= ", NOW()";
    $fn .= ",foldertocreate";
    $fv .= ",:foldertocreate";
    $fn .= ",logfile";
    $fv .= ",:logfile";

    $params = array(
      ':nbretry'            => (((int) $retry * 1) > 0 ? (int) $retry : 5)
      , ':mail'               => $email_dest
      , ':addr'               => $host
      , ':login'              => $login
      , ':ssl'                => ($ssl == '1' ? '1' : '0')
      , ':pwd'                => $password
      , ':passif'             => ($passif == "1" ? "1" : "0")
      , ':destfolder'         => $destfolder
      , ':sendermail'         => $user_f->get_email()
      , ':text_mail_receiver' => $text_mail_receiver
      , ':text_mail_sender'   => $text_mail_sender
      , ':usr_id'             => $session->get_usr_id()
      , ':foldertocreate'     => $makedirectory
      , ':logfile'            => (!!$logfile ? '1' : '0')
    );

    $sql  = "INSERT INTO ftp_export ($fn) VALUES ($fv)";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $stmt->closeCursor();

    $ftp_export_id = $conn->lastInsertId();

    $sql = 'INSERT INTO ftp_export_elements
            (id, ftp_export_id, base_id, record_id, subdef, filename, folder, businessfields)
            VALUES
            (null, :ftp_export_id, :base_id, :record_id, :subdef,
              :filename, :folder, :businessfields)';

    $stmt = $conn->prepare($sql);

    foreach ($this->list['files'] as $file)
    {
      foreach ($file['subdefs'] as $subdef => $properties)
      {
        $filename = $file['export_name']
          . $properties["ajout"] . '.'
          . $properties['exportExt'];

        $bfields = isset($properties['businessfields']) ? $properties['businessfields'] : null;

        $params  = array(
          ':ftp_export_id'  => $ftp_export_id
          , ':base_id'        => $file['base_id']
          , ':record_id'      => $file['record_id']
          , ':subdef'         => $subdef
          , ':filename'       => $filename
          , ':folder'         => $properties['folder']
          , ':businessfields' => $bfields
        );
        $stmt->execute($params);
      }
    }

    $stmt->closeCursor();

    return true;
  }

}
