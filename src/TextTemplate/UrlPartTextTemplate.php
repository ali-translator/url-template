<?php

namespace ALI\UrlTemplate\TextTemplate;

use ALI\UrlTemplate\Enums\UrlPartType;

/**
 * Class
 */
class UrlPartTextTemplate
{
    /**
     * @var TextTemplate
     */
    protected $textTemplate;

    /**
     * @param TextTemplate $textTemplate
     */
    public function __construct(TextTemplate $textTemplate)
    {
        $this->textTemplate = $textTemplate;
    }

    /**
     * @param $parameterName
     * @param $regExprUrlPartTemplate
     * @param $urlType
     * @return string|string[]
     */
    public function makeOptionalParameterOnRegex($parameterName, $regExprUrlPartTemplate, $urlType)
    {
        $parameterKey = $this->textTemplate->getParameterKey($parameterName);
        switch ($urlType) {
            case UrlPartType::TYPE_HOST:
                $regExprUrlPartTemplate = str_replace($parameterKey . '\.', '(' . $parameterKey . '\.)?\.??', $regExprUrlPartTemplate,$replaceCount);
                if (!$replaceCount) {
                    // Parameter in one namespace with other parameter
                    $regExprUrlPartTemplate = str_replace($parameterKey, '(' . $parameterKey . ')?\.??', $regExprUrlPartTemplate, $replaceCount);
                }
                break;
            case UrlPartType::TYPE_PATH:
                $regExprUrlPartTemplate = str_replace(
                    [
                        $parameterKey . '\\/',
                        '\\/' . $parameterKey,
                    ],
                    [
                        '(' . $parameterKey . '\\/)?\\/?',
                        '\\/?(\\/' . $parameterKey . ')?',
                    ],
                    $regExprUrlPartTemplate,
                    $replaceCount
                );
                if (!$replaceCount) {
                    // Parameter in one namespace with other parameter
                    $regExprUrlPartTemplate = str_replace($parameterKey, '(' . $parameterKey . ')?\\/??', $regExprUrlPartTemplate);
                }
                break;
        }

        return $regExprUrlPartTemplate;
    }

    /**
     * @param $parameterName
     * @param $urlPart
     * @param $urlType
     * @return string|string[]
     */
    public function removeParameter($parameterName, $urlPart, $urlType)
    {
        $parameterKey = $this->textTemplate->getParameterKey($parameterName);
        switch ($urlType) {
            case UrlPartType::TYPE_HOST:
                $urlPart = str_replace($parameterKey . '.', '', $urlPart);
                break;
            case UrlPartType::TYPE_PATH:
                $urlPart = str_replace(
                    [
                        $parameterKey . '/',
                        '/' . $parameterKey,],
                    [
                        '',
                        '',
                    ],
                    $urlPart,
                    $replaceCount
                );
                if (!$replaceCount) {
                    // Parameter in one namespace with other parameter
                    $urlPart = str_replace($parameterKey, '', $urlPart);
                }
                break;
        }

        return $urlPart;
    }

}
