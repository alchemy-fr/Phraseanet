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

class YoutubeFormType extends AbstractType
{
    /** @var UrlGenerator */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dashboard_youtube = '<a href="https://code.google.com/apis/youtube/dashboard/" target="_blank">https://code.google.com/apis/youtube/dashboard/</a>';
        $youtube_console_url = '<a href="https://code.google.com/apis/console/" target="_blank">https://code.google.com/apis/console/</a>';

        try {
            $youtube_callback = $this->generator->generate('prod_bridge_callback', array('api_name' => 'youtube'), UrlGenerator::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $youtube_callback = null;
        }

        $builder->add('GV_youtube_api', 'checkbox', array(
            'label'        => _('Use youtube API'),
            'data'         => false,
            'help_message' => sprintf(_('Create API account at %s, then use %s as callback URL value'), $youtube_console_url, $youtube_callback),
        ));


        $builder->add('GV_youtube_client_id', 'text', array(
            'label'        => _('Youtube public key'),
        ));
        $builder->add('GV_youtube_client_secret', 'text', array(
            'label'        => _('Youtube secret key'),
        ));
        $builder->add('GV_youtube_dev_key', 'text', array(
            'label'        => _('Youtube developer key'),
            'help_message' => sprintf(_('See %s'), $dashboard_youtube),
        ));
    }

    public function getName()
    {
        return null;
    }
}
