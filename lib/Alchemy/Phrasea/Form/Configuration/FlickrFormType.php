<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Configuration;

use Alchemy\Phrasea\Model\Entities\Task;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Validator\Constraints as Assert;

class FlickrFormType extends AbstractType
{
    /** @var UrlGenerator */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $create_api_flickr = '<a href="https://secure.flickr.com/services/apps/create/" target="_blank">https://secure.flickr.com/services/apps/create/</a>';

        try {
            $flickr_callback = $this->generator->generate('prod_bridge_callback', array('api_name' => 'flickr'), UrlGenerator::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $flickr_callback = null;
        }


        $builder->add('GV_flickr_api', 'checkbox', array(
            'label'        => _('Use Flickr API'),
            'data'         => false,
            'help_message' => sprintf(_('Create API account at %s, then use %s as callback URL value'), $create_api_flickr, $flickr_callback),
        ));


        $builder->add('GV_flickr_client_id', 'text', array(
            'label'        => _('Flickr public key'),
        ));
        $builder->add('GV_flickr_client_secret', 'text', array(
            'label'        => _('Flickr secret key'),
        ));
    }

    public function getName()
    {
        return null;
    }
}
