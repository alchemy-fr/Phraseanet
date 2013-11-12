<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\FtpCredential;

class patch_390alpha1a implements patchInterface
{
    /** @var string */
    private $release = '3.9.0-alpha.1';

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
    public function apply(base $appbox, Application $app)
    {
        $version = $app['doctrine-migration.configuration']->getVersion($this->release . 'a');
        $version->execute('up');

        $conn = $app['phraseanet.appbox']->get_connection();
        $sql = 'SELECT usr_id, activeFTP, addrFTP, loginFTP,
                retryFTP, passifFTP, pwdFTP, destFTP, prefixFTPfolder
                FROM usr';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        $n = 0;
        $em = $app['EM'];

        foreach ($rs as $row) {
            $credential = new FtpCredential();
            $credential->setActive($row['activeFTP']);
            $credential->setAddress($row['addrFTP']);
            $credential->setLogin($row['loginFTP']);
            $credential->setMaxRetry((Integer) $row['retryFTP']);
            $credential->setPassive($row['passifFTP']);
            $credential->setPassword($row['pwdFTP']);
            $credential->setReceptionFolder($row['destFTP']);
            $credential->setRepositoryPrefixName($row['prefixFTPfolder']);
            $credential->setUsrId($row['usr_id']);

            $em->persist($credential);

            $n++;

            if ($n % 100 === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();

        return true;
    }
}
