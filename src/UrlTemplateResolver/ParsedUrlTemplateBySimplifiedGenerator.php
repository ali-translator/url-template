<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\ParsedUrlTemplate;
use ALI\UrlTemplate\TextTemplate\UrlPartTextTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;

class ParsedUrlTemplateBySimplifiedGenerator
{
    public function generateParsedUrlTemplate(
        string $simplifiedUrl,
        UrlTemplateConfig $urlTemplateConfig,
        array $parameters = []
    ): ParsedUrlTemplate
    {
        $urlData = parse_url($simplifiedUrl);
        $urlPartTextTemplate = new UrlPartTextTemplate($urlTemplateConfig->getTextTemplate());

        $patternedHost = $this->generatePatternedHost($urlData, $urlTemplateConfig, $urlPartTextTemplate);
        $patternedPath = $this->generatePatternedPath($urlData, $urlTemplateConfig, $urlPartTextTemplate);

        return new ParsedUrlTemplate($patternedHost, $patternedPath, $parameters, $urlTemplateConfig, $urlData);
    }

    protected function generatePatternedHost(
        array $urlData,
        UrlTemplateConfig $urlTemplateConfig,
        UrlPartTextTemplate $urlPartTextTemplate
    ): ?string
    {
        $patternedHost = $urlData['host'] ?? null;
        if ($patternedHost) {
            $urlPartTemplate = $urlTemplateConfig->getHostUrlTemplate();
            $simplifiedUrlPartTemplate = $urlPartTemplate;
            $urlPartParameters = $urlTemplateConfig->getHostUrlParameters();
            $urlPartType = UrlPartType::TYPE_HOST;
            foreach ($urlPartParameters as $parameterName) {
                $simplifiedUrlPartTemplate = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedUrlPartTemplate, $urlPartType);
            }

            $urlPartTemplateForReplacing = $urlPartTemplate;
            $patternedHost = str_replace($simplifiedUrlPartTemplate, $urlPartTemplateForReplacing, $patternedHost);
        }

        return $patternedHost;
    }

    protected function generatePatternedPath(
        $urlData,
        UrlTemplateConfig $urlTemplateConfig,
        UrlPartTextTemplate $urlPartTextTemplate
    ): string
    {
        $patternedPath = $urlData['path'] ?? '/';
        if ($patternedPath) {
            $urlPartTemplate = $urlTemplateConfig->getPathUrlTemplate();
            $simplifiedUrlPartTemplate = $urlPartTemplate;
            $urlPartParameters = $urlTemplateConfig->getPathUrlParameters();
            $urlPartType = UrlPartType::TYPE_PATH;
            foreach ($urlPartParameters as $parameterName) {
                $simplifiedUrlPartTemplate = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedUrlPartTemplate, $urlPartType);
            }

            $urlPartTemplateForReplacing = $urlPartTemplate;
            if ($simplifiedUrlPartTemplate && $simplifiedUrlPartTemplate !== '/') {
                $patternedPath = str_replace($simplifiedUrlPartTemplate, $urlPartTemplateForReplacing, $patternedPath);
            } else {
                $patternedPath = rtrim($urlPartTemplateForReplacing, '/') . $patternedPath;
            }
        }

        return $patternedPath;
    }
}