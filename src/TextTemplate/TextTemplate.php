<?php

namespace ALI\UrlTemplate\TextTemplate;

class TextTemplate
{
    private string $openTag;
    private string $closeTag;

    /**
     * @param string $openTag
     * @param string $closeTag
     */
    public function __construct(string $openTag = '{', string $closeTag = '}')
    {
        $this->openTag = $openTag;
        $this->closeTag = $closeTag;
    }

    /**
     * @return string[]
     */
    public function parseParametersName(?string $text): array
    {
        if (!$text) {
            return [];
        }
        $openTag = preg_quote($this->openTag, '/');
        $closeTag = preg_quote($this->closeTag, '/');
        $matchesPattern = '/' . $openTag . '([^' . $closeTag . ']+)' . $closeTag . '/';
        preg_match_all($matchesPattern, $text, $parametersName);

        return $parametersName[1];
    }

    public function resolveParameters(?string $text, array $parameters): string
    {
        if (!$text) {
            return '';
        }

        $keysForReplacing = [];
        foreach ($parameters as $parameterName => $parameterValue) {
            $keysForReplacing[] = $this->getParameterKey($parameterName);
        }

        return str_replace($keysForReplacing, $parameters, $text);
    }

    public function getParameterKey(string $parameterName): string
    {
        return $this->openTag . $parameterName . $this->closeTag;
    }
}
