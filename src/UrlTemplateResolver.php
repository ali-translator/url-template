<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\TextTemplate\UrlPartTextTemplate;
use ALI\UrlTemplate\UrlTemplateResolver\UrlPartParser;

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
     * @param UrlTemplateConfig $urlTemplateConfig
     */
    public function __construct(UrlTemplateConfig $urlTemplateConfig)
    {
        $this->urlTemplateConfig = $urlTemplateConfig;
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

    /**
     * @param ParsedUrlTemplate $parsedUrlTemplate
     * @return string
     */
    public function compileUrl($parsedUrlTemplate)
    {
        $urlData = $parsedUrlTemplate->getAdditionalUrlData();
        $urlData['host'] = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($parsedUrlTemplate->getPatternedHost(), $parsedUrlTemplate->getParameters());
        $urlData['path'] = $this->urlTemplateConfig->getTextTemplate()->resolveParameters($parsedUrlTemplate->getPatternedPath(), $parsedUrlTemplate->getParameters());

        return $this->buildUrlFromParseUrlParts($urlData);
    }

    /**
     * @param $simplifiedUrl
     * @param $parameters
     * @param bool $skipDefaultValuesFromUrl
     * @return ParsedUrlTemplate
     */
    public function generateParsedUrlTemplate($simplifiedUrl, $parameters, $skipDefaultValuesFromUrl = true)
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
            if ($skipDefaultValuesFromUrl) {
                $optionalityParameters = $this->urlTemplateConfig->getHostOptionalityParameters();
                foreach ($optionalityParameters as $optionalityParameterName) {
                    if (empty($parameters[$optionalityParameterName]) || $parameters[$optionalityParameterName] === $this->urlTemplateConfig->getParametersDefaultValue()[$optionalityParameterName]) {
                        $urlPartTemplateForReplacing = $urlPartTextTemplate->removeParameter($optionalityParameterName, $urlPartTemplateForReplacing, $urlPartType);
                    }
                }
            }

            $patternedHost = str_replace($simplifiedUrlPartTemplate, $urlPartTemplateForReplacing, $patternedHost);
        }

        $patternedPath = !empty($urlData['path']) ? $urlData['path'] : null;
        if ($patternedPath) {
            $urlPartTemplate = $this->urlTemplateConfig->getPathUrlTemplate();
            $simplifiedUrlPartTemplate = $urlPartTemplate;
            $urlPartParameters = $this->urlTemplateConfig->getPathUrlParameters();
            $urlPartType = UrlPartType::TYPE_PATH;
            foreach ($urlPartParameters as $parameterName) {
                $simplifiedUrlPartTemplate = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedUrlPartTemplate, $urlPartType);
            }

            $urlPartTemplateForReplacing = $urlPartTemplate;
            if ($skipDefaultValuesFromUrl) {
                $optionalityParameters = $this->urlTemplateConfig->getPathOptionalityParameters();
                foreach ($optionalityParameters as $optionalityParameterName) {
                    if (empty($parameters[$optionalityParameterName]) || $parameters[$optionalityParameterName] === $this->urlTemplateConfig->getParametersDefaultValue()[$optionalityParameterName]) {
                        $urlPartTemplateForReplacing = $urlPartTextTemplate->removeParameter($optionalityParameterName, $urlPartTemplateForReplacing, $urlPartType);
                    }
                }
            }

            if ($simplifiedUrlPartTemplate && $simplifiedUrlPartTemplate != '/') {
                $patternedPath = str_replace($simplifiedUrlPartTemplate, $urlPartTemplateForReplacing, $patternedPath);
            } else {
                $patternedPath = rtrim($urlPartTemplateForReplacing, '/') . $patternedPath;
            }
        }

        return new ParsedUrlTemplate($patternedHost, $patternedPath, $parameters, $this->urlTemplateConfig, $urlData);
    }

    /**
     * @param ParsedUrlTemplate $parsedUrlTemplate
     * @return string
     */
    public function getSimplifiedUrl($parsedUrlTemplate)
    {
        $urlPartTextTemplate = new UrlPartTextTemplate($this->urlTemplateConfig->getTextTemplate());

        $patternedHost = $parsedUrlTemplate->getPatternedHost();
        $hostUrlParameters = $parsedUrlTemplate->getUrlTemplateConfig()->getHostUrlParameters();
        $simplifiedHost = $patternedHost;
        if ($patternedHost && $hostUrlParameters) {
            foreach ($hostUrlParameters as $parameterName) {
                $simplifiedHost = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedHost, UrlPartType::TYPE_HOST);
            }
        }

        $pathUrlParameters = $parsedUrlTemplate->getUrlTemplateConfig()->getPathUrlParameters();
        $patternedPath = $parsedUrlTemplate->getPatternedPath();
        $simplifiedPath = $patternedPath;
        if ($patternedPath && $pathUrlParameters) {
            foreach ($pathUrlParameters as $parameterName) {
                $simplifiedPath = $urlPartTextTemplate->removeParameter($parameterName, $simplifiedPath, UrlPartType::TYPE_PATH);
            }
        }

        $urlData = $parsedUrlTemplate->getAdditionalUrlData();
        $urlData['host'] = $simplifiedHost;
        $urlData['path'] = $simplifiedPath;

        return $this->buildUrlFromParseUrlParts($urlData);
    }

    /**
     * @param array $urlFragments
     * @return string
     */
    protected function buildUrlFromParseUrlParts(array $urlFragments)
    {
        return (isset($urlFragments['scheme']) ? $urlFragments['scheme'] . ":" : '') .
            ((isset($urlFragments['user']) || isset($urlFragments['host'])) ? '//' : '') .
            (isset($urlFragments['user']) ? $urlFragments['user'] : '') .
            (isset($urlFragments['pass']) ? ":" . $urlFragments['pass'] : '') .
            (isset($urlFragments['user']) ? '@' : '') .
            (isset($urlFragments['host']) ? $urlFragments['host'] : '') .
            (isset($urlFragments['port']) ? ":" . $urlFragments['port'] : '') .
            (isset($urlFragments['path']) ? $urlFragments['path'] : '') .
            (!empty($urlFragments['query']) ? "?" . $urlFragments['query'] : '') .
            (isset($urlFragments['fragment']) ? "#" . $urlFragments['fragment'] : '');
    }
}
