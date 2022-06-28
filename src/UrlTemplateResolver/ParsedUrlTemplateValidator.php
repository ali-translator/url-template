<?php

namespace ALI\UrlTemplate\UrlTemplateResolver;

use ALI\UrlTemplate\ParsedUrlTemplate;

class ParsedUrlTemplateValidator
{
    public function validateParsedUrlTemplate(ParsedUrlTemplate $parsedUrlTemplate): array
    {
        $errors = [];
        $errors += $this->validatePatternedHost($parsedUrlTemplate);
        $errors += $this->validatePatternedPath($parsedUrlTemplate);
        $errors += $this->validateParameters($parsedUrlTemplate);

        return $errors;
    }

    public function validatePatternedHost(ParsedUrlTemplate $parsedUrlTemplate): array
    {
        $urlTemplateConfig = $parsedUrlTemplate->getUrlTemplateConfig();

        $errors = [];
        if (!preg_match('/' . preg_quote($urlTemplateConfig->getHostUrlTemplate(), '/') . '$/', $parsedUrlTemplate->getPatternedHost())) {
            $errors['host'] = 'Invalid patterned host "' . $parsedUrlTemplate->getPatternedHost() . '" for requirements : "' . $urlTemplateConfig->getHostUrlTemplate() . '"';
        }

        return $errors;
    }

    public function validatePatternedPath(ParsedUrlTemplate $parsedUrlTemplate): array
    {
        $urlTemplateConfig = $parsedUrlTemplate->getUrlTemplateConfig();

        $errors = [];
        if (!preg_match('/^' . preg_quote($urlTemplateConfig->getPathUrlTemplate(), '/') . '/', $parsedUrlTemplate->getPatternedPath())) {
            $errors['path'] = 'Invalid patterned path "' . $parsedUrlTemplate->getPatternedPath() . '" for requirements : "' . $urlTemplateConfig->getPathUrlTemplate() . '"';
        }

        return $errors;
    }

    public function validateParameters(ParsedUrlTemplate $parsedUrlTemplate): array
    {
        $urlTemplateConfig = $parsedUrlTemplate->getUrlTemplateConfig();

        $errors = [];
        foreach ($urlTemplateConfig->getAllParameters() as $parameterName) {
            $parameterRequirements = $urlTemplateConfig->getParameterRequirements($parameterName);
            $parameterValue = $parsedUrlTemplate->getParameter($parameterName);

            if (!preg_match('/^' . $parameterRequirements . '$/', $parameterValue)) {
                $errors[$parameterName] = 'Invalid parameter "' . $parameterName . '" value "' . $parameterValue . '" for requirements : "' . $parameterRequirements . '"';
            }
        }

        return $errors;
    }
}
