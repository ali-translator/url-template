<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\Helpers\DuplicateParameterResolver;
use ALI\UrlTemplate\ParsedUrlTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;

class CompiledUrlParser
{
    protected UrlPartParser $urlPartParser;

    public function __construct(
        DuplicateParameterResolver $duplicateParameterResolver,
        ?UrlPartParser $urlPartParser = null
    )
    {
        $this->urlPartParser = $urlPartParser ?? new UrlPartParser($duplicateParameterResolver);
    }

    /**
     * @throws InvalidUrlException
     */
    public function parseCompiledUrl(
        string            $compiledUrl,
        UrlTemplateConfig $urlTemplateConfig
    ): ParsedUrlTemplate
    {
        $urlData = parse_url($compiledUrl);

        [$patternedHost, $hostParametersValue] = $this->resolveHostParameters($urlTemplateConfig, $urlData);
        [$patternedUrlPath, $urlPathParametersValue] = $this->resolvePathParameters($urlTemplateConfig, $urlData);

        $parameters = $hostParametersValue + $urlPathParametersValue;

        return new ParsedUrlTemplate($patternedHost, $patternedUrlPath, $parameters, $urlTemplateConfig, $urlData);
    }

    protected function resolveHostParameters(UrlTemplateConfig $urlTemplateConfig, $urlData): array
    {
        $hostConfigUrlParameters = $urlTemplateConfig->getHostUrlParameters();
        if (empty($urlData['host'])) {
            $patternedHost = '';
            $hostParametersValue = [];
        } elseif (empty($hostConfigUrlParameters)) {
            if (
                $urlTemplateConfig->getHostUrlTemplate()
                && $urlTemplateConfig->getHostUrlTemplate() !== $urlData['host']
            ) {
                throw new InvalidUrlException();
            }
            $patternedHost = $urlData['host'];
            $hostParametersValue = [];
        } else {
            [$patternedHost, $hostParametersValue] = $this->urlPartParser->parse(UrlPartType::TYPE_HOST, $urlData['host'], $hostConfigUrlParameters, $urlTemplateConfig);
        }

        return [$patternedHost, $hostParametersValue];
    }

    protected function resolvePathParameters(UrlTemplateConfig $urlTemplateConfig, $urlData): array
    {
        $pathConfigUrlParameters = $urlTemplateConfig->getPathUrlParameters();
        if (empty($urlData['path'])) {
            $patternedUrlPath = '';
            $urlPathParametersValue = [];
        } elseif (empty($pathConfigUrlParameters)) {
            if (
                $urlTemplateConfig->getPathUrlTemplate() !== '/'
                && strpos($urlData['path'], $urlTemplateConfig->getPathUrlTemplate()) !== 0
            ) {
                throw new InvalidUrlException();
            }
            $patternedUrlPath = $urlData['path'];
            $urlPathParametersValue = [];
        } else {
            [$patternedUrlPath, $urlPathParametersValue] = $this->urlPartParser->parse(UrlPartType::TYPE_PATH, $urlData['path'], $pathConfigUrlParameters, $urlTemplateConfig);
        }

        return [$patternedUrlPath, $urlPathParametersValue];
    }
}