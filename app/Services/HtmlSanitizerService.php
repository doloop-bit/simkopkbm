<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HtmlSanitizerService
{
    private static ?HtmlSanitizer $sanitizer = null;

    public static function clean(?string $html): string
    {
        if (empty($html)) {
            return '';
        }

        if (self::$sanitizer === null) {
            $config = (new HtmlSanitizerConfig)
                ->allowSafeElements()
                ->allowRelativeMedias()
                ->allowElement('img', ['src', 'alt', 'title', 'width', 'height', 'class'])
                ->allowElement('table', ['class', 'border'])
                ->allowElement('thead', ['class'])
                ->allowElement('tbody', ['class'])
                ->allowElement('tr', ['class'])
                ->allowElement('th', ['class', 'colspan', 'rowspan', 'scope'])
                ->allowElement('td', ['class', 'colspan', 'rowspan'])
                ->allowElement('span', ['class', 'data-latex'])
                ->allowElement('div', ['class', 'data-latex'])
                ->allowElement('sub', [])
                ->allowElement('sup', [])
                ->allowElement('u', [])
                ->allowAttribute('class', ['img', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'span', 'div']);

            self::$sanitizer = new HtmlSanitizer($config);
        }

        return self::$sanitizer->sanitize($html);
    }
}
