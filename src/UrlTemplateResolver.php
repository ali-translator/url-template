<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\Exceptions\MissingRequiredUrlParametersException;
use ALI\UrlTemplate\Helpers\UrlPartsConverter;
use ALI\UrlTemplate\TextTemplate\UrlPartTextTemplate;
use ALI\UrlTemplate\UrlTemplateResolver\UrlPartParser;
use Exception;

/**
 * Class
 */
class UrlTemplateResolver
{
    /**
     * @var UrlTemplateConfig
     */
    protected $urlTemplateConfig;

    /**
     * @var UrlPartsConverter
     */
    protected $urlPartsConverter;

    /**
     * @param UrlTemplateConfig $urlTemplateConfig
     */
    public function __construct(UrlTemplateConfig $urlTemplateConfig)
    {
        $this->urlTemplateConfig = $urlTemplateConfig;
        $this->urlPartsConverter = new UrlPartsConverter();
    }

    /**
     * @return UrlTemplateConfig
     */
    public function getUrlTemplateConfig()
    {
        return $this->urlTemplateConfig;
    }

    /**
     * @return UrlPartsConverter
     */
    public function getUrlPartsConverter()
    {
        return $this->urlPartsConverter;
    }

    /**
     * @param $compiledUrl
     * @return ParsedUrlTemplate
     * @throws InvalidUrlException
     */
    public function parseCompiledUrl($compiledUrl)
    {
        $urlPartParser = new UrlPartParser($this->urlTemplateConfig);
        $urlData = parse_url($compiledUrl);

        // Resolve host parameters
        $hostUrlParameters = $this->urlTemplateConfig->getHostUrlParameters();
        if (empty($urlData['host'])) {
            $patternedHost = '';
            $hostParametersValue = [];
        } elseif (empty($hostUrlParameters)) {
            $patternedHost = $urlData['host'];
            $hostParametersValue = [];
        } else {
            list($patternedHost, $hostParametersValue) = $urlPartParser->parse(UrlPartType::TYPE_HOST, $urlData['host'], $hostUrlParameters);
        }

        // Resolve path parameters
        $pathUrlParameters = $this->urlTemplateConfig->getPathUrlParameters();
        if (empty($urlData['path'])) {
            $patternedUrlPath = '';
            $urlPathParametersValue = [];
        } elseif (empty($pathUrlParameters)) {
            $patternedUrlPath = $urlData['path'];
            $urlPathParametersValue = [];
        } else {
            list($patternedUrlPath, $urlPathParametersValue) = $urlPartParser->parse(UrlPartType::TYPE_PATH, $urlData['path'], $pathUrlParameters);
        }

        $parameters = $hostParametersValue + $urlPathParametersValue;

        return new ParsedUrlTemplate($patternedHost, $patternedUrlPath, $parameters, $this->urlTemplateConfig, $urlData);
    }

    const COMPILE_TYPE_ALL = 'all';
    const COMPILE_TYPE_HOST = 'host';
    const COMPILE_TYPE_HOST_WITH_SCHEME = 'hostWithScheme';
    const COMPILE_TYPE_PATH = 'path';

