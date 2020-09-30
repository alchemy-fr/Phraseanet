<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\ValidationData;
use Alchemy\Phrasea\Model\Repositories\AuthFailureRepository;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Assert\Assertion;
use Doctrine\Common\Persistence\ObjectManager;

class AuthFailureManipulator
{
    /** @var Application */
    private $app;
    /** @var AuthFailureRepository */
    private $repository;
    /** @var ObjectManager */
    private $manager;

    public function __construct(Application $app, AuthFailureRepository $repository, ObjectManager $manager)
    {
        $this->app = $app;
        $this->repository = $repository;
        $this->manager = $manager;
    }


      /**

     * Cleanup any needed table abroad TRUNCATE SQL function

     *

     * @param string $className

     * @param ObjectManager $manager

     * @param AuthFailureRepository $repository

     */

    public function truncateTable (string $className) 
    {

        $cmd = $this->manager->getClassMetadata($className);

        $connection = $this->manager->getConnection();

        $connection->beginTransaction();

        if ($this->repository->count() > 10)
        {

            try {

                $connection->query('SET FOREIGN_KEY_CHECKS=0');

                $connection->query('TRUNCATE TABLE '.$cmd->getTableName());

                $connection->query('SET FOREIGN_KEY_CHECKS=1');

                $connection->commit();

                $this->manager->flush();

                return $className . " successfully truncated";

            } catch (\Exception $e) {

                try {

                    fwrite(STDERR, print_r('Can\'t truncate table ' . $cmd->getTableName() . '. Reason: ' . $e->getMessage(), TRUE));

                    $connection->rollback();

                    return false;

                } catch (ConnectionException $connectionException) {

                    fwrite(STDERR, print_r('Can\'t rollback truncating table ' . $cmd->getTableName() . '. Reason: ' . $connectionException->getMessage(), TRUE));

                    return false;
                }
            }
        }
    }
}
