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

    public function transform(OrderElementView $model)
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

    public function includeResourceLinks(OrderElementView $model, ParamBag $params = null)
    {
        $parameterArray = $this->extractParamBagValues($params);
        $usedParams = array_keys(array_filter($parameterArray));

        if (array_diff($usedParams, $this->validParams)) {
            throw new \RuntimeException(sprintf(
                'Invalid param(s): "%s". Valid param(s): "%s"',
                implode(', ', $usedParams),
                implode(', ', $this->validParams)
            ));
        }

        list ($ttl) = $parameterArray['ttl'];

        if (null === $ttl) {
            $ttl = $this->urlGenerator->getDefaultTTL();
        }

        $subdefs = $model->getOrderableMediaSubdefs();
        $urls = $this->urlGenerator->generateMany($model->getAuthenticatedUser(), $subdefs, $ttl);

        $data = array_map(null, $subdefs, $urls);

        return $this->collection($data, function (array $data) use ($ttl) {
            /** @var \media_subdef $subdef */
            list($subdef, $url) = $data;

            return [
                'name' => $subdef->get_name(),
                'url' => $url,
                'url_ttl' => $ttl,
            ];
        });
    }

    /**
     * @param ParamBag|null $params
     * @return array
     */
    private function extractParamBagValues(ParamBag $params = null)
    {
        $array = array_fill_keys($this->validParams, null);

        if ($params) {
            array_walk($array, function (&$value, $key) use ($params) {
                $value = $params[$key];
            });
        }

        return $array;
    }
}
