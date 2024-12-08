<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\ParsedUrlTemplate;

class ParsedUrlTemplateToSimplifiedUrlDataConverter
{
    /**
     * @return string[]
     */
    public function getSimplifiedUrlData(
        ParsedUrlTemplate $parsedUrlTemplate
    ): array
    {
        $urlData = $parsedUrlTemplate->getAdditionalUrlData();
        $urlData['host'] = $this->generateSimplifiedHost($parsedUrlTemplate);
        $urlData['path'] = $this->generateSimplifiedPath($parsedUrlTemplate);

        return $urlData;
    }

    public function generateSimplifiedHost(ParsedUrlTemplate $parsedUrlTemplate): string
    {
        $patternedTemplateHost = $parsedUrlTemplate->getUrlTemplateConfig()->getHostUrlTemplate();
        $patternedHost = $parsedUrlTemplate->getPatternedHost();
        $simplifiedHost = str_replace($patternedTemplateHost, null, $patternedHost);
        if (!$simplifiedHost || $simplifiedHost[0] !== '.') {
            $simplifiedHost = '.' . $simplifiedHost;
        }

        return $simplifiedHost;
    }

    public function generateSimplifiedPath(ParsedUrlTemplate $parsedUrlTemplate): string
    {
        $patternedTemplatePath = $parsedUrlTemplate->getUrlTemplateConfig()->getPathUrlTemplate();
        $patternedPath = $parsedUrlTemplate->getPatternedPath();
        $simplifiedPath = str_replace($patternedTemplatePath, null, $patternedPath);
        if (!$simplifiedPath || $simplifiedPath[0] !== '/') {
            $simplifiedPath = '/' . $simplifiedPath;
        }

        return $simplifiedPath;
    }
}