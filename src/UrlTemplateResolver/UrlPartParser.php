<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\TextTemplate\TextTemplate;
use ALI\UrlTemplate\TextTemplate\UrlPartTextTemplate;
use ALI\UrlTemplate\UrlTemplateConfig;

/**
 * Class
 */
class UrlPartParser
{
    /**
     * @var UrlTemplateConfig
     */
    protected $urlTemplateConfig;

    /**
     * @var UrlPartTextTemplate
     */
    protected $urlPartTextTemplate;

    /**
     * @param UrlTemplateConfig $urlTemplateConfig
     */
    public function __construct(UrlTemplateConfig $urlTemplateConfig)
    {
        $this->urlTemplateConfig = $urlTemplateConfig;
        $this->urlPartTextTemplate = new UrlPartTextTemplate($this->urlTemplateConfig->getTextTemplate());
    }

    /**
     * @param $type
     * @param $urlPart
     * @param $parametersNames
     * @return array
     * @throws InvalidUrlException
     */
    public function parse($type, $urlPart, $parametersNames)
    {
        $textTemplate = $this->urlTemplateConfig->getTextTemplate();

        switch ($type) {
            case UrlPartType::TYPE_HOST:
                $urlPartTemplate = $this->urlTemplateConfig->getHostUrlTemplate();
                break;
            case UrlPartType::TYPE_PATH:
                $urlPartTemplate = $this->urlTemplateConfig->getPathUrlTemplate();
                break;
        }

        $patternedUrlPart = null;
        $urlPartParametersValue = [];
        if ($urlPart && $parametersNames) {

            $quotedUrlPartTemplate = $this->prepareUrlPartTemplate($type, $urlPartTemplate);
            $parametersForReplacing = $this->generateParametersForReplacing($parametersNames);
            $regularExpression = $this->generateRegularExpression($type, $textTemplate, $quotedUrlPartTemplate, $parametersForReplacing);

            // search parameters values
            if (!preg_match_all($regularExpression, $urlPart, $matches)) {
                throw new InvalidUrlException();
            }

            $urlPartParametersValue = $this->bindParametersValues($parametersNames, $matches);
            $patternedUrlPart = preg_replace($regularExpression, $urlPartTemplate, $urlPart, 1);
        }

        return [$patternedUrlPart, $urlPartParametersValue];
    }

    /**
     * @param $type
     * @param $urlPartTemplate
     * @return string|string[]
     */
    protected function prepareUrlPartTemplate($type, $urlPartTemplate)
    {
        switch ($type) {
            case UrlPartType::TYPE_HOST:
                $quotedUrlPartTemplate = str_replace('.', '\.', $urlPartTemplate);
                break;
            case UrlPartType::TYPE_PATH:
                $quotedUrlPartTemplate = str_replace('/', '\\/', $urlPartTemplate);
                break;
        }
        $optionalityParametersNames = $this->urlTemplateConfig->getNotRequireParameters();
        foreach ($optionalityParametersNames as $optionalityParameterName) {
            $quotedUrlPartTemplate = $this->urlPartTextTemplate->makeOptionalParameterOnRegex($optionalityParameterName, $quotedUrlPartTemplate, $type);
        }

        return $quotedUrlPartTemplate;
    }

    /**
     * @param $parametersNames
     * @return array
     */
    protected function generateParametersForReplacing($parametersNames)
    {
        $parametersForReplacing = [];
        foreach ($parametersNames as $parameterName) {
            $requirement = $this->urlTemplateConfig->getParameterRequirements($parameterName);
            if (!$requirement) {
                throw new \LogicException('Not found requirements for "' . $parameterName . '" parameter');
            }

            $parameterForReplacing = '(?<' . $parameterName . '>' . $requirement . ')';
            $parametersForReplacing[$parameterName] = $parameterForReplacing;
        }

        return $parametersForReplacing;
    }

    /**
     * @param $type
     * @param TextTemplate $textTemplate
     * @param array $quotedUrlPartTemplate
     * @param string $parametersForReplacing
     * @return string
     */
    protected function generateRegularExpression(
        $type,
        TextTemplate $textTemplate,
        $quotedUrlPartTemplate,
        array $parametersForReplacing
    )
    {
        $regularExpression = $textTemplate->resolveParameters($quotedUrlPartTemplate, $parametersForReplacing);
        switch ($type) {
            case UrlPartType::TYPE_HOST:
                $regularExpression = '/(?<=^|\.)' . $regularExpression . '/';
                break;
            case UrlPartType::TYPE_PATH:
                $regularExpression = '/\\/' . trim($regularExpression, '\\/') . '\\/?/';
                break;
        }

        return $regularExpression;
    }

    /**
     * @param array $parametersNames
     * @param array $matches
     * @return array
     */
    protected function bindParametersValues($parametersNames, $matches)
    {
        $urlPartParametersValue = [];

        $existedParametersValues = [];
        foreach ($parametersNames as $parameterName) {
            if (!empty($matches[$parameterName][0])) {
                $parameterValue = $matches[$parameterName][0];
                $parameterDecorator = $this->urlTemplateConfig->getParameterDecorator($parameterName);
                if ($parameterDecorator) {
                    $parameterValue = $parameterDecorator->parse($parameterValue);
                }
                $existedParametersValues[$parameterName] = $parameterValue;
            }
        }

        return $existedParametersValues + $urlPartParametersValue;
    }
}
