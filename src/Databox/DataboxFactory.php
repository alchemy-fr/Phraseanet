<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace App\Databox;

use Alchemy\Phrasea\Application;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataboxFactory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var DataboxRepository
     */
    private $databoxRepository;

//    /**
//     * @param Application $app
//     */
//    public function __construct(Application $app)
//    {
//        $this->app = $app;
//    }

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

    }


    /**
     * @param DbalDataboxRepository $databoxRepository
     */
    public function setDataboxRepository(\App\Databox\DbalDataboxRepository $databoxRepository)
    {
        $this->databoxRepository = $databoxRepository;
    }

    /**
     * @param int $id
     * @param array $raw
     * @return \App\Utils\databox when Databox could not be retrieved from Persistence layer
     */
    public function create($id, array $raw)
    {
        //return new \App\Utils\databox($this->app, $id, $this->databoxRepository, $raw);
        return new \App\Utils\databox($id, $raw, $this->container);
    }

    /**
     * @param array $rows
     * @throws NotFoundHttpException when Databox could not be retrieved from Persistence layer
     * @return \databox[]
     */
    public function createMany(array $rows)
    {
        $databoxes = [];

        foreach ($rows as $id => $raw) {
            $databoxes[$id] = new \databox($this->app, $id, $this->databoxRepository, $raw);
        }

        return $databoxes;
    }
}
