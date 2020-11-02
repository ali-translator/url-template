<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\Helpers\DuplicateParameterResolver;
use ALI\UrlTemplate\Helpers\OptionalityParametersCombinator;
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
     * @var OptionalityParametersCombinator
     */
    protected $optionalityParametersCombinator;

    /**
     * @param UrlTemplateConfig $urlTemplateConfig
     */
    public function __construct(UrlTemplateConfig $urlTemplateConfig)
    {
        $this->urlTemplateConfig = $urlTemplateConfig;
        $this->urlPartTextTemplate = new UrlPartTextTemplate($this->urlTemplateConfig->getTextTemplate());
        $this->optionalityParametersCombinator = new OptionalityParametersCombinator();
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
        $patternedUrlPart = null;
        $urlPartParametersValue = [];
        if (!$urlPart || !$parametersNames) {
            return [$patternedUrlPart, $urlPartParametersValue];
        }

        $duplicateParameterResolver = new DuplicateParameterResolver();

        switch ($type) {
            case UrlPartType::TYPE_HOST:
                $urlPartTemplate = $this->urlTemplateConfig->getHostUrlTemplate();
                break;
            case UrlPartType::TYPE_PATH:
                $urlPartTemplate = $this->urlTemplateConfig->getPathUrlTemplate();
                break;
        }

        $regularExpression = $this->generateRegularExpression($type, $urlPartTemplate, $duplicateParameterResolver);

        // search parameters values
        if (!preg_match_all($regularExpression, $urlPart, $matches, PREG_SET_ORDER)) {
            throw new InvalidUrlException();
        }

        $matches = current($matches);
        $urlPartParametersValue = $this->bindParametersValues($parametersNames, $matches, $duplicateParameterResolver);
        $patternedUrlPart = preg_replace($regularExpression, $urlPartTemplate, $urlPart, 1);

        return [$patternedUrlPart, $urlPartParametersValue];
    }

    /**
     * @var string[]
     */
    protected $regularExpressions = [];

    /**
     * @param string $type
     * @param string $urlPartTemplate
     * @param DuplicateParameterResolver $duplicateParameterResolver
     * @return string
     */
    protected function generateRegularExpression($type, $urlPartTemplate, $duplicateParameterResolver)
    {
        if(isset($this->regularExpressions[$urlPartTemplate])){
            return $this->regularExpressions[$urlPartTemplate];
        }

        $textTemplate = $this->urlTemplateConfig->getTextTemplate();
        $optionalityParametersNames = $this->urlTemplateConfig->getHideDefaultParametersFromUrl();

        $namespaceDelimiter = $type === UrlPartType::TYPE_HOST ? '.' : '/';
        $quotedNamespaceDelimiter = $type === UrlPartType::TYPE_HOST ? '\.' : '\\/';


        $urlPartTemplateNamespaceParts = explode($namespaceDelimiter, $urlPartTemplate);
        $urlPartTemplateNamespaceParts = array_filter($urlPartTemplateNamespaceParts);

        $allCombinationsReqExpressions = [];
        if ($type === UrlPartType::TYPE_PATH) {
            $allCombinationsReqExpressions[] = $quotedNamespaceDelimiter;
        }
        foreach ($urlPartTemplateNamespaceParts as $urlPartTemplatePartValue) {

            $namespaceParametersName = $textTemplate->parseParametersName($urlPartTemplatePartValue);
            $namespaceCombinationsReqExpressions = [];
            if ($namespaceParametersName) {
                // namespace with parameters
                $allCombination = $this->optionalityParametersCombinator->getAllParametersCombination($namespaceParametersName, $optionalityParametersNames);

                foreach ($allCombination as $currentCombinationParameters) {
                    if (!$currentCombinationParameters) {
                        // Combination where all parameters was skipped
                        $namespaceCombinationsReqExpressions[] = '';
                        continue;
                    }
                    // Combination where not all parameters was skipped
                    $namespaceUrlPartTemplate = $urlPartTemplatePartValue;
                    $parametersForReplacing = [];
                    foreach ($namespaceParametersName as $currentNamespaceParameterName) {
                        if (!isset($currentCombinationParameters[$currentNamespaceParameterName])) {
                            $namespaceUrlPartTemplate = $textTemplate->resolveParameters($namespaceUrlPartTemplate, [$currentNamespaceParameterName => null]);
                        } else {
                            $parametersForReplacing[] = $currentNamespaceParameterName;
                        }
                    }

                    $parametersForReplacing = $this->generateParametersForReplacing($parametersForReplacing, $duplicateParameterResolver);
                    $namespaceCombinationsReqExpressions[] = $textTemplate->resolveParameters($namespaceUrlPartTemplate, $parametersForReplacing);
                }
            } else {
                // namespace only with static text
                $namespaceCombinationsReqExpressions[] = preg_quote($urlPartTemplatePartValue, '/');
            }

            if (count($namespaceCombinationsReqExpressions) > 1) {
                $namespaceCombinationsReqCompiledExpressions = [];
                foreach ($namespaceCombinationsReqExpressions as $namespaceCombinationsReqExpression) {
                    $namespaceCombinationsReqCompiledExpressions[] = $namespaceCombinationsReqExpression ? '(' . $namespaceCombinationsReqExpression . '(' . $quotedNamespaceDelimiter . '|$)' . ')' : null;
                }
                $splitNamespaceReqExpression = '(' . implode('|', $namespaceCombinationsReqCompiledExpressions) . ')';
            } else {
                $splitNamespaceReqExpression = '(' . current($namespaceCombinationsReqExpressions) . '(' . $quotedNamespaceDelimiter . '|$))';
            }
            $allCombinationsReqExpressions[] = $splitNamespaceReqExpression;
        }

        $regularExpression = '' . implode('', $allCombinationsReqExpressions);
        if ($type === UrlPartType::TYPE_PATH) {
            $regularExpression = '^' . $regularExpression;
        }
        $this->regularExpressions[$urlPartTemplate] = '/' . $regularExpression . '/';

        return $this->regularExpressions[$urlPartTemplate];
    }

    /**
     * @param string[] $parametersNames
     * @param DuplicateParameterResolver $duplicateParameterResolver
     * @return array
     */
    protected function generateParametersForReplacing($parametersNames, $duplicateParameterResolver)
    {
        $parametersForReplacing = [];
        foreach ($parametersNames as $parameterName) {
            $requirement = $this->urlTemplateConfig->getParameterRequirements($parameterName);
            if (!$requirement) {
                throw new \LogicException('Not found requirements for "' . $parameterName . '" parameter');
            }

            $parameterForReplacing = '(?P<' . $duplicateParameterResolver->getParameterNameAlias($parameterName) . '>' . $requirement . ')';
            $parametersForReplacing[$parameterName] = $parameterForReplacing;
        }

        return $parametersForReplacing;
    }

    /**
     * @param array $parametersNames
     * @param array $matches
     * @param DuplicateParameterResolver $duplicateParameterResolver
     * @return array
     */
    protected function bindParametersValues($parametersNames, $matches, $duplicateParameterResolver)
    {
        $urlPartParametersValue = [];

        $existedParametersValues = [];
        foreach ($parametersNames as $parameterName) {
            $parameterValue = $duplicateParameterResolver->resolverParameterNameValue($parameterName, $matches);;
            if ($parameterValue) {
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
