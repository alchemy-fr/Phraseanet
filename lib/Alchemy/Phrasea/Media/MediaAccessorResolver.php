<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Media;

use Alchemy\Embed\Media\MediaInformation;
use Alchemy\Embed\Media\ResourceResolver;
use Alchemy\Phrasea\Controller\MediaAccessorController;
use Symfony\Component\HttpFoundation\Request;

class MediaAccessorResolver implements ResourceResolver
{
    /** @var \appbox */
    private $appbox;
    /** @var MediaAccessorController */
    private $controller;

    public function __construct(\appbox $appbox, MediaAccessorController $controller)
    {
        $this->appbox = $appbox;
        $this->controller = $controller;
    }

    public function resolve(Request $request, $routeName, array $routeParameters)
    {
        $parameters = array_intersect_key($routeParameters, [
            'token' => null,
        ]);

        list ($sbas_id, $record_id, $subdefName) = $this->controller->validateToken($parameters['token']);

        $databox = $this->appbox->get_databox($sbas_id);
        $record = $databox->get_record($record_id);
        $subdef = $record->get_subdef($subdefName);

        return new MediaInformation($subdef, $request, $routeName, $routeParameters);
    }
}
