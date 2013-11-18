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
use Symfony\Component\Validator\Constraints as Assert;

class HttpServerFormType extends AbstractType
{
    private $availableLanguages;

    public function __construct(array $availableLanguages)
    {
        $this->availableLanguages = $availableLanguages;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('GV_default_lng', 'choice', array(
            'multiple'    => false,
            'expanded'    => false,
            'choices'     => $this->availableLanguages,
            'label'       => _('Default language'),
            'data'        => 'fr_FR',
        ));
    }

    public function getName()
    {
        return null;
    }
}
