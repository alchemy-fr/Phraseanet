<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\File;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Translation\TranslatorInterface;

class Dimension extends AbstractChecker
{
    protected $width;
    protected $height;

    public function __construct(Application $app, array $options)
    {
        if ( ! isset($options['width'])) {
            throw new \InvalidArgumentException('Missing "width" option');
        }

        if ( ! isset($options['height']) || null === $options['height']) {
            $options['height'] = $options['width'];
        }

        if ((int) $options['height'] <= 0 || (int) $options['width'] <= 0) {
            throw new \InvalidArgumentException('Dimensions should be greater than 0');
        }

        $this->width = $options['width'];
        $this->height = $options['height'];
        parent::__construct($app);
    }

    public function check(EntityManager $em, File $file)
    {
        $boolean = false;

        if (method_exists($file->getMedia(), 'getWidth')) {

            $boolean = $file->getMedia()->getWidth() >= $this->width
                && $file->getMedia()->getHeight() >= $this->height;
        }

        return new Response($boolean, $this);
    }

    public function getMessage(TranslatorInterface $translator)
    {
        return $translator->trans('The file does not match required dimension');
    }
}
