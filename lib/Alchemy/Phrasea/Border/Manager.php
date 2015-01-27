<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Core\Event\RecordEvent\RecordStatusChangedEvent;
use Alchemy\Phrasea\Core\PhraseaEvents;
use Alchemy\Phrasea\Metadata\Tag\TfArchivedate;
use Alchemy\Phrasea\Metadata\Tag\TfQuarantine;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Metadata\Tag\TfRecordid;
use Alchemy\Phrasea\Border\Attribute\Metadata as MetadataAttr;
use Alchemy\Phrasea\Model\Entities\LazaretAttribute;
use Alchemy\Phrasea\Model\Entities\LazaretCheck;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Specification\Image as ImageSpec;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
use PHPExiftool\Driver\Value\Multi;
use Silex\Application;
use Symfony\Component\Filesystem\Exception\IOException;

/**
 * Phraseanet Border Manager
 *
 * It controls which files enter in Phraseanet.
 * Many Checkers can be registered to verify criterias.
 *
 */
class Manager
{
    protected $checkers = [];
    protected $app;
    protected $filesystem;

    const RECORD_CREATED = 1;
    const LAZARET_CREATED = 2;
    const FORCE_RECORD = true;
    const FORCE_LAZARET = false;

    /**
     * Constructor
     *
     * @param Application $app The application context
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->app = null;
    }

    /**
     * Add a file to Phraseanet after having checked it
     *
     * @param  LazaretSession $session       The current Lazaret Session
     * @param  File           $file          A File package object
     * @param  type           $callable      A callback to execute after process
     *                                       (arguments are $element (LazaretFile or \record_adapter),
     *                                       $visa (Visa)
     *                                       and $code (self::RECORD_CREATED or self::LAZARET_CREATED))
     * @param  type           $forceBehavior Force a behavior, one of the self::FORCE_* constant
     * @return int            One of the self::RECORD_CREATED or self::LAZARET_CREATED constants
     */
    public function process(LazaretSession $session, File $file, $callable = null, $forceBehavior = null)
    {
        $visa = $this->getVisa($file);

        /**
         * Generate UUID
         */
        $file->getUUID(true, false);

        if (($visa->isValid() || $forceBehavior === self::FORCE_RECORD) && $forceBehavior !== self::FORCE_LAZARET) {

            $this->addMediaAttributes($file);

            $element = $this->createRecord($file);

            $code = self::RECORD_CREATED;
        } else {

            $element = $this->createLazaret($file, $visa, $session, $forceBehavior === self::FORCE_LAZARET);

            $code = self::LAZARET_CREATED;
        }

        /**
         * Write UUID
         */
        $file->getUUID(false, true);

        if (is_callable($callable)) {
            $callable($element, $visa, $code);
        }

        $visa = null;

        return $code;
    }

    /**
     * Check a File package object against the Checkers, and returns a Visa
     *
     * @param  File $file A File package object
     * @return Visa The Visa
     */
    public function getVisa(File $file)
    {
        $visa = new Visa();

        foreach ($this->checkers as $checker) {
            $visa->addResponse($checker->check($this->app['EM'], $file));
        }

        return $visa;
    }

    /**
     * Registers a checker
     *
     * @param  CheckerInterface $checker The checker to register
     * @return Manager
     */
    public function registerChecker(CheckerInterface $checker)
    {
        $this->checkers[] = $checker;

        return $this;
    }

    /**
     * Registers an array of checkers
     *
     * @param  array   $checkers Array of checkers
     * @return Manager
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
     * @param  CheckerInterface $checker The checker to unregister
     * @return Manager
     */
    public function unregisterChecker(CheckerInterface $checker)
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
     * @param  string $filename The desired filename
     * @param  string $suffix   A suffix to the filename
     * @return string The available filename to use
     */
    protected function bookLazaretPathfile($filename, $suffix = '')
    {
        $output = $this->app['tmp.path'].'/lazaret/lzrt_' . substr($filename, 0, 3) . '_' . $suffix . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $infos = pathinfo($output);
        $n = 0;

        $this->app['filesystem']->mkdir($this->app['tmp.lazaret.path']);

        while (true) {
            $output = sprintf('%s/%s-%d%s', $infos['dirname'], $infos['filename'],  ++ $n, (isset($infos['extension']) ? '.' . $infos['extension'] : ''));

            try {
                if ( ! $this->app['filesystem']->exists($output)) {
                    $this->app['filesystem']->touch($output);
                    break;
                }
            } catch (IOException $e) {

            }
        }

        return realpath($output);
    }

