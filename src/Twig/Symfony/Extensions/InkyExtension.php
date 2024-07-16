<?php

namespace App\Twig\Symfony\Extensions;

use Pinky;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * This extension provides Twig filter `inline_css`
 * Normally this filter is provided by symfony itself but the twig package seems to be bugged
 * thus this filter must be provided manually
 */
class InkyExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('inky_to_html', [InkyExtension::class, 'twigInky']),
        ];
    }

    /**
     * @param string $body
     * @return string
     */
    public static function twigInky(string $body): string
    {
        return false === ($html = Pinky\transformString($body)->saveHTML()) ? '' : $html;
    }

}