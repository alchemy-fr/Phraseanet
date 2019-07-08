<?php
/**
 * Created by PhpStorm.
 * User: macjy
 * Date: 2019-07-05
 * Time: 16:40
 */

namespace Alchemy\Phrasea\Core\Event;

use Alchemy\Phrasea\Application;


interface WorkerableEventInterface
{
    /**
     * info : similar from "serialize" php magic method
     *
     * Should return a "scalars" representation of the event, that is
     * - boolean, integer, float, string,
     * - or (recursive) array of thoses.
     *
     * eg. :
     *        return [
     *          'foo'    => $this->foo,
     *          'bar_id' => $this->bar->getId()
     *        ];
     *
     * @return mixed
     */
    function getAsScalars();

    /**
     * info : similar from "unserialize" php magic method
     *
     * Should restore the object complex properties from the "scalars" representation
     *
     * eg. :
     *        $this->foo = $data['foo'];
     *        $this->bar = $app->getBar($data['bar_id']);
     *
     * @param mixed $data
     * @param Application $app
     * @return void
     */
    function restoreFromScalars($data, Application $app);
}