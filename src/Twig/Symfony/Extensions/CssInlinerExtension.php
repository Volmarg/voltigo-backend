<?php

namespace App\Twig\Symfony\Extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * This extension provides Twig filter `inline_css`
 * Normally this filter is provided by symfony itself but the twig package seems to be bugged
 * thus this filter must be provided manually
 */
class CssInlinerExtension extends AbstractExtension
{
    /**
     * @return TwigFilter[]
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('inline_css', 'Twig\\Extra\\CssInliner\\twig_inline_css', ['is_safe' => ['all']]),
        ];
    }
}