<?php

namespace Alchemy\Phrasea\Media\Subdef;

use Symfony\Component\Translation\TranslatorInterface;
use Alchemy\Phrasea\Media\Subdef\Specification\PdfSpecification;

class Pdf extends Provider
{
    protected $options = [];

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getType()
    {
        return self::TYPE_PDF;
    }

    public function getDescription()
    {
        return $this->translator->trans('Generates a pdf file');
    }

    public function getMediaAlchemystSpec()
    {
        if (! $this->spec) {
            $this->spec = new PdfSpecification();
        }

        return $this->spec;
    }

}