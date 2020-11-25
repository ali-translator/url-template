<?php

namespace ALI\UrlTemplate;

use Exception;

/**
 * Class
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
     * @var static[]
     */
    protected $compiledDefaultParametersValue;

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

        $this->compiledDefaultParametersValue = $this->urlTemplateConfig->getCompiledDefaultParametersValue($this->parameters);

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
     * @throws Exception
     */
    public function getParameter($parameterName)
    {
        $parameters = $this->getFullParameters();
        if (array_key_exists($parameterName, $parameters)) {
            return $parameters[$parameterName];
        }

        return null;
    }

    /**
     * @return string[]
     */
    public function getOwnParameters()
    {
        return $this->parameters;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getOwnDefaultParameters()
    {
        $defaultParameters = $this->compiledDefaultParametersValue;

        $defaultOwnParameters = [];
        foreach ($this->parameters as $parameterName => $ownParameterValue) {
            if (isset($defaultParameters[$parameterName]) && $defaultParameters[$parameterName] === $ownParameterValue) {
                $defaultOwnParameters[$parameterName] = $ownParameterValue;
            }
        }

        return $defaultOwnParameters;
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getExcessiveOwnParameters()
    {
        $defaultOwnParameters = $this->getOwnDefaultParameters();

        $excessiveOwnParameters = [];
        foreach ($defaultOwnParameters as $parameterName => $parameterValue) {
            if ($this->urlTemplateConfig->isHiddenParameter($parameterName)) {
                $excessiveOwnParameters[$parameterName] = $parameterValue;
            }
        }

        return $excessiveOwnParameters;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getFullParameters()
    {
        return $this->parameters + $this->compiledDefaultParametersValue;
    }

    /**
     * @return string[]
     * @throws Exception
     */
    public function getDecoratedFullParameters()
    {
        $fullParameters = $this->getFullParameters();
        foreach ($fullParameters as $parameterName => $parameterValue) {
            $decorator = $this->urlTemplateConfig->getParameterDecorator($parameterName);
            if ($decorator) {
                $fullParameters[$parameterName] = $decorator->generate($parameterValue);
            }
        }

        return $fullParameters;
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

    /**
     * @throws Exception
     */
    public function getActualHiddenUrlParameters()
    {
        $parameterNamesWhichHideOnUrl = [];
        foreach ($this->urlTemplateConfig->getHideDefaultParametersFromUrl() as $hiddenParameterName) {
            $defaultParameterValue = $this->urlTemplateConfig->getCompiledDefaultParameterValueItem($hiddenParameterName, $this->getOwnParameters());
            $parsedUrlTemplateParameterValue = $this->getParameter($hiddenParameterName);
            if ($parsedUrlTemplateParameterValue === $defaultParameterValue) {
                $parameterNamesWhichHideOnUrl[] = $hiddenParameterName;
            }
        }

        return $parameterNamesWhichHideOnUrl;
    }
}
