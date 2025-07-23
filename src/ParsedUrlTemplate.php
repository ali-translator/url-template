<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\Exceptions\InvalidDefaultParamNameException;

class ParsedUrlTemplate
{
    protected ?string $patternedHost;
    protected ?string $patternedPath;

    /**
     * @var string[]
     */
    protected array $parameters;

    /**
     * @var string[]
     */
    protected array $compiledDefaultParametersValue;

    protected UrlTemplateConfig $urlTemplateConfig;

    /**
     * @var string[]
     */
    protected array $additionalUrlData;

    /**
     * @param string[] $parameters
     * @param string[] $additionalUrlData
     */
    public function __construct(
        ?string $patternedHost,
        ?string $patternedPath,
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

    public function getPatternedHost(): ?string
    {
        return $this->patternedHost;
    }

    public function getPatternedPath(): ?string
    {
        return $this->patternedPath;
    }

    public function getParameter(string $parameterName): ?string
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
    public function getOwnParameters(): array
    {
        return $this->parameters;
    }

    public function getOwnDefaultParameters(): array
    {
        $defaultParameters = $this->compiledDefaultParametersValue;

        return array_filter(
            $this->parameters,
            fn($ownParameterValue, $parameterName) =>
                isset($defaultParameters[$parameterName]) && $defaultParameters[$parameterName] === $ownParameterValue,
            ARRAY_FILTER_USE_BOTH
        );
    }

    public function getExcessiveOwnParameters(): array
    {
        return array_filter(
            $this->getOwnDefaultParameters(),
            fn($parameterName) => $this->urlTemplateConfig->isHiddenParameter($parameterName),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @return string[]
     */
    public function getFullParameters(): array
    {
        return $this->parameters + $this->compiledDefaultParametersValue;
    }

    /**
     * @return string[]
     */
    public function getDecoratedFullParameters(): array
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

    public function unsetParameter(string $parameterName): void
    {
        unset($this->parameters[$parameterName]);
    }

    public function setParameter(string $parameterName, string $parameterValue): void
    {
        $this->parameters[$parameterName] = $parameterValue;
    }

    /**
     * @param string[] $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function getUrlTemplateConfig(): UrlTemplateConfig
    {
        return $this->urlTemplateConfig;
    }

    /**
     * @return string[]
     */
    public function getAdditionalUrlData(): array
    {
        return $this->additionalUrlData;
    }

    /**
     * @return string[]
     * @throws InvalidDefaultParamNameException
     */
    public function getActualHiddenUrlParameters(): array
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
