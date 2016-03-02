<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use League\Fractal\TransformerAbstract;

class OrderElementTransformer extends TransformerAbstract
{
    /**
     * @var Application
     */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function transform(OrderElement $element)
    {
        $data = [
            'id' => $element->getId(),
            'record_id' => [
                'databox_id' => $element->getSbasId($this->app),
                'record_id' => $element->getRecordId(),
            ],
        ];

        if (null !== $element->getOrderMaster()) {
            $data['validator_id'] = $element->getOrderMaster()->getId();
            $data['status'] = $element->getDeny() ? 'rejected' : 'accepted';
        }

        return $data;
    }
}
