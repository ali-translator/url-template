<?php

namespace ALI\UrlTemplate\Helpers;

/**
 * Class
 */
class UrlPartsConverter
{
    /**
     * @param $url
     * @return string[]
     */
    public function parse($url)
    {
        return parse_url($url);
    }

    /**
     * @param array $urlFragments
     * @return string
     */
    public function buildUrlFromParseUrlParts(array $urlFragments)
    {
        return $this->buildUrlHostFromParseUrlParts($urlFragments) . $this->buildUrlPathFromParseUrlParts($urlFragments);
    }

    /**
     * @param array $urlFragments
     * @return string
     */
    public function buildUrlHostFromParseUrlParts(array $urlFragments)
    {
        return (isset($urlFragments['scheme']) ? $urlFragments['scheme'] . ":" : '') .
            ((isset($urlFragments['user']) || isset($urlFragments['host'])) ? '//' : '') .
            (isset($urlFragments['user']) ? $urlFragments['user'] : '') .
            (isset($urlFragments['pass']) ? ":" . $urlFragments['pass'] : '') .
            (isset($urlFragments['user']) ? '@' : '') .
            (isset($urlFragments['host']) ? $urlFragments['host'] : '') .
            (isset($urlFragments['port']) ? ":" . $urlFragments['port'] : '');
    }

    /**
     * @param array $urlFragments
     * @return string
     */
    public function buildUrlPathFromParseUrlParts(array $urlFragments)
    {
        return (isset($urlFragments['path']) ? $urlFragments['path'] : '') .
            (!empty($urlFragments['query']) ? "?" . $urlFragments['query'] : '') .
            (isset($urlFragments['fragment']) ? "#" . $urlFragments['fragment'] : '');
    }
}
