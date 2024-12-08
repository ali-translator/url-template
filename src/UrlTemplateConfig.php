<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\ParameterDecorators\ParameterDecoratorInterface;
use ALI\UrlTemplate\TextTemplate\TextTemplate;
use ALI\UrlTemplate\Units\UrlTemplateConfigData;
use RuntimeException;

class UrlTemplateConfig
{
    /**
     * Example "{country}.test.com"
     */
    protected ?string $domainUrlTemplate;

    /**
     * Example "/prefix/{language}/{city}"
     */
    protected ?string $pathUrlTemplate;

    /**
     * Example:
     *      [
     *          'country' => '[a-z]{2,3}'
     *          'city' => '-0-9a-z]+'
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
     * If parameter has no default value - their considered as required.
     */
    protected array $defaultParametersValue = [];

    /**
     * @var array<string>|bool
     */
    protected $hideDefaultParametersFromUrl;

    /**
     * @var ParameterDecoratorInterface[]
     */
    protected array $parametersDecorators;

    protected TextTemplate $textTemplate;

    protected ?string $defaultUrlSchema;

    /**
     * @param array<string, array<string>>|array<string> $parametersRequirements
     * @param bool|array<string> $hideDefaultParametersFromUrl
     * @param array<ParameterDecoratorInterface> $parametersDecorators
     */
    public function __construct(
        ?string $domainUrlTemplate,
        ?string $pathUrlTemplate,
        array $parametersRequirements,
        array $parametersDefaultValue,
        $hideDefaultParametersFromUrl,
        array $parametersDecorators = [],
        ?TextTemplate $textTemplate = null,
        ?string $defaultUrlSchema = 'https'
    )
    {
        $this->domainUrlTemplate = $domainUrlTemplate;

        $pathUrlTemplate = trim($pathUrlTemplate, '/');
        if ($pathUrlTemplate) {
            $this->pathUrlTemplate = '/' . $pathUrlTemplate . '/';
        } else {
            $this->pathUrlTemplate = '/';
        }

        $this->parametersRequirements = $parametersRequirements;
        $this->defaultParametersValue = $parametersDefaultValue;
        $this->parametersDecorators = $parametersDecorators;
        $this->defaultUrlSchema = $defaultUrlSchema;

        if ($hideDefaultParametersFromUrl === true) {
            $hideDefaultParametersFromUrl = array_keys($parametersDefaultValue);
        } elseif (!is_array($hideDefaultParametersFromUrl)) {
            $hideDefaultParametersFromUrl = [];
        }
        $this->hideDefaultParametersFromUrl = array_combine($hideDefaultParametersFromUrl, $hideDefaultParametersFromUrl);

        $this->textTemplate = $textTemplate ?: new TextTemplate();
    }

    public function generateUrlTemplateConfigData(): UrlTemplateConfigData
    {
        return new UrlTemplateConfigData(
            $this->domainUrlTemplate,
            $this->pathUrlTemplate,
            $this->parametersRequirements,
            $this->defaultParametersValue,
            $this->hideDefaultParametersFromUrl,
            $this->parametersDecorators,
            $this->textTemplate,
            $this->defaultUrlSchema
        );
    }

    /**
     * @var string[]
     */
    private array $_domainUrlParameters;

    /**
     * @return string[]
     */
    public function getHostUrlParameters(): array
    {
        if (!isset($this->_domainUrlParameters)) {
            $this->_domainUrlParameters = $this->textTemplate->parseParametersName($this->domainUrlTemplate);
        }

        return $this->_domainUrlParameters;
    }

    /**
     * @var string[]
     */
    private array $_pathUrlParameters;

    /**
     * @return string[]
     */
    public function getPathUrlParameters(): array
    {
        if (!isset($this->_pathUrlParameters)) {
            $this->_pathUrlParameters = $this->textTemplate->parseParametersName($this->pathUrlTemplate);
        }

        return $this->_pathUrlParameters;
    }

    /**
     * @return string[]
     */
    public function getAllParameters(): array
    {
        return array_merge($this->getHostUrlParameters(), $this->getPathUrlParameters());
    }

