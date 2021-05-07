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
     * Example "{country}.test.com"
     *
     * @var null|string
     */
    protected $domainUrlTemplate;

    /**
     * Example "/prefix/{language}/{city}"
     *
     * @var null|string
     */
    protected $pathUrlTemplate;

    /**
     * Example:
     *      [
     *          'country' => '[a-z]{2,3}'
     *          'city' => '-0-9a-z]+'
     *      ]
     *
     * @var array
     */
    protected $parametersRequirements;

    /**
     * @example
     *      [
     *          'country' => 'gb',
     *          'language' => 'en',
     *          'city => 'London',
     *      ]
     * If parameter has no default value - their considered as required.
     *
     * @var array
     */
    protected $parametersDefaultValue;

    /**
     * @var bool
     */
    protected $hideDefaultParametersFromUrl;

    /**
     * @var ParameterDecoratorInterface[]
     */
    protected $parametersDecorators;

    /**
     * @var TextTemplate
     */
    protected $textTemplate;

    /**
     * @var string|null
     */
    protected $defaultUrlSchema;

    /**
     * @param string|null $domainUrlTemplate
     * @param string|null $pathUrlTemplate
     * @param string[] $parametersRequirements
     * @param array $parametersDefaultValue
     * @param bool|array $hideDefaultParametersFromUrl
     * @param ParameterDecoratorInterface[] $parametersDecorators
     * @param TextTemplate $textTemplate
     * @param string|null $defaultUrlSchema
     */
    public function __construct(
        $domainUrlTemplate,
        $pathUrlTemplate,
        array $parametersRequirements,
        array $parametersDefaultValue,
        $hideDefaultParametersFromUrl,
        $parametersDecorators = [],
        $textTemplate = null,
        $defaultUrlSchema = 'https'
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

    /**
     * @return UrlTemplateConfig
     */
    public function generateUrlTemplateConfig()
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

    /**
     * @return string|null
     */
    public function getDomainUrlTemplate()
    {
        return $this->domainUrlTemplate;
    }

    /**
     * @param string|null $domainUrlTemplate
     */
    public function setDomainUrlTemplate(string $domainUrlTemplate)
    {
        $this->domainUrlTemplate = $domainUrlTemplate;
    }

    /**
     * @return string|null
     */
    public function getPathUrlTemplate()
    {
        return $this->pathUrlTemplate;
    }

    /**
     * @param string|null $pathUrlTemplate
     */
    public function setPathUrlTemplate(string $pathUrlTemplate)
    {
        $this->pathUrlTemplate = $pathUrlTemplate;
    }

    /**
     * @return array
     */
    public function getParametersRequirements()
    {
        return $this->parametersRequirements;
    }

    /**
     * @param array $parametersRequirements
     */
    public function setParametersRequirements(array $parametersRequirements)
    {
        $this->parametersRequirements = $parametersRequirements;
    }

    /**
     * @return array
     */
    public function getParametersDefaultValue()
    {
        return $this->parametersDefaultValue;
    }

    /**
     * @param array $parametersDefaultValue
     */
    public function setParametersDefaultValue(array $parametersDefaultValue)
    {
        $this->parametersDefaultValue = $parametersDefaultValue;
    }

    /**
     * @return bool
     */
    public function isHideDefaultParametersFromUrl()
    {
        return $this->hideDefaultParametersFromUrl;
    }

    /**
     * @param bool $hideDefaultParametersFromUrl
     */
    public function setHideDefaultParametersFromUrl(bool $hideDefaultParametersFromUrl)
    {
        $this->hideDefaultParametersFromUrl = $hideDefaultParametersFromUrl;
    }

    /**
     * @return ParameterDecoratorInterface[]
     */
    public function getParametersDecorators()
    {
        return $this->parametersDecorators;
    }

    /**
     * @param ParameterDecoratorInterface[] $parametersDecorators
     */
    public function setParametersDecorators(array $parametersDecorators)
    {
        $this->parametersDecorators = $parametersDecorators;
    }

    /**
     * @return TextTemplate
     */
    public function getTextTemplate()
    {
        return $this->textTemplate;
    }

    /**
     * @param TextTemplate $textTemplate
     */
    public function setTextTemplate(TextTemplate $textTemplate)
    {
        $this->textTemplate = $textTemplate;
    }

    /**
     * @return string|null
     */
    public function getDefaultUrlSchema()
    {
        return $this->defaultUrlSchema;
    }

    /**
     * @param string|null $defaultUrlSchema
     */
    public function setDefaultUrlSchema(string $defaultUrlSchema)
    {
        $this->defaultUrlSchema = $defaultUrlSchema;
    }
}