    /**
     * Adds a record to Phraseanet
     *
     * @param  File           $file The package file
     * @return \record_adater
     */
    protected function createRecord(File $file)
    {
        $element = \record_adapter::createFromFile($file, $this->app);

        $date = new \DateTime();

        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfArchivedate(), new MonoValue($date->format('Y/m/d H:i:s'))
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfRecordid(), new MonoValue($element->get_record_id())
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfBasename(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_BASENAME))
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfFilename(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_FILENAME))
                )
            )
        );

        $newMetadata = $file->getMedia()->getMetadatas()->toArray();
        foreach ($file->getAttributes() as $attribute) {
            switch ($attribute->getName()) {
                case AttributeInterface::NAME_METAFIELD:
                    $values = $attribute->getValue();
                    $value = $attribute->getField()->is_multi() ? new Multi($values) : new MonoValue(array_pop($values));

                    $tag = $attribute->getField()->get_tag();

                    if ($tag instanceof \Alchemy\Phrasea\Metadata\Tag\Nosource) {
                        $tag->setTagname($attribute->getField()->get_name());
                        $_meta = new Metadata($tag, $value);
                    } else {
                        $_meta = new Metadata($attribute->getField()->get_tag(), $value);
                    }
                    $newMetadata[] = $_meta;
                    break;

                case AttributeInterface::NAME_METADATA:
                    $newMetadata[] = $attribute->getValue();
                    break;
                case AttributeInterface::NAME_STATUS:
                    $element->set_binary_status(decbin(bindec($element->get_status()) | bindec($attribute->getValue())));

                    $this->app['dispatcher']->dispatch(PhraseaEvents::RECORD_STATUS_CHANGED, new RecordStatusChangedEvent($element));

                    break;
                case AttributeInterface::NAME_STORY:

                    $story = $attribute->getValue();

                    if ( ! $story->hasChild($element)) {
                        $story->appendChild($element);
                    }

                    break;
            }
        }

        $this->app['phraseanet.metadata-setter']->replaceMetadata($newMetadata, $element);

        $element->rebuild_subdefs();

        return $element;
    }

    /**
     * Send a package file to lazaret
     *
     * @param File           $file    The package file
     * @param Visa           $visa    The visa related to the package file
     * @param LazaretSession $session The current LazaretSession
     * @param Boolean        $forced  True if the file has been forced to quarantine
     *
     * @return LazaretFile
     */
    protected function createLazaret(File $file, Visa $visa, LazaretSession $session, $forced)
    {
        $date = new \DateTime();
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfQuarantine(), new MonoValue($date->format('Y/m/d H:i:s'))
                )
            )
        );

        $lazaretPathname = $this->bookLazaretPathfile($file->getOriginalName());
        $lazaretPathnameThumb = $this->bookLazaretPathfile($file->getOriginalName(), 'thumb');

        $this->app['filesystem']->copy($file->getFile()->getRealPath(), $lazaretPathname, true);

        $spec = new ImageSpec();

        $spec->setResizeMode(ImageSpec::RESIZE_MODE_INBOUND_FIXEDRATIO);
        $spec->setDimensions(375, 275);

        try {
            $this->app['media-alchemyst']->turnInto($file->getFile()->getPathname(), $lazaretPathnameThumb, $spec);
        } catch (MediaAlchemystException $e) {

        }

        $lazaretFile = new LazaretFile();
        $lazaretFile->setBaseId($file->getCollection()->get_base_id());
        $lazaretFile->setSha256($file->getSha256());
        $lazaretFile->setUuid($file->getUUID());
        $lazaretFile->setOriginalName($file->getOriginalName());

        $lazaretFile->setForced($forced);

        $lazaretFile->setFilename(pathinfo($lazaretPathname, PATHINFO_BASENAME));
        $lazaretFile->setThumbFileName(pathinfo($lazaretPathnameThumb, PATHINFO_BASENAME));

        $lazaretFile->setSession($session);

        $this->app['EM']->persist($lazaretFile);

        foreach ($file->getAttributes() as $fileAttribute) {
            $attribute = new LazaretAttribute();
            $attribute->setName($fileAttribute->getName());
            $attribute->setValue($fileAttribute->asString());
            $attribute->setLazaretFile($lazaretFile);
            $lazaretFile->addAttribute($attribute);

            $this->app['EM']->persist($attribute);
        }

        foreach ($visa->getResponses() as $response) {
            if ( ! $response->isOk()) {

                $check = new LazaretCheck();
                $check->setCheckClassname(get_class($response->getChecker()));
                $check->setLazaretFile($lazaretFile);

                $lazaretFile->addCheck($check);

                $this->app['EM']->persist($check);
            }
        }

        $this->app['EM']->flush();

        return $lazaretFile;
    }

    /**
     * Add technical Metadata attribute to a package file by reference to add it
     * to Phraseanet
     *
     * @param File $file The file
     */
    protected function addMediaAttributes(File $file)
    {
        $metadataCollection = $this->app['phraseanet.metadata-reader']->read($file->getMedia());

        array_walk($metadataCollection, function (Metadata $metadata) use ($file) {
            $file->addAttribute(new MetadataAttr($metadata));
        });

        return $this;
    }
}
