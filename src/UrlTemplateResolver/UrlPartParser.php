<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\Enums\UrlPartType;
use ALI\UrlTemplate\Exceptions\InvalidUrlException;
use ALI\UrlTemplate\Helpers\DuplicateParameterResolver;
use ALI\UrlTemplate\Helpers\OptionalityParametersCombinator;
use ALI\UrlTemplate\UrlTemplateConfig;
use LogicException;

class UrlPartParser
{
    protected OptionalityParametersCombinator $optionalityParametersCombinator;
    protected DuplicateParameterResolver $duplicateParameterResolver;


    public function __construct(DuplicateParameterResolver $duplicateParameterResolver)
    {
        $this->optionalityParametersCombinator = new OptionalityParametersCombinator();
        $this->duplicateParameterResolver = $duplicateParameterResolver;
    }

    /**
     * @throws InvalidUrlException
     */
    public function parse(
        string $type,
        string $urlPart,
        array $parametersNames,
        UrlTemplateConfig $urlTemplateConfig
    ): array
    {
        $patternedUrlPart = null;
        $urlPartParametersValue = [];
        if (!$urlPart || !$parametersNames) {
            return [$patternedUrlPart, $urlPartParametersValue];
        }

        switch ($type) {
            case UrlPartType::TYPE_HOST:
                $urlPartTemplate = $urlTemplateConfig->getHostUrlTemplate();
                break;
            case UrlPartType::TYPE_PATH:
                $urlPartTemplate = $urlTemplateConfig->getPathUrlTemplate();
                break;
            default:
                throw new InvalidUrlException("Unsupported url part type '{$type}'.");
        }

        $regularExpression = $this->generateRegularExpression($type, $urlPartTemplate, $this->duplicateParameterResolver, $urlTemplateConfig);

        // search parameters values
        if (!preg_match_all($regularExpression, $urlPart, $matches, PREG_SET_ORDER)) {
            throw new InvalidUrlException();
        }

        $matches = current($matches);
        $urlPartParametersValue = $this->bindParametersValues($parametersNames, $matches, $this->duplicateParameterResolver, $urlTemplateConfig);
        $patternedUrlPart = preg_replace($regularExpression, $urlPartTemplate, $urlPart, 1);

        return [$patternedUrlPart, $urlPartParametersValue];
    }

    /**
     * @var string[]
     */
    protected array $regularExpressions = [];

    protected function generateRegularExpression(
        string $type,
        string $urlPartTemplate,
        DuplicateParameterResolver $duplicateParameterResolver,
        UrlTemplateConfig $urlTemplateConfig
    ): string
    {
        $cacheKey = spl_object_id($urlTemplateConfig) . $type . $urlPartTemplate;

        if (isset($this->regularExpressions[$cacheKey])) {
            return $this->regularExpressions[$cacheKey];
        }

        $textTemplate = $urlTemplateConfig->getTextTemplate();
        $optionalityParametersNames = $urlTemplateConfig->getHideDefaultParametersFromUrl();

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
                        // Combination where all parameters were skipped
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

                    $parametersForReplacing = $this->generateParametersForReplacing($parametersForReplacing, $duplicateParameterResolver, $urlTemplateConfig);
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

        $regularExpression = implode('', $allCombinationsReqExpressions);
        switch ($type){
            case UrlPartType::TYPE_PATH:
                $regularExpression = '^' . $regularExpression;
                break;
            case UrlPartType::TYPE_HOST:
                if (!$urlTemplateConfig->isAllowedSubdomains()) {
                    $regularExpression = '^' . $regularExpression;
                }
                break;
        }
        $regularExpression = '/' . $regularExpression . '/';

        $this->regularExpressions[$cacheKey] = $regularExpression;

        return $this->regularExpressions[$cacheKey];
    }

    /**
     * @param string[] $parametersNames
     */
    protected function generateParametersForReplacing(
        array $parametersNames,
        DuplicateParameterResolver $duplicateParameterResolver,
        UrlTemplateConfig $urlTemplateConfig
    ): array
    {
        $parametersForReplacing = [];
        foreach ($parametersNames as $parameterName) {
            $requirement = $urlTemplateConfig->getParameterRequirements($parameterName);
            if (!$requirement) {
                throw new InvalidUrlException('Not found requirements for "' . $parameterName . '" parameter');
            }

            $parameterForReplacing = '(?P<' . $duplicateParameterResolver->getParameterNameAlias($parameterName) . '>' . $requirement . ')';
            $parametersForReplacing[$parameterName] = $parameterForReplacing;
        }

        return $parametersForReplacing;
    }

    protected function bindParametersValues(
        array $parametersNames,
        array $matches,
        DuplicateParameterResolver $duplicateParameterResolver,
        UrlTemplateConfig $urlTemplateConfig
    ): array
    {
        $urlPartParametersValue = [];

        $existedParametersValues = [];
        foreach ($parametersNames as $parameterName) {
            $parameterValue = $duplicateParameterResolver->resolverParameterNameValue($parameterName, $matches);;
            if ($parameterValue) {
                $parameterDecorator = $urlTemplateConfig->getParameterDecorator($parameterName);
                if ($parameterDecorator) {
                    $parameterValue = $parameterDecorator->parse($parameterValue);
                }
                $existedParametersValues[$parameterName] = $parameterValue;
            }
        }

        return $existedParametersValues + $urlPartParametersValue;
    }
}
