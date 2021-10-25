<?php

declare(strict_types=1);

namespace MakinaCorpus\Calista\View;

use MakinaCorpus\Calista\Datasource\PropertyDescription;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Represents a property, uses the property info to display it.
 */
class PropertyView
{
    private string $name;
    private ?string $type = null;
    private array $options = [];

    public function __construct(string $name, ?string $type = null, array $options = [])
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $this->name = $name;
        $this->type = $type ?? $options['type'] ?? null;
    }

    public static function fromDescription(PropertyDescription $description, array $options = []): self
    {
        return new self(
            $description->getName(),
            $description->getType(),
            $options + ['label' => $description->getLabel()] + $description->getDefaultViewOptions(),
        );
    }

    /**
     * Return a new instance with overriden options.
     */
    public function withOptions(array $overrides): self
    {
        if (!$overrides) {
            return $this;
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $ret = clone $this;
        $ret->options = $resolver->resolve($overrides + $this->options);

        return $ret;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'bool_as_int' => false,
            'bool_value_false' => "No",
            'bool_value_true' => "Yes",
            'callback' => null,
            'collection_separator' => ', ',
            'date_format' => 'Y-m-d H:i:s',
            'decimal_precision' => 2,
            'decimal_separator' => '.',
            'hidden' => false,
            'label' => null,
            'string_ellipsis' => true,
            'string_maxlength' => 100,
            'string_raw' => false,
            'thousand_separator' => ',',
            'type' => null,
            // Property access component will be used to fetch value, but in
            // case you have heavy performance problems, the property view can
            // give its own value getter, which will bypass then the property
            // access slow getValue() function.
            // If value accessor is a string, it will be called as an object
            // method, if it is a callbable, it will just be called.
            'value_accessor' => null,
            // A virtual fields means the value will be computed by the display
            // callback without the need of fetching the value first, this can
            // only be set to true when the display callback knows how to fetch
            // the property by itself.
            'virtual' => false,
        ]);

        $resolver->setAllowedTypes('bool_as_int', ['bool']);
        $resolver->setAllowedTypes('bool_value_false', ['string']);
        $resolver->setAllowedTypes('bool_value_true', ['string']);
        $resolver->setAllowedTypes('callback', ['null', 'callable', 'string']);
        $resolver->setAllowedTypes('collection_separator', ['null', 'string']);
        $resolver->setAllowedTypes('date_format', ['null', 'string']);
        $resolver->setAllowedTypes('decimal_precision', ['null', 'int']);
        $resolver->setAllowedTypes('decimal_separator', ['null', 'string']);
        $resolver->setAllowedTypes('label', ['null', 'string']);
        $resolver->setAllowedTypes('string_ellipsis', ['null', 'bool', 'string']);
        $resolver->setAllowedTypes('string_maxlength', ['null', 'int']);
        $resolver->setAllowedTypes('string_raw', ['null', 'bool']);
        $resolver->setAllowedTypes('thousand_separator', ['null', 'string']);
        $resolver->setAllowedTypes('type', ['null', 'string']);
        $resolver->setAllowedTypes('value_accessor', ['null', 'string', 'callable']);
        $resolver->setAllowedTypes('virtual', ['bool']);
    }

    /**
     * Create clone with new name.
     */
    public function rename(string $name, ?string $label = null, array $optionsOverrides = []): self
    {
        $ret = clone $this;
        $ret->options = $optionsOverrides ?? $this->options;
        $ret->name = $name;
        $ret->label = $label ?? $this->label;

        return $ret;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get label, return property name if missing.
     */
    public function getLabel(): string
    {
        return $this->options['label'] ?? $this->name;
    }

    /**
     * Is this property virtual.
     */
    public function isVirtual(): bool
    {
        return (bool)$this->options['virtual'] ?? false;
    }

    /**
     * Has this property a type.
     */
    public function hasType(): bool
    {
        return null !== $this->type;
    }

    /**
     * Get property type.
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * Is property hidden (used for REST API only as of now).
     */
    public function isHidden(): bool
    {
        return (bool)$this->options['hidden'] ?? false;
    }

    /**
     * Get display options.
     */
    public function getOptions(): array
    {
        return $this->options + ['name' => $this->name];
    }
}
