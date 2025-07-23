<?php

namespace ALI\UrlTemplate\Units;

use ALI\UrlTemplate\ParameterDecorators\ParameterDecoratorInterface;
use ALI\UrlTemplate\TextTemplate\TextTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;

/**
 * Unit of values of @UrlTemplateConfig
 */
class UrlTemplateConfigData
{
    /**
     * @example "{country}.test.com"
     */
    protected ?string $domainUrlTemplate;

    /**
     * @example "/prefix/{language}/{city}"
     */
    protected ?string $pathUrlTemplate;

    /**
     * @example
     *      [
     *          'country' => '[a-z]{2,3}',
     *          'city' => '-0-9a-z]+',
     *      ]
     */
    protected array $parametersRequirements;

    /**
     * @example
     *      [
     *          'country' => 'gb',
     *          'language' => 'en',
     *          'city => 'London',
     *      ]
     * If the parameter has no default value - they're considered as required.
     */
    protected array $parametersDefaultValue;

    protected $hideDefaultParametersFromUrl;

    /**
     * @var ParameterDecoratorInterface[]
     */
    protected array $parametersDecorators;

    protected TextTemplate $textTemplate;

    protected ?string $defaultUrlSchema;

    /**
     * @param bool|array $hideDefaultParametersFromUrl
     */
    public function __construct(
        ?string      $domainUrlTemplate,
        ?string      $pathUrlTemplate,
        array        $parametersRequirements,
        array        $parametersDefaultValue,
        $hideDefaultParametersFromUrl,
        array        $parametersDecorators = [],
        ?TextTemplate $textTemplate = null,
        ?string $defaultUrlSchema = 'https'
    )
    {
        $this->domainUrlTemplate = $domainUrlTemplate;
        $this->pathUrlTemplate = $pathUrlTemplate;
        $this->parametersRequirements = $parametersRequirements;
        $this->parametersDefaultValue = $parametersDefaultValue;
        $this->hideDefaultParametersFromUrl = $hideDefaultParametersFromUrl;
        $this->parametersDecorators = $parametersDecorators;
        $this->textTemplate = $textTemplate;
        $this->defaultUrlSchema = $defaultUrlSchema;
    }

    public function generateUrlTemplateConfig(): UrlTemplateConfig
    {
        return new UrlTemplateConfig(
            $this->domainUrlTemplate,
            $this->pathUrlTemplate,
            $this->parametersRequirements,
            $this->parametersDefaultValue,
            $this->hideDefaultParametersFromUrl,
            $this->parametersDecorators,
            $this->textTemplate,
            $this->defaultUrlSchema
        );
    }

    public function getDomainUrlTemplate(): ?string
    {
        return $this->domainUrlTemplate;
    }

    public function setDomainUrlTemplate(?string $domainUrlTemplate): void
    {
        $this->domainUrlTemplate = $domainUrlTemplate;
    }

    public function getPathUrlTemplate(): ?string
    {
        return $this->pathUrlTemplate;
    }

    public function setPathUrlTemplate(?string $pathUrlTemplate): void
    {
        $this->pathUrlTemplate = $pathUrlTemplate;
    }

    public function getParametersRequirements(): array
    {
        return $this->parametersRequirements;
    }

    public function setParametersRequirements(array $parametersRequirements): void
    {
        $this->parametersRequirements = $parametersRequirements;
    }

    public function getParametersDefaultValue(): array
    {
        return $this->parametersDefaultValue;
    }

    public function setParametersDefaultValue(array $parametersDefaultValue): void
    {
        $this->parametersDefaultValue = $parametersDefaultValue;
    }

    public function isHideDefaultParametersFromUrl(): bool
    {
        return $this->hideDefaultParametersFromUrl;
    }

    public function setHideDefaultParametersFromUrl(bool $hideDefaultParametersFromUrl): void
    {
        $this->hideDefaultParametersFromUrl = $hideDefaultParametersFromUrl;
    }

    /**
     * @return ParameterDecoratorInterface[]
     */
    public function getParametersDecorators(): array
    {
        return $this->parametersDecorators;
    }

    /**
     * @param ParameterDecoratorInterface[] $parametersDecorators
     */
    public function setParametersDecorators(array $parametersDecorators): void
    {
        $this->parametersDecorators = $parametersDecorators;
    }

    public function getTextTemplate(): TextTemplate
    {
        return $this->textTemplate;
    }

    public function setTextTemplate(TextTemplate $textTemplate): void
    {
        $this->textTemplate = $textTemplate;
    }

    public function getDefaultUrlSchema(): ?string
    {
        return $this->defaultUrlSchema;
    }

    public function setDefaultUrlSchema(?string $defaultUrlSchema): void
    {
        $this->defaultUrlSchema = $defaultUrlSchema;
    }
}
