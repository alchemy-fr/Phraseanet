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

class ClientFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_download_max', 'integer', array(
            'label'        => _('Maximum megabytes allowed for download'),
            'data'         => 120,
            'help_message' => _('If request is bigger, then mail is still available'),
        ));
        $builder->add('GV_ong_search', 'integer', array(
            'label'        => _('Search tab position'),
            'data'         => 1,
        ));
        $builder->add('GV_ong_advsearch', 'integer', array(
            'label'        => _('Advanced search tab position'),
            'data'         => 2,
        ));
        $builder->add('GV_ong_topics', 'integer', array(
            'label'        => _('Topics tab position'),
            'data'         => 0,
        ));
        $builder->add('GV_ong_actif', 'integer', array(
            'label'        => _('Active tab position'),
            'data'         => 1,
        ));

        $builder->add('GV_client_render_topics', 'choice', array(
            'label'        => _('Topics display mode'),
            'data'         => 'tree',
            'choices'      => array('tree' => _('Trees'), 'popups' => _('Drop-down')),
        ));

        $builder->add('GV_rollover_reg_preview', 'checkbox', array(
            'label'        => _('Enable roll-over on stories'),
            'data'         => true,
        ));
        $builder->add('GV_rollover_chu', 'checkbox', array(
            'label'        => _('Enable roll-over on basket elements'),
            'data'         => true,
        ));

        $builder->add('GV_client_coll_ckbox', 'choice', array(
            'label'        => _('Collections display mode'),
            'data'         => 'checkbox',
            'choices'      => array('popup' => _('Drop-down'), 'checkbox' => _('Check-box')),
        ));

        $builder->add('GV_viewSizeBaket', 'checkbox', array(
            'label'        => _('Display the total size of the document basket'),
            'data'         => true,
        ));
        $builder->add('GV_clientAutoShowProposals', 'checkbox', array(
            'label'        => _('Display proposals tab'),
            'data'         => true,
        ));
        $builder->add('GV_needAuth2DL', 'checkbox', array(
            'label'        => _('Require authentication to download documents'),
            'data'         => true,
            'help_message' => _('Used for guest account'),
        ));
        $builder->add('GV_requireTOUValidationForExport', 'checkbox', array(
            'label'        => _('Users must accept Terms of Use for each export'),
            'data'         => false,
        ));
    }

    public function getName()
    {
        return null;
    }
}
