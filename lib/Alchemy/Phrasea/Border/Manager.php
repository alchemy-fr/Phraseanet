<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Border\Attribute\Metadata as MetadataAttr;
use Alchemy\Phrasea\Border\Attribute\MetaField as MetafieldAttr;
use Alchemy\Phrasea\Border\Attribute\Status as StatusAttr;
use Alchemy\Phrasea\Border\Attribute\Story as StoryAttr;
use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Core\Event\Record\RecordEvents;
use Alchemy\Phrasea\Core\Event\Record\SubdefinitionCreateEvent;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Metadata\PhraseanetMetadataSetter;
use Alchemy\Phrasea\Metadata\Tag\TfArchivedate;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Metadata\Tag\TfQuarantine;
use Alchemy\Phrasea\Metadata\Tag\TfRecordid;
use Alchemy\Phrasea\Model\Entities\LazaretAttribute;
use Alchemy\Phrasea\Model\Entities\LazaretCheck;
use Alchemy\Phrasea\Model\Entities\LazaretFile;
use Alchemy\Phrasea\Model\Entities\LazaretSession;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
use PHPExiftool\Driver\Value\Multi;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Phraseanet Border Manager
 *
 * It controls which files enter in Phraseanet.
 * Many Checkers can be registered to verify criterias.
 *
 */
class Manager
{
    /**
     * @var CheckerInterface[]
     */
    protected $checkers = [];
    protected $app;
    protected $filesystem;

