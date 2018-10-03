<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Fractal;

use League\Fractal\TransformerAbstract;

class CallbackTransformer extends TransformerAbstract
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(callable $callback = null)
    {
        if (null === $callback) {
            $callback = function () {
                return [];
            };
        }

        $this->callback = $callback;
    }

    public function transform()
    {
        return call_user_func_array($this->callback, func_get_args());
    }
}
