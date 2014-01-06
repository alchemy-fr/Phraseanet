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
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Specification\Image as ImageSpec;

class patch_370alpha7a implements patchInterface
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
        return ['lazaret'];
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
        } catch (\PDOException $e) {
            // table not found
            if ($e->getCode() == '42S02') {

            }

            return;
        }

        //order matters for foreign keys constraints
        //truncate all altered tables
        $this->truncateTable($app['EM'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretAttribute');
        $this->truncateTable($app['EM'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretCheck');
        $this->truncateTable($app['EM'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretFile');
        $this->truncateTable($app['EM'], 'Alchemy\\Phrasea\\Model\\Entities\\LazaretSession');

        $i = 0;

        foreach ($rs as $row) {

            $filePath = $app['root.path'] . '/tmp/lazaret/' . $row['filepath'];

            if (file_exists($filePath)) {

                $spec = new ImageSpec();

                $spec->setResizeMode(ImageSpec::RESIZE_MODE_INBOUND_FIXEDRATIO);
                $spec->setDimensions(375, 275);

                $thumbPath = $app['root.path'] . '/tmp/lazaret/' . sprintf("thumb_%s", $row['filepath']);

                try {
                    $app['media-alchemyst']->turnInto($filePath, $thumbPath, $spec);
                } catch (MediaAlchemystException $e) {

                }

                $media = $app['mediavorus']->guess($filePath);

                $collection = \collection::get_from_base_id($app, $row['base_id']);

                $borderFile = new \Alchemy\Phrasea\Border\File($app, $media, $collection);

                $lazaretSession = new LazaretSession();
                $lazaretSession->setUsrId($row['usr_id']);

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

                $app['EM']->persist($lazaretFile);

                if (0 === ++$i % 100) {
                    $app['EM']->flush();
                    $app['EM']->clear();
                }
            }
        }

        $app['EM']->flush();
        $app['EM']->clear();

        $stmt->closeCursor();

        return true;
    }

    private function truncateTable(\Doctrine\ORM\EntityManager $em, $className)
    {
        $query = $em->createQuery(sprintf('DELETE FROM %s', $className));
        $query->execute();
    }
}
