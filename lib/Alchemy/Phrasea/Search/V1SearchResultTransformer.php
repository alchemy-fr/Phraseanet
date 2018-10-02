<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use League\Fractal\TransformerAbstract;

class V1SearchResultTransformer extends V1SearchTransformer
{
    /**
     * @var TransformerAbstract
     */
    private $transformer;

    public function __construct(TransformerAbstract $transformer)
    {
        $this->transformer = $transformer;
    }

    public function includeResults(SearchResultView $resultView)
    {
        return $this->item($resultView, $this->transformer);
    }
}
