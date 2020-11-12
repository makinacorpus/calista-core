<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View\PropertyRenderer;

class DateTypeRenderer implements TypeRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getSupportedTypes(): array
    {
        return [
            'date',
            'interval',
            'time',
            'timestamp',
            \DateInterval::class,
            \DateTime::class,
            \DateTimeImmutable::class,
            \DateTimeInterface::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function render(string $type, $value, array $options): ?string
    {
        if (null === $value || '' === $value) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->doRenderDateTime($value, $options);
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->doRenderInterval($value, $options);
        }

        // Handle UNIX timestamp gracefully.
        if (\is_int($value) || (\is_string($value) && \ctype_digit($value))) {
            try {
                return $this->doRenderDateTime(new \DateTimeImmutable('@' . $value), $options);
            } catch (\Throwable $e) {
                return null;
            }
        }

        // Handle all other formats gracefully, as long as PHP standard library
        // succeeds in parsing them arbitrarily.
        if (\is_string($value)) {
            try {
                return $this->doRenderDateTime(new \DateTimeImmutable($value), $options);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    private function doRenderDateTime(\DateTimeInterface $value, array $options)
    {
        switch ($options['date_format']) {
            // @todo handle INTL
            // @todo handle date constants (a few format, eg. atom, rfcXXXX, etc...)

            default:
                return $value->format($options['date_format']);
        }
    }

    /**
     * @fixme french inside, needs translation, sorry...
     */
    private function doRenderInterval(\DateInterval $value, array $options)
    {
        $pieces = [];
        if ($value->y) {
            $pieces[] = $value->y . " an(s)";
        }
        if ($value->m) {
            $pieces[] = $value->m . " mois";
        }
        if ($value->d) {
            $pieces[] = $value->d . " jour(s)";
        }
        if ($value->h) {
            $pieces[] = $value->h . " heure(s)";
        }
        if ($value->i) {
            $pieces[] = $value->i . " minute(s)";
        }
        if ($value->s) {
            $pieces[] = $value->s . " seconde(s)";
        }

        return \implode(' ', $pieces);
    }
}
