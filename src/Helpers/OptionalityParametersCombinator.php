<?php

namespace ALI\UrlTemplate\Helpers;

class OptionalityParametersCombinator
{
    /**
     * @param string[] $parameters
     * @param string[] $optionalityParameters
     * @return array
     */
    public function getAllParametersCombination(array $parameters, array $optionalityParameters): array
    {
        $indexedOptionalityParameters = array_combine($optionalityParameters, $optionalityParameters);

        if ($this->isAllParametersWasRequired($parameters, $indexedOptionalityParameters)) {
            return [array_combine($parameters,$parameters)];
        }

        $parametersOptionalityBinaryMask = $this->getParametersOptionalityBinaryMask($parameters, $indexedOptionalityParameters);
        $possibleBinaryVariants = $this->getPossibleBinaryVariants($parametersOptionalityBinaryMask);

        $parametersIndexedByNumber = array_values($parameters);
        $allParametersCombination = [];
        foreach ($possibleBinaryVariants as $binaryVariant) {
            $binaryVariantArray = str_split($binaryVariant);
            $currentCombinationParameters = [];
            foreach ($binaryVariantArray as $parameterNumber => $paramBinaryValue) {
                if ($paramBinaryValue === "0") {
                    // Required parameter on this combination
                    $requiredOnThisCombinationParameterName = $parametersIndexedByNumber[$parameterNumber];
                    $currentCombinationParameters[$requiredOnThisCombinationParameterName] = $requiredOnThisCombinationParameterName;
                }
            }
            $allParametersCombination[] = $currentCombinationParameters;
        }

        return $allParametersCombination;
    }

    private function getPossibleBinaryVariants(string $binaryMask): array
    {
        $lengths = strlen($binaryMask);
        $maxValueBinary = str_repeat(1, $lengths);
        $maxValueInteger = bindec($maxValueBinary);

        $uniqueBinaryResults = [];
        for ($currentIntegerValue = 0; $currentIntegerValue <= $maxValueInteger; $currentIntegerValue++) {
            $currentBinaryValue = decbin($currentIntegerValue);
            $currentBinaryValue = str_repeat('0', $lengths - strlen($currentBinaryValue)) . $currentBinaryValue;
            $binaryResult = ($currentBinaryValue & $binaryMask);
            $uniqueBinaryResults[$binaryResult] = $binaryResult;
        }

        return $uniqueBinaryResults;
    }

    /**
     * @param string[] $parameters
     * @param string[] $indexedOptionalityParameters
     * @return string
     */
    private function getParametersOptionalityBinaryMask(array $parameters, array $indexedOptionalityParameters): string
    {
        $maskArray = '';
        foreach ($parameters as $parameterName) {
            $maskArray .= isset($indexedOptionalityParameters[$parameterName]) ? '1' : '0';
        }

        return $maskArray;
    }

    private function isAllParametersWasRequired(array $parameters, array $indexedOptionalityParameters): bool
    {
        foreach ($parameters as $parameterName) {
            if (isset($indexedOptionalityParameters[$parameterName])) {
                return false;
            }
        }

        return true;
    }
}
