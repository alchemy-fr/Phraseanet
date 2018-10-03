<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use Doctrine\DBAL\DBALException;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Specification\Image as ImageSpec;

class patch_370alpha7a extends patchAbstract
{
    /** @var string */
    private $release = '3.7.0-alpha.7';

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
        return true;
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
        return ['20131118000009', '20131118000003'];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        $conn = $appbox->get_connection();

        try {
            //get all old lazaret file & transform them to LazaretFile object
            $sql = 'SELECT * FROM lazaret';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $rs = $stmt->fetchAll();
            $stmt->closeCursor();
        } catch (DBALException $e) {
            // table not found
            if ($e->getCode() == '42S02') {

            }

            return;
        }

        //order matters for foreign keys constraints
        //truncate all altered tables
        $this->truncateTable($app['orm.em'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretAttribute');
        $this->truncateTable($app['orm.em'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretCheck');
        $this->truncateTable($app['orm.em'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretFile');
        $this->truncateTable($app['orm.em'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretSession');

        $i = 0;

        foreach ($rs as $row) {
            $filePath = $app['tmp.lazaret.path'].'/'.$row['filepath'];
            if (null === $user = $this->loadUser($app['orm.em'], $row['usr_id'])) {
                continue;
            }

            if (file_exists($filePath)) {
                $spec = new ImageSpec();

                $spec->setResizeMode(ImageSpec::RESIZE_MODE_INBOUND_FIXEDRATIO);
                $spec->setDimensions(375, 275);

                $thumbPath = $app['tmp.lazaret.path'].'/'.sprintf("thumb_%s", $row['filepath']);

                try {
                    $app['media-alchemyst']->turnInto($filePath, $thumbPath, $spec);
                } catch (MediaAlchemystException $e) {

                }

                $media = $app->getMediaFromUri($filePath);

                $collection = \collection::getByBaseId($app, $row['base_id']);

                $borderFile = new \Alchemy\Phrasea\Border\File($app, $media, $collection);

                $lazaretSession = new LazaretSession();
                $lazaretSession->setUser($user);

                $lazaretFile = new LazaretFile();
                $lazaretFile->setBaseId($row['base_id']);

                if (null === $row['uuid']) {
                    $uuid = $borderFile->getUUID(true);
                    $lazaretFile->setUuid($uuid);
                } else {
                    $lazaretFile->setUuid($row['uuid']);
                }

                if (null === $row['sha256']) {
                    $sha256 = $media->getHash('sha256');
                    $lazaretFile->setSha256($sha256);
                } else {
                    $lazaretFile->setSha256($row['sha256']);
                }

                $lazaretFile->setOriginalName($row['filename']);
                $lazaretFile->setFilename($row['filepath']);
                $lazaretFile->setThumbFilename(pathinfo($thumbPath), PATHINFO_BASENAME);
                $lazaretFile->setCreated(new \DateTime($row['created_on']));
                $lazaretFile->setSession($lazaretSession);

                $app['orm.em']->persist($lazaretFile);

                if (0 === ++$i % 100) {
                    $app['orm.em']->flush();
                    $app['orm.em']->clear();
                }
            }
        }

        $app['orm.em']->flush();
        $app['orm.em']->clear();

        $stmt->closeCursor();

        return true;
    }

    private function truncateTable(\Doctrine\ORM\EntityManager $em, $className)
    {
        $query = $em->createQuery(sprintf('DELETE FROM %s', $className));
        $query->execute();
    }
}
