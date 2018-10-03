<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Model\Entities\Preset;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;

class PresetManipulator implements ManipulatorInterface
{
    /** @var Objectmanager */
    private $om;
    /** @var TranslatorInterface */
    private $translator;
    /** @var EntityRepository */
    private $repository;

    public function __construct(ObjectManager $om, EntityRepository $repo)
    {
        $this->om = $om;
        $this->repository = $repo;
    }

    public function create(User $user, $sbasId, $title, array $data)
    {
        $preset = new Preset();

        $preset->setUser($user);
        $preset->setTitle($title);
        $preset->setData($data);
        $preset->setSbasId($sbasId);

        $this->om->persist($preset);
        $this->om->flush();

        return $preset;
    }

    public function delete(Preset $preset)
    {
        $this->om->remove($preset);
        $this->om->flush();
    }
}
