<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Common\Plugin\Imagine\Filter\Basic;

use Common\Plugin\Imagine\Image\ImageInterface;
use Common\Plugin\Imagine\Image\Palette\Color\ColorInterface;
use Common\Plugin\Imagine\Filter\FilterInterface;

/**
 * A rotate filter
 */
class Rotate implements FilterInterface
{
    /**
     * @var integer
     */
    private $angle;

    /**
     * @var ColorInterface
     */
    private $background;

    /**
     * Constructs Rotate filter with given angle and background color
     *
     * @param integer        $angle
     * @param ColorInterface $background
     */
    public function __construct($angle, ColorInterface $background = null)
    {
        $this->angle      = $angle;
        $this->background = $background;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(ImageInterface $image)
    {
        return $image->rotate($this->angle, $this->background);
    }
}