    /**
     * @param ParsedUrlTemplate $parsedUrlTemplate
     * @param string $compileType
     * @return string
     * @throws Exception
     */
    public function compileUrl($parsedUrlTemplate, $compileType = self::COMPILE_TYPE_ALL)
    {
        $urlData = $parsedUrlTemplate->getAdditionalUrlData();

        $decoratedFullParameters = $parsedUrlTemplate->getDecoratedFullParameters();
        $parameterNamesWhichHideOnUrl = $parsedUrlTemplate->getActualHiddenUrlParameters();

        $urlHost = $parsedUrlTemplate->getPatternedHost();
        if ($urlHost && in_array($compileType, [self::COMPILE_TYPE_ALL, self::COMPILE_TYPE_HOST, self::COMPILE_TYPE_HOST_WITH_SCHEME])) {
            foreach ($parameterNamesWhichHideOnUrl as $parameterNameForRemoving) {
                $urlHost = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($urlHost, [$parameterNameForRemoving => null]);
            }
            $urlHost = preg_replace('/\.{2,}/', '.', $urlHost);
            $urlHost = str_replace('..', ',', $urlHost);

            // Check host parameters existing
            $hostTemplateParameters = $this->urlTemplateConfig->getHostUrlParameters();
            $missingHostParameters = array_diff($hostTemplateParameters, array_keys($decoratedFullParameters));
            if ($missingHostParameters) {
                throw new MissingRequiredUrlParametersException('Missing required host url parameters: "' . implode(', ', $missingHostParameters) . '"');
            }

            $urlHost = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($urlHost, $decoratedFullParameters);

            if ($urlHost) {
                $urlData['host'] = $urlHost;
            }

            if ($compileType === self::COMPILE_TYPE_HOST) {
                return $urlHost;
            }
            if ($compileType === self::COMPILE_TYPE_HOST_WITH_SCHEME) {
                return $this->urlPartsConverter->buildUrlHostFromParseUrlParts($urlData);
            }
        }

        $urlPath = $parsedUrlTemplate->getPatternedPath();
        if ($urlPath && ($compileType === self::COMPILE_TYPE_ALL || $compileType === self::COMPILE_TYPE_PATH)) {
            foreach ($parameterNamesWhichHideOnUrl as $parameterNameForRemoving) {
                $urlPath = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($urlPath, [$parameterNameForRemoving => null]);
            }

            // Check path parameters existing
            $pathTemplateParameters = $this->urlTemplateConfig->getPathUrlParameters();
            $missingPathParameters = array_diff($pathTemplateParameters, array_keys($decoratedFullParameters));
            if ($missingPathParameters) {
                throw new MissingRequiredUrlParametersException('Missing required path url parameters: "' . implode(', ', $missingPathParameters) . '"');
            }

            $urlPath = preg_replace('/\/{2,}/', '/', $urlPath);
            $urlPath = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($urlPath, $decoratedFullParameters);
            if ($urlPath) {
                $urlData['path'] = $urlPath;
            }

            if ($compileType === self::COMPILE_TYPE_PATH) {
                unset($urlData['scheme']);
            }
        }

        return $this->urlPartsConverter->buildUrlFromParseUrlParts($urlData);
    }

    /**
     * @param $simplifiedUrl
     * @param $parameters
     * @return ParsedUrlTemplate
     * @throws Exception
     */
    public function generateParsedUrlTemplate($simplifiedUrl, $parameters)
    {
        $urlData = parse_url($simplifiedUrl);
        $urlPartTextTemplate = new UrlPartTextTemplate($this->urlTemplateConfig->getTextTemplate());

        $patternedHost = !empty($urlData['host']) ? $urlData['host'] : null;
        if ($patternedHost) {
            $urlPartTemplate = $this->urlTemplateConfig->getHostUrlTemplate();
            $simplifiedUrlPartTemplate = $urlPartTemplate;
            $urlPartParameters = $this->urlTemplateConfig->getHostUrlParameters();
            $urlPartType = UrlPartType::TYPE_HOST;
            foreach ($urlPartParameters as $parameterName) {
                $simplifiedUrlPartTemplate = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedUrlPartTemplate, $urlPartType);
            }

            $urlPartTemplateForReplacing = $urlPartTemplate;
            $patternedHost = str_replace($simplifiedUrlPartTemplate, $urlPartTemplateForReplacing, $patternedHost);
        }

        $patternedPath = !empty($urlData['path']) ? $urlData['path'] : '/';
        if ($patternedPath) {
            $urlPartTemplate = $this->urlTemplateConfig->getPathUrlTemplate();
            $simplifiedUrlPartTemplate = $urlPartTemplate;
            $urlPartParameters = $this->urlTemplateConfig->getPathUrlParameters();
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

        return new ParsedUrlTemplate($patternedHost, $patternedPath, $parameters, $this->urlTemplateConfig, $urlData);
    }

    /**
     * @param ParsedUrlTemplate $parsedUrlTemplate
     * @return string[]
     */
    public function getSimplifiedUrlData($parsedUrlTemplate)
    {
        $patternedTemplateHost = $parsedUrlTemplate->getUrlTemplateConfig()->getHostUrlTemplate();
        $patternedHost = $parsedUrlTemplate->getPatternedHost();
        $simplifiedHost = str_replace($patternedTemplateHost, null, $patternedHost);
        if (!$simplifiedHost || $simplifiedHost[0] !== '.') {
            $simplifiedHost = '.' . $simplifiedHost;
        }

        $patternedTemplatePath = $parsedUrlTemplate->getUrlTemplateConfig()->getPathUrlTemplate();
        $patternedPath = $parsedUrlTemplate->getPatternedPath();
        $simplifiedPath = str_replace($patternedTemplatePath, null, $patternedPath);
        if (!$simplifiedPath || $simplifiedPath[0] !== '/') {
            $simplifiedPath = '/' . $simplifiedPath;
        }

        $urlData = $parsedUrlTemplate->getAdditionalUrlData();
        $urlData['host'] = $simplifiedHost;
        $urlData['path'] = $simplifiedPath;

        return $urlData;
    }
}
