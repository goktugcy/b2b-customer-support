<?php

namespace App\Services\Content;

use HTMLPurifier;
use HTMLPurifier_Config;

class HtmlSanitizer
{
    private HTMLPurifier $purifier;

    public function __construct()
    {
        $config = HTMLPurifier_Config::createDefault();
        $config->set('Core.Encoding', 'UTF-8');
        $cachePath = storage_path('framework/cache/htmlpurifier');

        if (! is_dir($cachePath)) {
            @mkdir($cachePath, 0775, true);
        }

        $config->set('Cache.SerializerPath', $cachePath);
        $config->set('HTML.Allowed', implode(',', [
            'p',
            'br',
            'strong',
            'b',
            'em',
            'i',
            'u',
            's',
            'ul',
            'ol',
            'li',
            'blockquote',
            'pre',
            'code',
            'h2',
            'h3',
            'a[href|target|rel]',
        ]));
        $config->set('Attr.AllowedFrameTargets', ['_blank']);
        $config->set('URI.AllowedSchemes', [
            'http' => true,
            'https' => true,
            'mailto' => true,
        ]);
        $config->set('AutoFormat.RemoveEmpty', true);

        $this->purifier = new HTMLPurifier($config);
    }

    public function sanitize(?string $html): string
    {
        return trim($this->purifier->purify($html ?? ''));
    }
}