    /**
     * @var bool
     */
    private $enabled = true;

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
     * Whether checks are activated while electing Visa
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Add a file to Phraseanet after having checked it
     *
     * @param  LazaretSession $session       The current Lazaret Session
     * @param  File           $file          A File package object
     * @param  callable       $callable      A callback to execute after process
     *                                       (arguments are $element (LazaretFile or \record_adapter),
     *                                       $visa (Visa)
     *                                       and $code (self::RECORD_CREATED or self::LAZARET_CREATED))
     * @param  bool           $forceBehavior Force a behavior, one of the self::FORCE_* constant
     * @return int            One of the self::RECORD_CREATED or self::LAZARET_CREATED constants
     */
    public function process(LazaretSession $session, File $file, $callable = null, $forceBehavior = null, $nosubdef = false)
    {
        $visa = $this->getVisa($file);

        // READ the uuid (possibly generates one) but DO NOT write (because we need the stripped file for sha compare ?)

        $file->getUUID(true, false);

        if (($visa->isValid() || $forceBehavior === self::FORCE_RECORD) && $forceBehavior !== self::FORCE_LAZARET) {

            $this->addMediaAttributes($file);

            // Write UUID

            $file->getUUID(false, true);

            $element = $this->createRecord($file, $nosubdef);

            $code = self::RECORD_CREATED;
        } else {

            // Write UUID

            $file->getUUID(false, true);

            $element = $this->createLazaret($file, $visa, $session, $forceBehavior === self::FORCE_LAZARET);

            $code = self::LAZARET_CREATED;
        }

//        // Write UUID
//        $file->getUUID(false, true);

        if (is_callable($callable)) {
            $callable($element, $visa, $code);
        }

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

        if (!$this->isEnabled()) {
            return $visa;
        }

        foreach ($this->checkers as $checker) {
            if ($checker->isApplicable($file)) {
                $visa->addResponse($checker->check($this->app['orm.em'], $file));
            }
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
        if (!$this->hasChecker($checker)) {
            $this->checkers[] = $checker;
        }

        return $this;
    }

    /**
     * @param CheckerInterface $checker
     * @return bool
     */
    public function hasChecker(CheckerInterface $checker)
    {
        foreach ($this->checkers as $registered) {
            if ($checker === $registered) {
                return true;
            }
        }

        return false;
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
        if (false === $this->hasChecker($checker)) {
            throw new \LogicException('Trying to unregister unregistered checker');
        }

        foreach ($this->checkers as $key => $registeredChecker) {
            if ($checker === $registeredChecker) {
                unset($this->checkers[$key]);
            }
        }

        return $this;
    }

    /**
     * Get checker instance from its class name.
     *
     * @param string $checkerName
     * @return CheckerInterface
     */
    public function getCheckerFromFQCN($checkerName)
    {
        $checkerName = trim($checkerName, '\\');
        if (!class_exists($checkerName)) {
            throw new \RuntimeException('Checker FQCN does not exists');
        }

        foreach ($this->checkers as $checker) {
            if ($checker instanceof $checkerName) {
                return $checker;
            }
        }

        throw new RuntimeException('Checker could not be found');
    }

    /**
     * Returns all the checkers registered
     *
     * @return array
     */
    public function getCheckers()
    {
        if (!$this->enabled) {
            return [];
        }

        return array_values($this->checkers);
    }

    /**
     * Adds a record to Phraseanet
     *
     * @param  File           $file The package file
     * @return \record_adapter
     */
    protected function createRecord(File $file, $nosubdef=false)
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
                    new TfRecordid(), new MonoValue($element->getRecordId())
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
                    /** @var MetafieldAttr $attribute */
                    $values = $attribute->getValue();
                    $value = $attribute->getField()->is_multi() ? new Multi($values) : new MonoValue(array_pop($values));

                    $newMetadata[] = new Metadata($attribute->getField()->get_tag(), $value);
                    break;

                case AttributeInterface::NAME_METADATA:
                    /** @var MetadataAttr $attribute */
                    $newMetadata[] = $attribute->getValue();
                    break;
                case AttributeInterface::NAME_STATUS:
                    /** @var StatusAttr $attribute */
                    $element->setStatus(decbin(bindec($element->getStatus()) | bindec($attribute->getValue())));

                    break;
                case AttributeInterface::NAME_STORY:

                    /** @var StoryAttr $attribute */
                    $story = $attribute->getValue();

                    if ( ! $story->hasChild($element)) {
                        $story->appendChild($element);
                    }

                    break;
            }
        }

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->app['dispatcher'];
        $dispatcher->addListener(
            RecordEvents::METADATA_CHANGED,
            function (Event $event)  {
                // we do not want replaceMetadata() to send a writemeta
//                $event->stopPropagation();
            },
            10
        );


        /** @var PhraseanetMetadataSetter $phraseanetMetadataSetter */
        $phraseanetMetadataSetter = $this->app['phraseanet.metadata-setter'];
        $phraseanetMetadataSetter->replaceMetadata($newMetadata, $element);

        if(!$nosubdef) {
            $dispatcher->dispatch(RecordEvents::SUBDEFINITION_CREATE, new SubdefinitionCreateEvent($element, true));
        }

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

        $lazaretFilesystemService = $this->app['phraseanet.lazaret_filesystem'];
        $persistedLazaret = $lazaretFilesystemService->writeLazaret($file);

        $lazaretFile = new LazaretFile();
        $lazaretFile->setBaseId($file->getCollection()->get_base_id());
        $lazaretFile->setSha256($file->getSha256());
        $lazaretFile->setUuid($file->getUUID());
        $lazaretFile->setOriginalName($file->getOriginalName());

        $lazaretFile->setForced($forced);

        $lazaretFile->setFilename($persistedLazaret->getFilename());
        $lazaretFile->setThumbFileName($persistedLazaret->getThumbnailFilename());

        $lazaretFile->setSession($session);

        $this->app['orm.em']->persist($lazaretFile);

        foreach ($file->getAttributes() as $fileAttribute) {
            $attribute = new LazaretAttribute();
            $attribute->setName($fileAttribute->getName());
            $attribute->setValue($fileAttribute->asString());
            $attribute->setLazaretFile($lazaretFile);
            $lazaretFile->addAttribute($attribute);

            $this->app['orm.em']->persist($attribute);
        }

        foreach ($visa->getResponses() as $response) {
            if ( ! $response->isOk()) {

                $check = new LazaretCheck();
                $check->setCheckClassname(get_class($response->getChecker()));
                $check->setLazaretFile($lazaretFile);

                $lazaretFile->addCheck($check);

                $this->app['orm.em']->persist($check);
            }
        }

        $this->app['orm.em']->flush();

        return $lazaretFile;
    }

    /**
     * Add technical Metadata attribute to a package file by reference to add it
     * to Phraseanet
     *
     * @param File $file The file
     *
     * @return Manager
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
