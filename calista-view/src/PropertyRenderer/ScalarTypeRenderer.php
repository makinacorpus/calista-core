<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\PropertyRenderer;

class ScalarTypeRenderer implements TypeRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(): array
    {
        return [
            'bool',
            'float',
            'int',
            'null', // Default renderer when nothing else was found.
            'string',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $type, $value, array $options): ?string
    {
        if (null === $value) {
            return null;
        }

        switch ($type) {

            case 'int':
                return $this->renderInt($value, $options);

            case 'float':
                return $this->renderFloat($value, $options);

            case 'bool':
                return $this->renderBool($value, $options);

            default:
                return $this->valueToString($value, $options);
        }
    }

    private function valueToString($value, array $options): ?string
    {
        if (null === $value) {
            return '';
        }

        if (\is_string($value)) {
            return $this->renderString($value, $options);
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            return $this->renderString((string) $value, $options);
        }

        return null;
    }

    private function renderInt($value, array $options = []): ?string
    {
        return null === $value ? '' : \number_format($value, 0, '.', $options['thousand_separator']);
    }

    private function renderFloat($value, array $options): ?string
    {
        return \number_format($value, $options['decimal_precision'], $options['decimal_separator'], $options['thousand_separator']);
    }

    private function renderBool($value, array $options): ?string
    {
        if ($options['bool_as_int']) {
            return $value ? "1" : "0";
        }

        if ($value) {
            if ($options['bool_value_true']) {
                return $options['bool_value_true'];
            }

            return "true"; // @todo translate

        } else {
            if ($options['bool_value_false']) {
                return $options['bool_value_false'];
            }

            return "false"; // @todo translate
        }
    }

    private function renderString($value, array $options): ?string
    {
        $value = \strip_tags($value);

        if (0 < $options['string_maxlength'] && \strlen($value) > $options['string_maxlength']) {
            $value = \substr($value, 0, $options['string_maxlength']);

            if ($options['string_ellipsis']) {
                if (\is_string($options['string_ellipsis'])) {
                    $value .= $options['string_ellipsis'];
                } else {
                    $value .= '...';
                }
            }
        }

        return $value;
    }
    
}
