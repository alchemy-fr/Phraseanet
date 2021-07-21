<?php

/*
 * This file is part of Media-Alchemyst.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\MediaAlchemyst\Transmuter;

use Alchemy\BinaryDriver\Exception\ExceptionInterface as BinaryAdapterException;
use Alchemy\Phrasea\MediaAlchemyst\Exception\RuntimeException;
use Alchemy\Phrasea\MediaAlchemyst\Exception\SpecNotSupportedException;
use Alchemy\Phrasea\MediaAlchemyst\Specification\Flash;
use Alchemy\Phrasea\MediaAlchemyst\Specification\SpecificationInterface;
use Alchemy\Phrasea\MediaVorus\Media\MediaInterface;
use SwfTools\Exception\ExceptionInterface as SwfToolsException;
use Unoconv\Exception\ExceptionInterface as UnoconvException;
use Unoconv\Unoconv;

class Document2Flash extends AbstractTransmuter
{
    public function execute(SpecificationInterface $spec, MediaInterface $source, $dest)
    {
        if (! $spec instanceof Flash) {
            throw new SpecNotSupportedException('SwfTools only accept Flash specs');
        }

        $tmpDest = $this->tmpFileManager->createTemporaryFile(self::TMP_FILE_SCOPE, 'pdf2swf');

        try {
            if ($source->getFile()->getMimeType() != 'application/pdf') {
                $this->container['unoconv']->transcode(
                    $source->getFile()->getPathname(), Unoconv::FORMAT_PDF, $tmpDest
                );
            } else {
                copy($source->getFile()->getPathname(), $tmpDest);
            }

            $this->container['swftools.pdf-file']->toSwf($tmpDest, $dest);
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
        } catch (BinaryAdapterException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute flash to image due to Binary Adapter', $e->getCode(), $e);
        } catch (UnoconvException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute document to flash due to Unoconv', null, $e);
        } catch (SwfToolsException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw new RuntimeException('Unable to transmute document to flash due to SwfTools', null, $e);
        } catch (RuntimeException $e) {
            $this->tmpFileManager->clean(self::TMP_FILE_SCOPE);
            throw $e;
        }
    }
}
