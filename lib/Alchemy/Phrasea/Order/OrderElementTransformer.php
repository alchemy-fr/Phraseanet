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
use Alchemy\Phrasea\Databox\Subdef\MediaSubdefRepository;
use Alchemy\Phrasea\Media\MediaSubDefinitionUrlGenerator;
use Alchemy\Phrasea\Model\Entities\OrderElement;
use League\Fractal\ParamBag;
use League\Fractal\TransformerAbstract;

class OrderElementTransformer extends TransformerAbstract
{
    protected $availableIncludes = ['resource_links'];

    private $validParams = ['ttl'];

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
            'record' => [
                'databox_id' => $element->getSbasId($this->app),
                'record_id' => $element->getRecordId(),
            ],
        ];

        $data['status'] = 'pending';

        if (null !== $element->getOrderMaster()) {
            $data['validator_id'] = $element->getOrderMaster()->getId();
            $data['status'] = $element->getDeny() ? 'rejected' : 'accepted';
        }

        return $data;
    }

    public function includeResourceLinks(OrderElement $element, ParamBag $params = null)
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

        $generator = $this->getSubdefUrlGenerator();

        if (null === $ttl) {
            $ttl = $generator->getDefaultTTL();
        }

        $urls = $generator->generateMany($this->app->getAuthenticatedUser(), $this->findOrderableMediaSubdef($element), $ttl);
        $urls = array_map(null, array_keys($urls), array_values($urls));

        return $this->collection($urls, function (array $data) use ($ttl) {
            return [
                'name' => $data[0],
                'url' => $data[1],
                'url_ttl' => $ttl,
            ];
        });
    }

    /**
     * @param OrderElement $element
     * @return \media_subdef[]
     */
    private function findOrderableMediaSubdef(OrderElement $element)
    {
        if (false !== $element->getDeny()) {
            return [];
        }

        $databox = $this->app->findDataboxById($element->getSbasId($this->app));
        $record = $databox->get_record($element->getRecordId());

        $subdefNames = [];

        foreach ($databox->get_subdef_structure()->getSubdefGroup($record->getType()) as $databoxSubdef) {
            if ($databoxSubdef->isOrderable()) {
                $subdefNames[] = $databoxSubdef->get_name();
            }
        }

        return $this->getMediaSubDefRepository($databox->get_sbas_id())
            ->findByRecordIdsAndNames([$element->getRecordId()], $subdefNames);
    }

    /**
     * @return MediaSubDefinitionUrlGenerator
     */
    private function getSubdefUrlGenerator()
    {
        return $this->app['media_accessor.subdef_url_generator'];
    }

    /**
     * @return MediaSubdefRepository
     */
    private function getMediaSubDefRepository($databoxId)
    {
        return $this->app['provider.repo.media_subdef']->getRepositoryForDatabox($databoxId);
    }
}
