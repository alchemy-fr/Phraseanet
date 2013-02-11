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
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Specification\Image as ImageSpec;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_370a7 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.0a7';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    public function apply(base $appbox, Application $app)
    {
        $conn = $appbox->get_connection();

        try {
            //get all old lazaret file & transform them to \Entities\LazaretFile object
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
        $this->truncateTable($app['EM'], 'Entities\\LazaretAttribute');
        $this->truncateTable($app['EM'], 'Entities\\LazaretCheck');
        $this->truncateTable($app['EM'], 'Entities\\LazaretFile');
        $this->truncateTable($app['EM'], 'Entities\\LazaretSession');

        // suspend auto-commit
        $app['EM']->getConnection()->beginTransaction();

        try {
            foreach ($rs as $row) {

                $filePath = $app['phraseanet.registry']->get('GV_RootPath') . 'tmp/lazaret/' . $row['filepath'];

                if (file_exists($filePath)) {

                    $spec = new ImageSpec();

                    $spec->setResizeMode(ImageSpec::RESIZE_MODE_INBOUND_FIXEDRATIO);
                    $spec->setDimensions(375, 275);

                    $thumbPath = $app['phraseanet.registry']->get('GV_RootPath') . 'tmp/lazaret/' . sprintf("thumb_%s", $row['filepath']);

                    try {
                        $app['media-alchemyst']
                            ->open($filePath)
                            ->turnInto($thumbPath, $spec)
                            ->close();
                    } catch (MediaAlchemystException $e) {

                    }

                    $media = $app['mediavorus']->guess($filePath);

                    $collection = \collection::get_from_base_id($app, $row['base_id']);

                    $borderFile = new \Alchemy\Phrasea\Border\File($app, $media, $collection);

                    $lazaretSession = new \Entities\LazaretSession();
                    $lazaretSession->setUsrId($row['usr_id']);

                    $lazaretFile = new \Entities\LazaretFile();
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
                }
            }

            $app['EM']->flush();
        } catch (\Exception $e) {
            $app['EM']->getConnection()->rollback();
            $app['EM']->close();
        }

        $stmt->closeCursor();

        return;
    }

    private function truncateTable(\Doctrine\ORM\EntityManager $em, $className)
    {
        $query = $em->createQuery(sprintf('DELETE FROM %s', $className));
        $query->execute();
    }
}
