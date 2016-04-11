<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Order;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class OrderElementTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['resource_links'];

    private $validParams = ['ttl'];

    /**
     * @var MediaSubDefinitionUrlGenerator
     */
    private $urlGenerator;

    public function __construct(MediaSubDefinitionUrlGenerator $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function transform(OrderElementViewModel $model)
    {
        $element = $model->getElement();
        $record = $model->getRecordReference();

        $data = [
            'id' => $element->getId(),
            'record' => [
                'databox_id' => $record->getDataboxId(),
                'record_id' => $record->getRecordId(),
            ],
        ];

        $data['status'] = 'pending';

        if (null !== $element->getOrderMaster()) {
            $data['validator_id'] = $element->getOrderMaster()->getId();
            $data['status'] = $element->getDeny() ? 'rejected' : 'accepted';
        }

        return $data;
    }

    public function includeResourceLinks(OrderElementViewModel $model, ParamBag $params = null)
    {
        $ttl = null;

        if ($params) {
            $usedParams = array_keys(iterator_to_array($params));

            if (array_diff($usedParams, $this->validParams)) {
                throw new \RuntimeException(sprintf(
                    'Invalid param(s): "%s". Valid param(s): "%s"',
                    implode(', ', $usedParams),
                    implode(', ', $this->validParams)
                ));
            }

            list ($ttl) = $params->get('ttl');
        }

        if (null === $ttl) {
            $ttl = $this->urlGenerator->getDefaultTTL();
        }

        $urls = $this->urlGenerator->generateMany($model->getAuthenticatedUser(), $model->getOrderableMediaSubdefs(), $ttl);
        $urls = array_map(null, array_keys($urls), array_values($urls));

        return $this->collection($urls, function (array $data) use ($ttl) {
            return [
                'name' => $data[0],
                'url' => $data[1],
                'url_ttl' => $ttl,
            ];
        });
    }
}
