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
     * @param array $raw
     * @return databox_field
     */
    public function create(array $raw)
    {
        return new databox_field($this->app, $this->databox, $raw);
    }

    /**
     * @param array $rows
     * @return databox_field[]
     */
    public function createMany(array $rows)
    {
        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);
        $instances = [];

        foreach ($rows as $index => $raw) {
            $instances[$index] = new databox_field($this->app, $this->databox, $raw);
        }

        file_put_contents("/tmp/phraseanet-log.txt", sprintf("%s (%d) Dt=%.4f, dt=%.4f\n", __FILE__, __LINE__, microtime(true) - (isset($GLOBALS['_t_']) ? $GLOBALS['_t_'] : ($GLOBALS['_t_'] = microtime(true))), min((isset($GLOBALS['_t0_']) ? microtime(true) - $GLOBALS['_t0_'] : 0), $GLOBALS['_t0_'] = microtime(true))), FILE_APPEND);
        return $instances;
    }
}
