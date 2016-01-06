<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Metadata;

use Alchemy\Phrasea\Metadata\Tag\PdfText;
use Alchemy\Phrasea\Metadata\Tag\TfBasename;
use Alchemy\Phrasea\Metadata\Tag\TfExtension;
use Alchemy\Phrasea\Metadata\Tag\TfFilename;
use Alchemy\Phrasea\Metadata\Tag\TfMimetype;
use Alchemy\Phrasea\Metadata\Tag\TfSize;
use MediaVorus\Media\MediaInterface;
use PHPExiftool\Driver\Metadata\Metadata;
use PHPExiftool\Driver\Value\Mono as MonoValue;
use XPDF\PdfToText;
use XPDF\Exception\Exception as XPDFException;

class PhraseanetMetadataReader
{
    protected $pdfToText;

    /**
     * Sets a PdfToText driver for extracting PDF content.
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
     * Gets the PdfToText driver.
     *
     * @return PdfTotext
     */
    public function getPdfToText()
    {
        return $this->pdfToText;
    }

    public function read(MediaInterface $media)
    {
        $ret = [];
        $mimeType = $media->getFile()->getMimeType();

        foreach ([
            'getWidth' => 'TfWidth',
            'getHeight' => 'TfHeight',
            'getChannels' => 'TfChannels',
            'getColorDepth' => 'TfBits',
            'getDuration' => 'TfDuration',
         ] as $method => $tag) {
            $classname = 'Alchemy\\Phrasea\\Metadata\\Tag\\'.$tag;
            if (method_exists($media, $method)) {
                $ret[] = new Metadata(new $classname(), new MonoValue(call_user_func([$media, $method])));
            }
        }

        if ($mimeType == 'application/pdf' && null !== $this->pdfToText) {
            try {
                $text = $this->pdfToText->getText($media->getFile()->getRealPath());
                if (trim($text)) {
                    $ret[] = new Metadata(new PdfText(), new MonoValue($text));
                }
            } catch (XPDFException $e) {
            }
        }

        $ret[] = new Metadata(new TfMimetype(), new MonoValue($mimeType));
        $ret[] = new Metadata(new TfSize(), new MonoValue($media->getFile()->getSize()));
        $ret[] = new Metadata(new TfBasename(), new MonoValue(pathinfo($media->getFile()->getFileName(), PATHINFO_BASENAME)));
        $ret[] = new Metadata(new TfFilename(), new MonoValue(pathinfo($media->getFile()->getFileName(), PATHINFO_FILENAME)));
        $ret[] = new Metadata(new TfExtension(), new MonoValue(pathinfo($media->getFile()->getFileName(), PATHINFO_EXTENSION)));

        return $ret;
    }
}
