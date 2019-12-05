<?php
declare(strict_types=1);

namespace MZ\FPDI;

class Link
{
    /**
     * @var float
     */
    private $x;

    /**
     * @var float
     */
    private $y;

    /**
     * @var float
     */
    private $w;

    /**
     * @var float
     */
    private $h;

    /**
     * @var string
     */
    private $link;

    /**
     * @var float
     */
    private $scaleFactor;

    /**
     * @param string $link
     * @param float  $scaleFactor
     * @param float  $pageFormatHeight
     * @param float  $x1
     * @param float  $y1
     * @param float  $x2
     * @param float  $y2
     */
    public function __construct(
        string $link,
        float $scaleFactor,
        float $pageFormatHeight,
        float $x1,
        float $y1,
        float $x2,
        float $y2
    ) {
        $this->scaleFactor = $scaleFactor;
        $this->link = $link;

        $this->x = $x1;
        $this->y = $pageFormatHeight - $y2;
        $this->w = abs($x2 - $x1);
        $this->h = abs($y2 - $y1);
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            $this->x / $this->scaleFactor,
            $this->y / $this->scaleFactor,
            $this->w / $this->scaleFactor,
            $this->h / $this->scaleFactor,
            $this->link
        ];
    }
}
