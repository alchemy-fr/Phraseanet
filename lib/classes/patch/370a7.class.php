<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use MediaAlchemyst\Exception\Exception as MediaAlchemystException;
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
    private $release = '3.7.0.0.a7';

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

    public function apply(base &$appbox)
    {
        $Core = \bootstrap::getCore();

        $em = $Core->getEntityManager();

        //order matters for foreign keys constraints
        //truncate all altered tables
        $this->truncateTable($em, 'Entities\\LazaretAttribute');
        $this->truncateTable($em, 'Entities\\LazaretCheck');
        $this->truncateTable($em, 'Entities\\LazaretFile');
        $this->truncateTable($em, 'Entities\\LazaretSession');

        $conn = $appbox->get_connection();

        //get all old lazaret file & transform them to \Entities\LazaretFile object
        $sql = 'SELECT * FROM lazaret';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $rs = $stmt->fetchAll();

        // suspend auto-commit
        $em->getConnection()->beginTransaction();

        $transfertLazaretFile = $success = false;

        if ($stmt->rowCount() > 0) {
            try {
                foreach ($rs as $row) {

                    $filePath = __DIR__ . '/../../../tmp/lazaret/' . $row['filepath'];

                    if (file_exists($filePath)) {

                        $spec = new ImageSpec();

                        $spec->setResizeMode(ImageSpec::RESIZE_MODE_INBOUND_FIXEDRATIO);
                        $spec->setDimensions(375, 275);

                        $thumbPass = sprintf("thumb_%s", $filePath);

                        try {
                            $Core['media-alchemyst']
                                ->open($filePath)
                                ->turnInto($thumbPass, $spec)
                                ->close();
                        } catch (MediaAlchemystException $e) {

                        }

                        $media = $Core['mediavorus']->guess(new \SplFileInfo($filePath));

                        $collection = \collection::get_from_base_id($row['base_id']);

                        $borderFile = new \Alchemy\Phrasea\Border\File($media, $collection);

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
                        $lazaretFile->setPathname($row['filepath']);
                        $lazaretFile->setThumbPathname($thumbPass);
                        $lazaretFile->setCreated(new \DateTime($row['created_on']));
                        $lazaretFile->setSession($lazaretSession);

                        $em->persist($lazaretFile);
                    }
                }

                $em->flush();

                $transfertLazaretFile = true;
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $em->close();
            }

            if ($transfertLazaretFile) {
                try {
                    $sql = 'DROP TABLE lazaret';
                    $stmt = $conn->prepare($sql);
                    $stmt->execute();

                    //success deletion, commit all changes
                    $em->getConnection()->commit();

                    $success = true;
                } catch (\PDOException $e) {
                    $em->getConnection()->rollback();
                    $em->close();
                }
            }
        } else {
            $success = true;
        }

        $stmt->closeCursor();

        if ( ! $success) {
            throw new \RuntimeException(sprintf("Patch %s failed", __CLASS__));
        }

        return;
    }

    private function truncateTable(\Doctrine\ORM\EntityManager $em, $className)
    {
        $query = $em->createQuery(sprintf('DELETE FROM %s', $className));
        $query->execute();
    }
}

