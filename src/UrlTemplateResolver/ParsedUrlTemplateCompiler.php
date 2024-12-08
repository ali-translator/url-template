<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Exceptions\MissingRequiredUrlParametersException;
use ALI\UrlTemplate\Helpers\UrlPartsConverter;
use ALI\UrlTemplate\ParsedUrlTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;

class ParsedUrlTemplateCompiler
{
    protected UrlPartsConverter $urlPartsConverter;

    public function __construct(UrlPartsConverter $urlPartsConverter)
    {
        $this->urlPartsConverter = $urlPartsConverter;
    }

    public function compileUrl(
        ParsedUrlTemplate $parsedUrlTemplate,
        UrlTemplateConfig $urlTemplateConfig,
        string            $compileType = CompileType::COMPILE_TYPE_ALL
    ): string
    {
        $urlData = $parsedUrlTemplate->getAdditionalUrlData();

        $decoratedFullParameters = $parsedUrlTemplate->getDecoratedFullParameters();
        $parameterNamesWhichHideOnUrl = $parsedUrlTemplate->getActualHiddenUrlParameters();

        $templateUrlHost = $parsedUrlTemplate->getPatternedHost() ?: $urlTemplateConfig->getHostUrlTemplate();
        if ($templateUrlHost && in_array($compileType, [CompileType::COMPILE_TYPE_ALL, CompileType::COMPILE_TYPE_HOST, CompileType::COMPILE_TYPE_HOST_WITH_SCHEME])) {
            $urlHost = $this->compileUrlHost($parameterNamesWhichHideOnUrl, $urlTemplateConfig, $templateUrlHost, $decoratedFullParameters);

            if ($urlHost) {
                $urlData['host'] = $urlHost;
                if (!empty($urlData['host'])
                    && empty($urlData['scheme'])
                    && $urlTemplateConfig->getDefaultUrlSchema()
                ) {
                    // Use default schema
                    $urlData['scheme'] = $urlTemplateConfig->getDefaultUrlSchema();
                }
            }

            if ($compileType === CompileType::COMPILE_TYPE_HOST) {
                return $urlHost;
            }
            if ($compileType === CompileType::COMPILE_TYPE_HOST_WITH_SCHEME) {
                return $this->urlPartsConverter->buildUrlHostFromParseUrlParts($urlData);
            }
        }

        $templatedUrlPath = $parsedUrlTemplate->getPatternedPath();
        if ($templatedUrlPath && ($compileType === CompileType::COMPILE_TYPE_ALL || $compileType === CompileType::COMPILE_TYPE_PATH)) {
            $urlPath = $this->compileUrlPath($parameterNamesWhichHideOnUrl, $urlTemplateConfig, $templatedUrlPath, $decoratedFullParameters);
            if ($urlPath) {
                $urlData['path'] = $urlPath;
            }

            if ($compileType === CompileType::COMPILE_TYPE_PATH) {
                unset($urlData['scheme']);
            }
        }

        return $this->urlPartsConverter->buildUrlFromParseUrlParts($urlData);
    }

    protected function compileUrlHost(
        array             $parameterNamesWhichHideOnUrl,
        UrlTemplateConfig $urlTemplateConfig,
        string            $templateUrlHost,
        array             $decoratedFullParameters
    ): string
    {
        foreach ($parameterNamesWhichHideOnUrl as $parameterNameForRemoving) {
            $templateUrlHost = $urlTemplateConfig->getTextTemplate()->resolveParameters($templateUrlHost, [$parameterNameForRemoving => null]);
        }
        $templateUrlHost = preg_replace('/\.{2,}/', '.', $templateUrlHost);
        $templateUrlHost = str_replace('..', ',', $templateUrlHost);

        // Check host parameters existing
        $hostTemplateParameters = $urlTemplateConfig->getHostUrlParameters();
        $missingHostParameters = array_diff($hostTemplateParameters, array_keys($decoratedFullParameters));
        if ($missingHostParameters) {
            throw new MissingRequiredUrlParametersException('Missing required host url parameters: "' . implode(', ', $missingHostParameters) . '"');
        }

        return $urlTemplateConfig->getTextTemplate()->resolveParameters($templateUrlHost, $decoratedFullParameters);
    }

    protected function compileUrlPath(
        array             $parameterNamesWhichHideOnUrl,
        UrlTemplateConfig $urlTemplateConfig,
        string            $templatedUrlPath,
        array             $decoratedFullParameters
    ): string
    {
        foreach ($parameterNamesWhichHideOnUrl as $parameterNameForRemoving) {
            $templatedUrlPath = $urlTemplateConfig->getTextTemplate()->resolveParameters($templatedUrlPath, [$parameterNameForRemoving => null]);
        }

        // Check path parameters existing
        $pathTemplateParameters = $urlTemplateConfig->getPathUrlParameters();
        $missingPathParameters = array_diff($pathTemplateParameters, array_keys($decoratedFullParameters));
        if ($missingPathParameters) {
            throw new MissingRequiredUrlParametersException('Missing required path url parameters: "' . implode(', ', $missingPathParameters) . '"');
        }

        $templatedUrlPath = preg_replace('/\/{2,}/', '/', $templatedUrlPath);

        return $urlTemplateConfig->getTextTemplate()->resolveParameters($templatedUrlPath, $decoratedFullParameters);
    }
}