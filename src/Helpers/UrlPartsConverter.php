<?php

namespace ALI\UrlTemplate\Helpers;

class UrlPartsConverter
{
    /**
     * @param string $url
     * @return array|false|int|string|null
     */
    public function parse(string $url)
    {
        return parse_url($url);
    }

    /**
     * @param array $urlFragments
     * @return string
     */
    public function buildUrlFromParseUrlParts(array $urlFragments): string
    {
        return $this->buildUrlHostFromParseUrlParts($urlFragments) . $this->buildUrlPathFromParseUrlParts($urlFragments);
    }

    public function buildUrlHostFromParseUrlParts(array $urlFragments): string
    {
        return (isset($urlFragments['scheme']) ? $urlFragments['scheme'] . ":" : '') .
            ((isset($urlFragments['user']) || isset($urlFragments['host'])) ? '//' : '') .
            ($urlFragments['user'] ?? '') .
            (isset($urlFragments['pass']) ? ":" . $urlFragments['pass'] : '') .
            (isset($urlFragments['user']) ? '@' : '') .
            ($urlFragments['host'] ?? '') .
            (isset($urlFragments['port']) ? ":" . $urlFragments['port'] : '');
    }

    public function buildUrlPathFromParseUrlParts(array $urlFragments): string
    {
        return ($urlFragments['path'] ?? '') .
            (!empty($urlFragments['query']) ? "?" . $urlFragments['query'] : '') .
            (isset($urlFragments['fragment']) ? "#" . $urlFragments['fragment'] : '');
    }
}
