<?php

namespace SilverStripe\ForagerFluent\Jobs;

use InvalidArgumentException;
use SilverStripe\Forager\Jobs\IndexJob as BaseIndexJob;
use SilverStripe\ForagerFluent\Helpers\FluentHelper;
use TractorCow\Fluent\State\FluentState;

/**
 * @property string|null $locale
 */
class IndexJob extends BaseIndexJob
{

    public function setup(): void
    {
        $this->extend('onBeforeFluentSetup');

        if (!$this->getIndexSuffix()) {
            throw new InvalidArgumentException('An index suffix must be specified');
        }

        $locale = FluentHelper::getLocaleForIndexSuffix($this->getIndexSuffix());

        if (!$locale) {
            throw new InvalidArgumentException(
                sprintf('Unable to find Locale for index with suffix "%s"', $this->getIndexSuffix())
            );
        }

        $this->setLocale($locale);

        // Wrap our process in FluentState, so that the state is automatically reset at the end of the process
        FluentState::singleton()->withState(function (FluentState $state): void {
            // Set our Fluent state. This will mean that DataObjects are fetched for this specific Locale
            $state->setLocale($this->getLocale());

            parent::setup();
        });

        $this->extend('onAfterFluentSetup');
    }

    public function process(): void
    {
        $this->extend('onBeforeFluentProcess');

        // Wrap our process in FluentState, so that the state is automatically reset at the end of the process
        FluentState::singleton()->withState(function (FluentState $state): void {
            // Set our Fluent state. This will mean that DataObjects are fetched for this specific Locale (and with the
            // content from that Locale)
            $state->setLocale($this->getLocale());

            parent::process();
        });

        $this->extend('onAfterFluentProcess');
    }

    public function getLocale(): ?string
    {
        if (is_bool($this->locale)) {
            return null;
        }

        return $this->locale;
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }

}
