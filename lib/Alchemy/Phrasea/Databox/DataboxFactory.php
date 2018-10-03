<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
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

    /**
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param DataboxRepository $databoxRepository
     */
    public function setDataboxRepository(DataboxRepository $databoxRepository)
    {
        $this->databoxRepository = $databoxRepository;
    }

    /**
     * @param int $id
     * @param array $raw
     * @return \databox when Databox could not be retrieved from Persistence layer
     */
    public function create($id, array $raw)
    {
        return new \databox($this->app, $id, $this->databoxRepository, $raw);
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
