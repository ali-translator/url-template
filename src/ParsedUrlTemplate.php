<?php

namespace ALI\UrlTemplate;

/**
 * Class
 *
 * // TODO get simplified url ?
 */
class ParsedUrlTemplate
{
    /**
     * @var string
     */
    protected $patternedHost;

    /**
     * @var string
     */
    protected $patternedPath;

    /**
     * @var string[]
     */
    protected $parameters;

    /**
     * @var UrlTemplateConfig
     */
    protected $urlTemplateConfig;

    /**
     * @var string[]
     */
    protected $additionalUrlData;

    /**
     * @param string $patternedHost
     * @param string $patternedPath
     * @param string[] $parameters
     * @param UrlTemplateConfig $urlTemplateConfig
     * @param string[] $additionalUrlData
     */
    public function __construct(
        $patternedHost,
        $patternedPath,
        array $parameters,
        UrlTemplateConfig $urlTemplateConfig,
        array $additionalUrlData = []
    )
    {
        $this->patternedHost = $patternedHost;
        $this->patternedPath = $patternedPath;
        $this->parameters = $parameters;
        $this->urlTemplateConfig = $urlTemplateConfig;

        unset($additionalUrlData['host']);
        unset($additionalUrlData['path']);
        $this->additionalUrlData = $additionalUrlData;
    }

    /**
     * @return string
     */
    public function getPatternedHost()
    {
        return $this->patternedHost;
    }

    /**
     * @return string
     */
    public function getPatternedPath()
    {
        return $this->patternedPath;
    }

    /**
     * @param string $parameterName
     * @return string|null
     */
    public function getParameter($parameterName)
    {
        $parameters = $this->getParameters();
        if (array_key_exists($parameterName, $parameters)) {
            return $parameters[$parameterName];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getParameters()
    {
        return $this->parameters + $this->urlTemplateConfig->getParametersDefaultValue();
    }


    /**
     * @param string $parameterName
     * @param string $parameterValue
     */
    public function setParameter($parameterName, $parameterValue)
    {
        $this->parameters[$parameterName] = $parameterValue;
    }

    /**
     * @param string[] $parameters
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @return UrlTemplateConfig
     */
    public function getUrlTemplateConfig()
    {
        return $this->urlTemplateConfig;
    }

    /**
     * @return string[]
     */
    public function getAdditionalUrlData()
    {
        return $this->additionalUrlData;
    }
}