    public function isRequiredParameter(string $parameterName): bool
    {
        return !array_key_exists($parameterName, $this->defaultParametersValue);
    }

    public function getHostUrlTemplate(): ?string
    {
        return $this->domainUrlTemplate ?? null;
    }

    public function getPathUrlTemplate(): ?string
    {
        return $this->pathUrlTemplate ?? null;
    }

    /**
     * @return string[]
     */
    public function getHostRequiredParameters(): array
    {
        return array_diff($this->getHostUrlParameters(), array_keys($this->defaultParametersValue));
    }


    /**
     * @return string[]
     */
    public function getHostNotRequiredParameters(): array
    {
        return array_diff($this->getHostUrlParameters(), $this->getHostRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getPathRequiredParameters(): array
    {
        return array_diff($this->getPathUrlParameters(), array_keys($this->defaultParametersValue));
    }

    /**
     * @return string[]
     */
    public function getPathNotRequiredParameters(): array
    {
        return array_diff($this->getPathUrlParameters(), $this->getPathRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getRequiredParameters(): array
    {
        return array_diff($this->getAllParameters(), array_keys($this->defaultParametersValue));
    }

    /**
     * @return string[]
     */
    public function getNotRequireParameters(): array
    {
        return array_diff($this->getAllParameters(), $this->getRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getParametersRequirements(): array
    {
        return $this->parametersRequirements;
    }

    public function getParameterRequirements(string $parameterName): ?string
    {
        if (!isset($this->parametersRequirements[$parameterName])) {
            return null;
        }

        $parameterRequirements = $this->parametersRequirements[$parameterName];

        // Resolve by decorators
        if (is_array($parameterRequirements)) {
            $parameterDecorator = $this->getParameterDecorator($parameterName);
            if ($parameterDecorator) {
                foreach ($parameterRequirements as &$possibleParameter) {
                    $possibleParameter = $parameterDecorator->generate($possibleParameter);
                }
                unset($possibleParameter);
            }
            $parameterRequirements = array_map(function ($val) {
                return preg_quote($val, '/');
            }, $parameterRequirements);
            $parameterRequirements = '(' . implode('|', $parameterRequirements) . ')';
        }

        return $parameterRequirements;
    }

    public function getDefaultParametersValue(): array
    {
        return $this->defaultParametersValue;
    }

    public function getCompiledDefaultParametersValue(array $existedParametersValue): array
    {
        $compiledDefaultParametersValue = [];
        foreach ($this->defaultParametersValue as $parameterName => $parameterValue) {
            $compiledDefaultParametersValue[$parameterName] = $this->getCompiledDefaultParameterValueItem($parameterName, $existedParametersValue);
        }

        return $compiledDefaultParametersValue;
    }

    /**
     * @return mixed
     */
    public function getCompiledDefaultParameterValueItem(string $parameterName, array $existedParametersValue)
    {
        if (!isset($this->defaultParametersValue[$parameterName])) {
            throw new RuntimeException('Invalid default param name "' . $parameterName . '"');
        }

        $defaultParameterValue = $this->defaultParametersValue[$parameterName];
        if (is_callable($defaultParameterValue)) {
            $defaultParameterValue = call_user_func($defaultParameterValue, $existedParametersValue);
        }

        return $defaultParameterValue;
    }

    public function getHideDefaultParametersFromUrl(): array
    {
        return $this->hideDefaultParametersFromUrl;
    }

    public function isHiddenParameter(string $parameterName): bool
    {
        return isset($this->hideDefaultParametersFromUrl[$parameterName]);
    }

    public function getTextTemplate(): TextTemplate
    {
        return $this->textTemplate;
    }

    public function getParameterDecorator(string $parameterName): ?ParameterDecoratorInterface
    {
        if (isset($this->parametersDecorators[$parameterName])) {
            return $this->parametersDecorators[$parameterName];
        }

        return null;
    }

    public function getDefaultUrlSchema(): ?string
    {
        return $this->defaultUrlSchema;
    }
}
