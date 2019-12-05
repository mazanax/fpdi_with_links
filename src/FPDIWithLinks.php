<?php
declare(strict_types=1);

namespace MZ\FPDI;

use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParser;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfArray;
use setasign\Fpdi\PdfParser\Type\PdfDictionary;
use setasign\Fpdi\PdfParser\Type\PdfName;
use setasign\Fpdi\PdfParser\Type\PdfNumeric;
use setasign\Fpdi\PdfParser\Type\PdfType;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PageBoundaries;
use setasign\Fpdi\PdfReader\PdfReaderException;
use setasign\Fpdi\Tcpdf\Fpdi;

class FPDIWithLinks extends Fpdi
{
    /**
     * @param int    $pageNumber
     * @param string $box
     * @param bool   $groupXObject
     *
     * @return string
     * @throws FilterException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws PdfReaderException
     *
     * @throws CrossReferenceException
     */
    public function importPage($pageNumber, $box = PageBoundaries::CROP_BOX, $groupXObject = true): string
    {
        $pageId = parent::importPage($pageNumber, $box, $groupXObject);
        $readerId = $this->importedPages[$pageId]['readerId'];
        $reader = $this->getPdfReader($readerId);
        $parser = $reader->getParser();

        $page = $reader->getPage($pageNumber);
        $annotations = $page->getAttribute('Annots');

        if (!$annotations) {
            return $pageId;
        }

        $annotations = static::getAnnotations(
            $parser,
            PdfType::resolve($annotations, $parser)
        );

        if ($annotations) {
            $height = $page->getWidthAndHeight()[1];

            $this->importedPages[$pageId]['links'] = static::getLinks($parser, $annotations, $this->k, $height);
        }

        return $pageId;
    }

    /**
     * @param mixed $tpl
     * @param int   $x
     * @param int   $y
     * @param null  $width
     * @param null  $height
     * @param bool  $adjustPageSize
     *
     * @return array
     */
    public function useTemplate($tpl, $x = 0, $y = 0, $width = null, $height = null, $adjustPageSize = false): array
    {
        $result = parent::useTemplate(
            $tpl,
            $x,
            $y,
            $width,
            $height,
            $adjustPageSize
        );

        foreach ($this->importedPages[$tpl]['links'] ?? [] as $link) {
            /* @var Link $link */
            $this->Link(...$link->toArray());
        }

        return $result;
    }

    /**
     * @param PdfParser $parser
     * @param array     $annotations
     * @param float     $scaleFactor
     * @param float     $pageFormatHeight
     *
     * @return Link[]
     */
    private static function getLinks(
        PdfParser $parser,
        array $annotations,
        float $scaleFactor,
        float $pageFormatHeight
    ): array {
        $filteredLinks = array_filter($annotations, [__CLASS__, 'annotationIsLink']);

        return array_map(
            static function (PdfDictionary $linkDescription) use ($parser, $scaleFactor, $pageFormatHeight) {
                return static::wrapLinksWithObject($linkDescription, $parser, $scaleFactor, $pageFormatHeight);
            },
            $filteredLinks
        );
    }

    /**
     * @param PdfType $annotation
     *
     * @return bool
     */
    private static function annotationIsLink(PdfType $annotation): bool
    {
        return
            $annotation instanceof PdfDictionary
            && $annotation->value['Type'] instanceof PdfName
            && 'Annot' === $annotation->value['Type']->value
            && $annotation->value['Subtype'] instanceof PdfName
            && 'Link' === $annotation->value['Subtype']->value
            && $annotation->value['A'] instanceof PdfType
            && $annotation->value['Rect'] instanceof PdfType;
    }

    /**
     * @param PdfDictionary $linkDescription
     * @param PdfParser     $parser
     * @param float         $scaleFactor
     * @param float         $pageFormatHeight
     *
     * @return Link
     *
     * @throws CrossReferenceException
     * @throws PdfParserException
     */
    private static function wrapLinksWithObject(
        PdfDictionary $linkDescription,
        PdfParser $parser,
        float $scaleFactor,
        float $pageFormatHeight
    ): Link {
        $rect = array_map(
            static function (PdfNumeric $value) {
                return $value->value;
            },
            PdfType::resolve($linkDescription->value['Rect'], $parser)->value
        );
        $linkA = PdfType::resolve($linkDescription->value['A'], $parser);
        $uri = PdfType::resolve($linkA->value['URI'], $parser)->value;

        return new Link($uri, $scaleFactor, $pageFormatHeight, ...$rect);
    }

    /**
     * @param PdfParser $parser
     * @param PdfType   $annotations
     *
     * @return array
     */
    private static function getAnnotations(PdfParser $parser, ?PdfType $annotations): array
    {
        $result = [];

        if ($annotations instanceof PdfArray && $annotations->value) {
            $result = array_map(
                static function (PdfType $object) use ($parser) {
                    return PdfType::resolve($object, $parser);
                },
                $annotations->value
            );

            $result = array_filter(
                $result,
                static function (PdfType $object) {
                    return !empty($object->value);
                }
            );
        }

        if ($result instanceof PdfArray) {
            $result = $annotations->value;
        }

        return $result;
    }
}
