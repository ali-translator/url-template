<?php

namespace ALI\UrlTemplate;

use ALI\UrlTemplate\ParameterDecorators\ParameterDecoratorInterface;
use ALI\UrlTemplate\TextTemplate\TextTemplate;

/**
 * Class
 */
class UrlTemplateConfig
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
    protected $defaultParametersValue;

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
    private $textTemplate;

    /**
     * @param string|null $domainUrlTemplate
     * @param string|null $pathUrlTemplate
     * @param string[] $parametersRequirements
     * @param array $parametersDefaultValue
     * @param bool|array $hideDefaultParametersFromUrl
     * @param ParameterDecoratorInterface[] $parametersDecorators
     * @param TextTemplate $textTemplate
     */
    public function __construct(
        $domainUrlTemplate,
        $pathUrlTemplate,
        array $parametersRequirements,
        array $parametersDefaultValue,
        $hideDefaultParametersFromUrl,
        $parametersDecorators = [],
        $textTemplate = null
    )
    {
        $this->domainUrlTemplate = $domainUrlTemplate;
        $this->pathUrlTemplate = '/' . trim($pathUrlTemplate, '/') . '/';
        $this->parametersRequirements = $parametersRequirements;
        $this->defaultParametersValue = $parametersDefaultValue;
        $this->parametersDecorators = $parametersDecorators;

        if ($hideDefaultParametersFromUrl === true) {
            $hideDefaultParametersFromUrl = array_keys($parametersDefaultValue);
        } elseif (!is_array($hideDefaultParametersFromUrl)) {
            $hideDefaultParametersFromUrl = [];
        }
        $this->hideDefaultParametersFromUrl = array_combine($hideDefaultParametersFromUrl, $hideDefaultParametersFromUrl);

        $this->textTemplate = $textTemplate ?: new TextTemplate();
    }

    /**
     * @var string[]
     */
    private $_domainUrlParameters;

    /**
     * @return string[]
     */
    public function getHostUrlParameters()
    {
        if ($this->_domainUrlParameters === null) {
            $this->_domainUrlParameters = $this->textTemplate->parseParametersName($this->domainUrlTemplate);
        }

        return $this->_domainUrlParameters;
    }

    /**
     * @var string[]
     */
    private $_pathUrlParameters;

    /**
     * @return string[]
     */
    public function getPathUrlParameters()
    {
        if ($this->_pathUrlParameters === null) {
            $this->_pathUrlParameters = $this->textTemplate->parseParametersName($this->pathUrlTemplate);
        }

        return $this->_pathUrlParameters;
    }

    /**
     * @return string[]
     */
    public function getAllParameters()
    {
        return array_merge($this->getHostUrlParameters(), $this->getPathUrlParameters());
    }

    /**
     * @param string $parameterName
     * @return bool
     */
    public function isRequiredParameter($parameterName)
    {
        return !array_key_exists($parameterName, $this->defaultParametersValue);
    }

    /**
     * @return string|null
     */
    public function getHostUrlTemplate()
    {
        return $this->domainUrlTemplate;
    }

    /**
     * @return string|null
     */
    public function getPathUrlTemplate()
    {
        return $this->pathUrlTemplate;
    }

    /**
     * @return string[]
     */
    public function getHostRequiredParameters()
    {
        return array_diff($this->getHostUrlParameters(), array_keys($this->defaultParametersValue));
    }


    /**
     * @return string[]
     */
    public function getHostNotRequiredParameters()
    {
        return array_diff($this->getHostUrlParameters(), $this->getHostRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getPathRequiredParameters()
    {
        return array_diff($this->getPathUrlParameters(), array_keys($this->defaultParametersValue));
    }

    /**
     * @return string[]
     */
    public function getPathNotRequiredParameters()
    {
        return array_diff($this->getPathUrlParameters(), $this->getPathRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getRequiredParameters()
    {
        return array_diff($this->getAllParameters(), array_keys($this->defaultParametersValue));
    }

    /**
     * @return string[]
     */
    public function getNotRequireParameters()
    {
        return array_diff($this->getAllParameters(), $this->getRequiredParameters());
    }

    /**
     * @return string[]
     */
    public function getParametersRequirements()
    {
        return $this->parametersRequirements;
    }

    /**
     * @param string $parameterName
     * @return string|null
     */
    public function getParameterRequirements($parameterName)
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

    /**
     * @return array
     */
    public function getDefaultParametersValue()
    {
        return $this->defaultParametersValue;
    }

    /**
     * @param array $existedParametersValue
     * @return array
     * @throws \Exception
     */
    public function getCompiledDefaultParametersValue(array $existedParametersValue)
    {
        $compiledDefaultParametersValue = [];
        foreach ($this->defaultParametersValue as $parameterName => $parameterValue) {
            $compiledDefaultParametersValue[$parameterName] = $this->getCompiledDefaultParameterValueItem($parameterName, $existedParametersValue);
        }

        return $compiledDefaultParametersValue;
    }

    /**
     * @param $parameterName
     * @param array $existedParametersValue
     * @return mixed
     * @throws \Exception
     */
    public function getCompiledDefaultParameterValueItem($parameterName, array $existedParametersValue)
    {
        if (!isset($this->defaultParametersValue[$parameterName])) {
            throw new \Exception('Invalid default param name "' . $parameterName . '"');
        }

        $defaultParameterValue = $this->defaultParametersValue[$parameterName];
        if (is_callable($defaultParameterValue)) {
            $defaultParameterValue = call_user_func($defaultParameterValue, $existedParametersValue);
        }

        return $defaultParameterValue;
    }

    /**
     * @return array
     */
    public function hideDefaultParametersFromUrl()
    {
        return $this->hideDefaultParametersFromUrl;
    }

    /**
     * @param string $parameterName
     * @return bool
     */
    public function isHiddenParameter($parameterName)
    {
        return isset($this->hideDefaultParametersFromUrl[$parameterName]);
    }

    /**
     * @return TextTemplate
     */
    public function getTextTemplate()
    {
        return $this->textTemplate;
    }

    /**
     * @param string $parameterName
     * @return ParameterDecoratorInterface|null
     */
    public function getParameterDecorator($parameterName)
    {
        if (isset($this->parametersDecorators[$parameterName])) {
            return $this->parametersDecorators[$parameterName];
        }

        return null;
    }
}
