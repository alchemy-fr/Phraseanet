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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MediaAccessorResolver implements ResourceResolver
{
    /** @var \appbox */
    private $appbox;
    /** @var MediaAccessorController */
    private $controller;
    /** @var UrlGeneratorInterface */
    private $urlGenerator;

    public function __construct(\appbox $appbox, UrlGeneratorInterface $urlGenerator, MediaAccessorController $controller)
    {
        $this->appbox = $appbox;
        $this->urlGenerator = $urlGenerator;
        $this->controller = $controller;
    }

    public function resolve(Request $request, $routeName, array $routeParameters)
    {
        $parameters = array_replace(
            $request->query->all(),
            array_intersect_key($routeParameters, [
                'token' => null,
            ])
        );

        list ($sbas_id, $record_id, $subdefName) = $this->controller->validateToken($parameters['token']);

        $databox = $this->appbox->get_databox($sbas_id);
        $record = $databox->get_record($record_id);
        $subdef = $record->get_subdef($subdefName);

        $urlGenerator = $this->urlGenerator;
        $url = $urlGenerator->generate($routeName, $parameters, $urlGenerator::ABSOLUTE_URL);
        $embedUrl = $urlGenerator->generate('alchemy_embed_view', ['url' => $url], $urlGenerator::ABSOLUTE_URL);

        return new MediaInformation($subdef, $url, $embedUrl);
    }
}
