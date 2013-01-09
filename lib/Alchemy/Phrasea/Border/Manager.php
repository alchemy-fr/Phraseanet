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

use Alchemy\Phrasea\Border\Checker\CheckerInterface;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Border\Attribute\AttributeInterface;
use Alchemy\Phrasea\Metadata\Tag\PdfText;
use Alchemy\Phrasea\Metadata\Tag\TfArchivedate;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Metadata\Tag\TfBits;
use Alchemy\Phrasea\Metadata\Tag\TfChannels;
use Alchemy\Phrasea\Metadata\Tag\TfDuration;
use Alchemy\Phrasea\Metadata\Tag\TfExtension;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Metadata\Tag\TfHeight;
use Alchemy\Phrasea\Metadata\Tag\TfMimetype;
use Alchemy\Phrasea\Metadata\Tag\TfQuarantine;
use Alchemy\Phrasea\Metadata\Tag\TfRecordid;
use Alchemy\Phrasea\Metadata\Tag\TfSize;
use Alchemy\Phrasea\Metadata\Tag\TfWidth;
use Alchemy\Phrasea\Border\Attribute\Metadata as MetadataAttr;
use Entities\LazaretAttribute;
use Entities\LazaretFile;
use Entities\LazaretSession;
use MediaAlchemyst\Exception\ExceptionInterface as MediaAlchemystException;
use MediaAlchemyst\Specification\Image as ImageSpec;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
use Symfony\Component\Filesystem\Exception\IOException;
use XPDF\PdfToText;

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
    protected $app;
    protected $filesystem;
    protected $pdfToText;

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
     * Set a PdfToText object for extracting PDF content
     *
     * @param PdfTotext $pdfToText The PdfToText Object
     *
     * @return Manager
     */
    public function setPdfToText(PdfToText $pdfToText)
    {
        $this->pdfToText = $pdfToText;

        return $this;
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
        $output = __DIR__ . '/../../../../tmp/lazaret/lzrt_' . substr($filename, 0, 3) . '_' . $suffix . '.' . pathinfo($filename, PATHINFO_EXTENSION);
        $infos = pathinfo($output);
        $n = 0;

        $this->app['filesystem']->mkdir(__DIR__ . '/../../../../tmp/lazaret');

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

        $metadatas = array();

        /**
         * @todo $key is not tagname but fieldname
         */
        $fieldToKeyMap = array();

        if (! $fieldToKeyMap) {
            foreach ($file->getCollection()->get_databox()->get_meta_structure() as $databox_field) {

                $tagname = $databox_field->get_tag()->getTagname();

                if ( ! isset($fieldToKeyMap[$tagname])) {
                    $fieldToKeyMap[$tagname] = array();
                }

                $fieldToKeyMap[$tagname][] = $databox_field->get_name();
            }
        }

        foreach ($file->getMedia()->getMetadatas() as $metadata) {

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
                case AttributeInterface::NAME_METAFIELD:

                    $key = $attribute->getField()->get_name();

                    if ( ! isset($metadatas[$key])) {
                        $metadatas[$key] = array();
                    }

                    $metadatas[$key] = array_merge($metadatas[$key], $attribute->getValue());
                    break;

                case AttributeInterface::NAME_METADATA:

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
                case AttributeInterface::NAME_STATUS:

                    $element->set_binary_status($element->get_status() | $attribute->getValue());

                    break;
                case AttributeInterface::NAME_STORY:

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

                        $tmpValues = array();
                        foreach ($values as $value) {
                            $tmpValues = array_merge($tmpValues, \caption_field::get_multi_values($value, $databox_field->get_separator()));
                        }

                        $values = array_unique($tmpValues);

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
            $this->app['media-alchemyst']
                ->open($file->getFile()->getPathname())
                ->turnInto($lazaretPathnameThumb, $spec)
                ->close();
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

            $lazaretFile->addLazaretAttribute($attribute);

            $this->app['EM']->persist($attribute);
        }

        foreach ($visa->getResponses() as $response) {
            if ( ! $response->isOk()) {

                $check = new \Entities\LazaretCheck();
                $check->setCheckClassname(get_class($response->getChecker()));
                $check->setLazaretFile($lazaretFile);

                $lazaretFile->addLazaretCheck($check);

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
     * @param  File                        $file The file
     * @return \Doctrine\ORM\EntityManager
     */
    protected function addMediaAttributes(File $file)
    {

        if (method_exists($file->getMedia(), 'getWidth')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new TfWidth(), new MonoValue($file->getMedia()->getWidth())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getHeight')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new TfHeight(), new MonoValue($file->getMedia()->getHeight())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getChannels')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new TfChannels(), new MonoValue($file->getMedia()->getChannels())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getColorDepth')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new TfBits(), new MonoValue($file->getMedia()->getColorDepth())
                    )
                )
            );
        }
        if (method_exists($file->getMedia(), 'getDuration')) {
            $file->addAttribute(
                new MetadataAttr(
                    new Metadata(
                        new TfDuration(), new MonoValue($file->getMedia()->getDuration())
                    )
                )
            );
        }

        if ($file->getFile()->getMimeType() == 'application/pdf' && null !== $this->pdfToText) {

            try {
                $text = $this->pdfToText->open($file->getFile()->getRealPath())
                    ->getText();

                if (trim($text)) {
                    $file->addAttribute(
                        new MetadataAttr(
                            new Metadata(
                                new PdfText(), new MonoValue($text)
                            )
                        )
                    );
                }

                $this->pdfToText->close();
            } catch (\XPDF\Exception\Exception $e) {

            }
        }

        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfMimetype(), new MonoValue($file->getFile()->getMimeType()))));
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfSize(), new MonoValue($file->getFile()->getSize()))));
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
        $file->addAttribute(
            new MetadataAttr(
                new Metadata(
                    new TfExtension(), new MonoValue(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION))
                )
            )
        );

        return $this;
    }
}
