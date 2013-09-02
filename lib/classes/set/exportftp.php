<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Entities\FtpExport;
use Entities\FtpExportElement;

class set_exportftp extends set_export
{
    /**
     *
     * @param integer $usr_to
     * @param String  $host
     * @param String  $login
     * @param String  $password
     * @param integer $ssl
     * @param integer $retry
     * @param integer $passif
     * @param String  $destfolder
     * @param String  $makedirectory
     * @param String  $logfile
     *
     * @return boolean
     */
    public function export_ftp($usr_to, $host, $login, $password, $ssl, $retry, $passif, $destfolder, $makedirectory, $logfile)
    {
        $conn = $this->app['phraseanet.appbox']->get_connection();

        $email_dest = '';
        if ($usr_to) {
            $user_t = User_Adapter::getInstance($usr_to, $this->app);
            $email_dest = $user_t->get_email();
        }

        $text_mail_receiver = "Bonjour,\n"
            . "L'utilisateur "
            . $this->app['authentication']->getUser()->get_display_name() . " (login : " . $this->app['authentication']->getUser()->get_login() . ") "
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

        $export = new FtpExport();
        $export->setNbretry(((int) $retry * 1) > 0 ? (int) $retry : 5)
            ->setMail($email_dest)
            ->setLogfile($logfile)
            ->setFoldertocreate($makedirectory)
            ->setUser($this->app['authentication']->getUser())
            ->setTextMailSender($text_mail_sender)
            ->setTextMailReceiver($text_mail_receiver)
            ->setSendermail($this->app['authentication']->getUser()->get_email())
            ->setDestfolder($destfolder)
            ->setPassif($passif == '1')
            ->setPwd($password)
            ->setSsl($ssl == '1')
            ->setLogin($login)
            ->setAddr($host);

        $this->app['EM']->persist($export);

        foreach ($this->list['files'] as $file) {
            foreach ($file['subdefs'] as $subdef => $properties) {
                $filename = $file['export_name'] . $properties["ajout"] . '.' . $properties['exportExt'];
                $bfields = isset($properties['businessfields']) ? $properties['businessfields'] : null;

                $element = new FtpExportElement();
                $element->setBaseId($file['base_id'])
                    ->setBusinessfields($bfields)
                    ->setExport($export)
                    ->setFilename($filename)
                    ->setFolder($properties['folder'])
                    ->setRecordId($file['record_id'])
                    ->setSubdef($subdef);
                $export->addElement($element);

                $this->app['EM']->persist($element);
            }
        }

        $this->app['EM']->flush();

        return true;
    }
}
