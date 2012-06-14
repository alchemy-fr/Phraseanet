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

use Alchemy\Phrasea\Metadata\Tag as PhraseaTag;
use Alchemy\Phrasea\Border\Attribute\Metadata as MetadataAttr;
use Doctrine\ORM\EntityManager;
use Entities\LazaretAttribute;
use Entities\LazaretFile;
use Entities\LazaretSession;
use Monolog\Logger;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
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
    protected $em;
    protected $filesystem;
    protected $logger;

    const RECORD_CREATED = 1;
    const LAZARET_CREATED = 2;
    const FORCE_RECORD = true;
    const FORCE_LAZARET = false;

    /**
     * Constructor
     *
     * @param \Doctrine\ORM\EntityManager $em     Entity manager
     * @param \Monolog\Logger             $logger A logger
     */
    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->filesystem = new Filesystem();
        $this->logger = $logger;
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->em = $this->filesystem = $this->logger = null;
    }

    /**
     * Add a file to Phraseanet after having checked it
     *
     * @param LazaretSession $session  The current Lazaret Session
     * @param File           $file     A File package object
     * @param type           $callable A callback to execute after process
     *                                          (arguments are $element (LazaretFile or \record_adapter),
     *                                          $visa (Visa)
     *                                          and $code (self::RECORD_CREATED or self::LAZARET_CREATED))
     * @param  type $forceBehavior Force a behavior, one of the self::FORCE_* constant
     * @return int  One of the self::RECORD_CREATED or self::LAZARET_CREATED constants
     */
    public function process(LazaretSession $session, File $file, $callable = null, $forceBehavior = null)
    {
        $visa = $this->getVisa($file);

        /**
         * Generates and write UUID
         */
        $file->getUUID(true, true);

        if (($visa->isValid() || $forceBehavior === self::FORCE_RECORD) && $forceBehavior !== self::FORCE_LAZARET) {

            $this->addMediaAttributes($file);

            $element = $this->createRecord($file);

            $code = self::RECORD_CREATED;
        } else {

            $element = $this->createLazaret($file, $visa, $session, $forceBehavior === self::FORCE_LAZARET);

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
     * @param  File $file A File package object
     * @return Visa The Visa
     */
    public function getVisa(File $file)
    {
        $visa = new Visa();

        foreach ($this->checkers as $checker) {
            $visa->addResponse($checker->check($this->em, $file));
        }

        return $visa;
    }

    /**
     * Registers a checker
     *
     * @param  Checker\CheckerInterface $checker The checker to register
     * @return Manager
     */
    public function registerChecker(Checker\CheckerInterface $checker)
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
     * @param  Checker\CheckerInterface $checker The checker to unregister
     * @return Manager
     */
    public function unregisterChecker(Checker\CheckerInterface $checker)
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
     * @return string The available filename to use
     */
    protected function bookLazaretPathfile($filename)
    {
        $root = __DIR__ . '/../../../../tmp/lazaret/';

        $infos = pathinfo($filename);

        $output = $root . $infos['basename'];

        $n = 1;
        while (file_exists($output) || ! touch($output)) {
            $output = $root . $infos['filename'] . '-' . ++ $n . '.' . $infos['extension'];
        }

        $this->filesystem->touch($output);

        return $output;
    }

    /**
     * Adds a record to Phraseanet
     *
     * @param  File           $file The package file
     * @return \record_adater
     */
    protected function createRecord(File $file)
    {
        $element = \record_adapter::createFromFile($file);

        $date = new \DateTime();

        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfArchivedate(), new MonoValue($date->format('Y/m/d H:i:s'))
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfRecordid(), new MonoValue($element->get_record_id())
                )
            )
        );

        $metadatas = array();

        $fileEntity = $file->getMedia()->getEntity();

        /**
         * @todo $key is not tagname but fieldname
         */
        $fieldToKeyMap = array();

        if ( ! $fieldToKeyMap) {
            foreach ($file->getCollection()->get_databox()->get_meta_structure() as $databox_field) {

                $tagname = $databox_field->get_tag()->getTagname();

                if ( ! isset($fieldToKeyMap[$tagname])) {
                    $fieldToKeyMap[$tagname] = array();
                }

                $fieldToKeyMap[$tagname][] = $databox_field->get_name();
            }
        }

        foreach ($fileEntity->getMetadatas() as $metadata) {

            $key = $metadata->getTag()->getTagname();

            if ( ! isset($fieldToKeyMap[$key])) {
                continue;
            }

            foreach ($fieldToKeyMap[$key] as $k) {
                if ( ! isset($metadatas[$k])) {
                    $metadatas[$k] = array();
                }

                $metadatas[$k] = array_merge($metadatas[$k], $metadata->getValue()->asArray());
            }
        }

        foreach ($file->getAttributes() as $attribute) {
            switch ($attribute->getName()) {

                /**
                 * @todo implement METATAG aka metadata by fieldname (where as
                 * current metadata is metadata by source.
                 */
                case Attribute\Attribute::NAME_METAFIELD:

                    $key = $attribute->getField()->get_name();

                    if ( ! isset($metadatas[$key])) {
                        $metadatas[$key] = array();
                    }

                    $metadatas[$key] = array_merge($metadatas[$key], array($attribute->getValue()));
                    break;

                case Attribute\Attribute::NAME_METADATA:

                    $key = $attribute->getValue()->getTag()->getTagname();

                    if ( ! isset($fieldToKeyMap[$key])) {
                        continue;
                    }

                    foreach ($fieldToKeyMap[$key] as $k) {
                        if ( ! isset($metadatas[$k])) {
                            $metadatas[$k] = array();
                        }

                        $metadatas[$k] = array_merge($metadatas[$k], $attribute->getValue()->getValue()->asArray());
                    }
                    break;
                case Attribute\Attribute::NAME_STATUS:

                    $element->set_binary_status($element->get_status() | $attribute->getValue());

                    break;
                case Attribute\Attribute::NAME_STORY:

                    $story = $attribute->getValue();

                    if ( ! $story->hasChild($element)) {
                        $story->appendChild($element);
                    }

                    break;
            }
        }

        $databox = $element->get_databox();

        $metas = array();

        foreach ($metadatas as $fieldname => $values) {
            foreach ($databox->get_meta_structure()->get_elements() as $databox_field) {

                if ($databox_field->get_name() == $fieldname) {

                    if ($databox_field->is_multi()) {

                        $values = array_unique($values);

                        foreach ($values as $value) {
                            if ( ! trim($value)) {
                                continue;
                            }
                            $metas[] = array(
                                'meta_struct_id' => $databox_field->get_id(),
                                'meta_id'        => null,
                                'value'          => $value,
                            );
                        }
                    } else {

                        $value = array_pop($values);

                        if ( ! trim($value)) {
                            continue;
                        }

                        $metas[] = array(
                            'meta_struct_id' => $databox_field->get_id(),
                            'meta_id'        => null,
                            'value'          => $value,
                        );
                    }
                }
            }
        }

        if ($metas) {
            $element->set_metadatas($metas, true);
        }

        $element->rebuild_subdefs();
        $element->reindex();

        return $element;
    }

    /**
     * Send a package file to lazaret
     *
     * @param  File                  $file    The package file
     * @param  Visa                  $visa    The visa related to the package file
     * @param  LazaretSession        $session The current LazaretSession
     * @param  Boolean               $forced  True if the file has been forced to quarantine
     * @return \Entities\LazaretFile
     */
    protected function createLazaret(File $file, Visa $visa, LazaretSession $session, $forced)
    {
        $date = new \DateTime();
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfQuarantine(), new MonoValue($date->format('Y/m/d H:i:s'))
                )
            )
        );

        $lazaretPathname = $this->bookLazaretPathfile($file->getFile()->getRealPath());

        $this->filesystem->copy($file->getFile()->getRealPath(), $lazaretPathname, true);

        $spec = new \MediaAlchemyst\Specification\Image();

        $spec->setResizeMode(\MediaAlchemyst\Specification\Image::RESIZE_MODE_INBOUND_FIXEDRATIO);
        $spec->setDimensions(375, 275);

        $core = \bootstrap::getCore();

        try {
           $core['media-alchemyst']
                ->open($file->getFile()->getPathname())
                ->turnInto($lazaretPathname, $spec)
                ->close();
        } catch (\MediaAlchemyst\Exception\Exception $e) {
            
        }

        $lazaretFile = new LazaretFile();
        $lazaretFile->setBaseId($file->getCollection()->get_base_id());
        $lazaretFile->setSha256($file->getSha256());
        $lazaretFile->setUuid($file->getUUID());
        $lazaretFile->setOriginalName($file->getOriginalName());

        $lazaretFile->setForced($forced);

        $lazaretFile->setPathname($lazaretPathname);
        $lazaretFile->setSession($session);

        $this->em->persist($lazaretFile);

        foreach ($file->getAttributes() as $fileAttribute) {
            $attribute = new LazaretAttribute();
            $attribute->setName($fileAttribute->getName());
            $attribute->setValue($fileAttribute->asString());
            $attribute->setLazaretFile($lazaretFile);

            $lazaretFile->addLazaretAttribute($attribute);

            $this->em->persist($attribute);
        }

        foreach ($visa->getResponses() as $response) {
            if ( ! $response->isOk()) {

                $check = new \Entities\LazaretCheck();
                $check->setCheckClassname(get_class($response->getChecker()));
                $check->setLazaretFile($lazaretFile);

                $lazaretFile->addLazaretCheck($check);

                $this->em->persist($check);
            }
        }

        $this->em->flush();

        return $lazaretFile;
    }

    /**
     * Add technical Metadata attribute to a package file by reference to add it
     * to Phraseanet
     *
     * @param  File                        $file The file
     * @return \Doctrine\ORM\EntityManager
     */
    protected function addMediaAttributes(File &$file)
    {

        if (method_exists($file->getMedia(), 'getWidth')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new PhraseaTag\TfWidth(), new MonoValue($file->getMedia()->getWidth())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getHeight')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new PhraseaTag\TfHeight(), new MonoValue($file->getMedia()->getHeight())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getChannels')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new PhraseaTag\TfChannels(), new MonoValue($file->getMedia()->getChannels())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getColorDepth')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new PhraseaTag\TfBits(), new MonoValue($file->getMedia()->getColorDepth())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getDuration')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new PhraseaTag\TfDuration(), new MonoValue($file->getMedia()->getDuration())
                    )
                )
            );
        }

        if ($file->getFile()->getMimeType() == 'application/pdf') {

            try {
                $extractor = \XPDF\PdfToText::load($this->logger);

                $text = $extractor->open($file->getFile()->getRealPath())
                    ->getText();

                if (trim($text)) {
                    $file->addAttribute(
                        new MetadataAttr(
                            new Metadata(
                                new PhraseaTag\PdfText(), new MonoValue($text)
                            )
                        )
                    );
                }

                $extractor->close();
            } catch (\XPDF\Exception\Exception $e) {

            }
        }

        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfMimetype(), new MonoValue($file->getFile()->getMimeType()))));
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfSize(), new MonoValue($file->getFile()->getSize()))));
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfBasename(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_BASENAME))
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfFilename(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_FILENAME))
                )
            )
        );
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new PhraseaTag\TfExtension(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION))
                )
            )
        );

        return $this;
    }
}
