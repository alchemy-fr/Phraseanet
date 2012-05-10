<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Core;
use Entities\LazaretAttribute;
use Entities\LazaretFile;
use Entities\LazaretSession;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Phraseanet Border Manager
 *
 * It controls which files enter in Phraseanet.
 * Many Checkers can be registered to verify criterias.
 *
 */
class Manager
{
    protected $checkers = array();
    protected $core;
    protected $filesystem;

    const RECORD_CREATED = 1;
    const LAZARET_CREATED = 2;
    const FORCE_RECORD = true;
    const FORCE_LAZARET = false;

    /**
     * Constructor
     *
     * @param \Alchemy\Phrasea\Core $core   Phraseanet Core
     */
    public function __construct(Core $core)
    {
        $this->core = $core;
        $this->filesystem = new Filesystem();
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->core = $this->filesystem = null;
    }

    /**
     * Add a file to Phraseanet after having checked it
     *
     * @param LazaretSession    $session        The current Lazaret Session
     * @param File              $file           A File package object
     * @param type              $callable       A callback to execute after process
     *                                          (arguments are $element (LazaretFile or \record_adapter),
     *                                          $visa (Visa)
     *                                          and $code (self::RECORD_CREATED or self::LAZARET_CREATED))
     * @param type              $forceBehaviour Force a behaviour, one of the self::FORCE_* constant
     * @return int              One of the self::RECORD_CREATED or self::LAZARET_CREATED constants
     */
    public function process(LazaretSession $session, File $file, $callable = null, $forceBehaviour = null)
    {
        $visa = $this->getVisa($file);

        if (($visa->isValid() || $forceBehaviour === self::FORCE_RECORD) && $forceBehaviour !== self::FORCE_LAZARET) {

            /**
             * add attributes
             */
            $element = \record_adapter::create($file->getCollection(), $file->getPathfile(), $file->getOriginalName());

            $code = self::RECORD_CREATED;
        } else {

            $lazaretPathname = $this->bookLazaretPathfile($file->getPathfile());

            $this->filesystem->copy($file->getPathfile(), $lazaretPathname, true);

            $lazaretFile = new LazaretFile();
            $lazaretFile->setBaseId($file->getCollection()->get_base_id());
            $lazaretFile->setSha256($file->getSha256());
            $lazaretFile->setUuid($file->getUUID());
            $lazaretFile->setOriginalName($file->getOriginalName());

            $lazaretFile->setPathname($lazaretPathname);
            $lazaretFile->setSession($session);

            foreach ($file->getAttributes() as $fileAttribute) {
                $attribute = new LazaretAttribute();
                $attribute->setName($fileAttribute->getName());
                $attribute->setValue($fileAttribute->asString());
                $attribute->setLazaretFile($lazaretFile);

                $lazaretFile->addLazaretAttribute($attribute);

                $this->core['EM']->persist($attribute);
            }

            $this->core['EM']->persist($lazaretFile);

            $this->core['EM']->flush();

            $element = $lazaretFile;

            $code = self::LAZARET_CREATED;
        }

        if (is_callable($callable)) {
            $callable($element, $visa, $code);
        }

        $visa = null;

        return $code;
    }

    /**
     * Check a File package object against the Checkers, and returns a Visa
     *
     * @param   File    $file   A File package object
     * @return  Visa            The Visa
     */
    public function getVisa(File $file)
    {
        $visa = new Visa();

        foreach ($this->checkers as $checker) {
            $visa->addResponse($checker->check($this->core['EM'], $file));
        }

        return $visa;
    }

    /**
     * Registers a checker
     *
     * @param   Checker\Checker $checker    The checker to register
     * @return  Manager
     */
    public function registerChecker(Checker\Checker $checker)
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * Registers an array of checkers
     *
     * @param   array   $checkers   Array of checkers
     * @return  Manager
     */
    public function registerCheckers(array $checkers)
    {
        foreach ($checkers as $checker) {
            $this->registerChecker($checker);
        }

        return $this;
    }

    /**
     * Unregister a checker
     *
     * @param   Checker\Checker   $checker  The checker to unregister
     * @return  Manager
     */
    public function unregisterChecker(Checker\Checker $checker)
    {
        $checkers = $this->checkers;
        foreach ($this->checkers as $offset => $registered) {

            if ($checker == $registered) {
                array_splice($checkers, $offset, 1);
            }
        }
        $this->checkers = $checkers;

        return $this;
    }

    /**
     * Returns all the checkers registered
     *
     * @return array
     */
    public function getCheckers()
    {
        return $this->checkers;
    }

    /**
     * Find an available Lazaret filename and creates the empty file.
     *
     * @param   string  $filename   The desired filename
     * @return  string              The available filename to use
     */
    protected function bookLazaretPathfile($filename)
    {
        $root = $this->core['Registry']->get('GV_RootPath') . 'tmp/lazaret/';

        $infos = pathinfo($filename);

        $output = $root . $infos['basename'];

        $n = 1;
        while (file_exists($output) || ! touch($output)) {
            $output = $root . $infos['filename'] . '-' . ++ $n . '.' . $infos['extension'];
        }

        $this->filesystem->touch($output);

        return $output;
    }
}
