<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Databox\Field;

use Alchemy\Phrasea\Application;
use databox;
use databox_field;

class DataboxFieldFactory
{
    /** @var Application */
    private $app;
    /** @var databox */
    private $databox;

    public function __construct(Application $app, databox $databox)
    {
        $this->app = $app;
        $this->databox = $databox;
    }

    /**
     * @param array $row
     * @return databox_field
     */
    public function create(array $row)
    {
        return new databox_field($this->app, $this->databox, $row);
    }

    /**
     * @param array $rows
     * @return databox_field[]
     */
    public function createMany(array $rows)
    {
        $instances = [];

        foreach ($rows as $index => $row) {
            $instances[$index] = new databox_field($this->app, $this->databox, $row);
        }

        return $instances;
    }
}
