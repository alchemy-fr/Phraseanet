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

class DailymotionFormType extends AbstractType
{
    /** @var UrlGenerator */
    private $generator;

    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $create_api_dailymotion = '<a href="http://www.dailymotion.com/profile/developer" target="_blank">http://www.dailymotion.com/profile/developer</a>';

        try {
            $dailymotion_callback = $this->generator->generate('prod_bridge_callback', array('api_name' => 'dailymotion'), UrlGenerator::ABSOLUTE_URL);
        } catch (RouteNotFoundException $e) {
            $dailymotion_callback = null;
        }


        $builder->add('GV_dailymotion_api', 'checkbox', array(
            'label'        => _('Use Dailymotion API'),
            'data'         => false,
            'help_message' => sprintf(_('Create API account at %s, then use %s as callback URL value'), $create_api_dailymotion, $dailymotion_callback),
        ));

        $builder->add('GV_dailymotion_client_id', 'text', array(
            'label'        => _('Dailymotion public key'),
        ));
        $builder->add('GV_dailymotion_client_secret', 'text', array(
            'label'        => _('Dailymotion secret key'),
        ));
    }

    public function getName()
    {
        return null;
    }
}
