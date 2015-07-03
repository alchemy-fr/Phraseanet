<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Application;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DataboxFactory
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param int   $id
     * @param array $raw
     * @throws NotFoundHttpException when Databox could not be retrieved from Persistence layer
     * @return \databox
     */
    public function create($id, array $raw)
    {
        return new \databox($this->app, $id, $raw);
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
            $databoxes[$id] = new \databox($this->app, $id, $raw);
        }

        return $databoxes;
    }
}
