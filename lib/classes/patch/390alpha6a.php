<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\FtpExport;
use Alchemy\Phrasea\Model\Entities\FtpExportElement;
use Gedmo\Timestampable\TimestampableListener;
use Doctrine\ORM\NoResultException;

class patch_390alpha6a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.6';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * {@inheritdoc}
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return ['ftp-export'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $sql = 'DELETE FROM FtpExports';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $sql = 'DELETE FROM FtpExportElements';
        $stmt = $app['phraseanet.appbox']->get_connection()->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        $conn = $app['phraseanet.appbox']->get_connection();

        $em = $app['EM'];
        $em->getEventManager()->removeEventSubscriber(new TimestampableListener());

        $sql = 'SELECT `id`, `crash`, `nbretry`, `mail`, `addr`, `ssl`,
                    `login`, `pwd`, `passif`,
                    `destfolder`, `sendermail`, `text_mail_sender`,
                    `text_mail_receiver`, `usr_id`, `date`, `foldertocreate`,
                    `logfile`
                FROM ftp_export';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $sql = 'SELECT base_id, record_id, subdef, filename, folder, error, done, businessfields
                FROM ftp_export_elements
                WHERE ftp_export_id = :export_id';
        $stmt = $conn->prepare($sql);

        $n = 0;

        foreach ($rs as $row) {
            try {
                $user = $em->getPartialReference('Phraseanet:User', $row['usr_id']);
            } catch (NoResultException $e) {
                continue;
            }

            $export = new FtpExport();
            $export
                ->setAddr($row['addr'])
                ->setCrash($row['crash'])
                ->setNbretry($row['nbretry'])
                ->setMail($row['mail'])
                ->setSsl($row['ssl'])
                ->setLogin($row['login'])
                ->setPwd($row['pwd'])
                ->setPassif($row['passif'])
                ->setDestfolder($row['destfolder'])
                ->setSendermail($row['sendermail'])
                ->setTextMailReceiver($row['text_mail_sender'])
                ->setTextMailSender($row['text_mail_reveiver'])
                ->setUser($user)
                ->setCreated(new \DateTime($row['date']))
                ->setUpdated(new \DateTime($row['date']))
                ->setFoldertocreate($row['foldertocreate'])
                ->setLogfile($row['logfile']);

            $em->persist($export);

            $stmt->execute(['export_id' => $row['id']]);
            $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($rs as $element) {
                $element = new FtpExportElement();
                $element->setBaseId($row['base_id'])
                    ->setRecordId($row['record_id'])
                    ->setBusinessfields($row['businessfields'])
                    ->setCreated(new \DateTime($row['date']))
                    ->setUpdated(new \DateTime($row['date']))
                    ->setDone(!!$row['done'])
                    ->setError(!!$row['error'])
                    ->setFilename($row['filename'])
                    ->setFolder($row['folder'])
                    ->setSubdef($row['subdef'])
                    ->setExport($export);

                $export->addElement($element);

                $em->persist($element);
            }

            $n++;

            if ($n % 200 === 0) {
                $em->flush();
                $em->clear();
            }
        }
        $stmt->closeCursor();

        $em->flush();
        $em->clear();

        $em->getEventManager()->addEventSubscriber(new TimestampableListener());

        return true;
    }
}
