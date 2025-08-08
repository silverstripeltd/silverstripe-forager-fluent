<?php

namespace SilverStripe\ForagerFluent\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forager\DataObject\DataObjectDocument;
use SilverStripe\Forager\Extensions\SearchServiceExtension as BaseSearchServiceExtension;
use SilverStripe\Forager\Service\IndexConfiguration;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

/**
 * @property DataObject|FluentExtension $owner
 * @mixin FluentExtension
 */
class SearchServiceExtension extends BaseSearchServiceExtension
{

    public function addToIndexes(): void
    {
        // Find out which indexes this content needs to be sent to
        $indexSuffixes = $this->getIndexSuffixesForLocalisation();

        if (!$indexSuffixes) {
            return;
        }

        $document = DataObjectDocument::create($this->owner);

        foreach ($indexSuffixes as $indexSuffix) {
            $this->getBatchProcessor()->addDocuments($indexSuffix, [$document]);
        }
    }

    /**
     * Remove this item from search
     */
    public function removeFromIndexes(): void
    {
        // Find out which indexes this content needs to be sent to
        $indexSuffixes = $this->getIndexSuffixesForLocalisation();

        if (!$indexSuffixes) {
            return;
        }

        $document = DataObjectDocument::create($this->owner)->setShouldFallbackToLatestVersion();

        foreach ($indexSuffixes as $indexSuffix) {
            $this->getBatchProcessor()->removeDocuments($indexSuffix, [$document]);
        }
    }

    private function getIndexSuffixesForLocalisation(): ?array
    {
        $sourceLocaleCode = FluentState::singleton()->getLocale();
        $fluentApplicable = $this->owner->hasExtension(FluentExtension::class);

        // This object is not localised, which (practically) means that this content is available and shared across all
        // of our Locales. We simply want to reindex this content into all of our indexes
        if (!$fluentApplicable) {
            return IndexConfiguration::singleton()->getIndexSuffixes();
        }

        // This class is localised with Fluent, which means that we expect it to be saved with a Locale state active. If
        // there is no Locale state active, then this would mean that the base table record is what is being saved
        //
        // This is not a use case that we are going to cover, as determining what content should be sent to which index
        // becomes incredibly complicated
        if (!$sourceLocaleCode) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                sprintf(
                    'Class "%s" with ID "%s" could not be indexed, as it was saved with no Locale state active',
                    static::class,
                    $this->owner->ID,
                )
            );

            return null;
        }

        $locale = Locale::get()->filter('Locale', $sourceLocaleCode)->first();

        if (!$locale) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                sprintf(
                    'Unable to find Locale with code "%s"',
                    $sourceLocaleCode,
                )
            );

            return null;
        }

        // Start tracking which Locales use this content as their source of truth
        $updatedLocales = [$sourceLocaleCode];
        // First, find out which Locales has this active Locale as a potential Fallback
        $inheritingLocales = Locale::get()->filter('Fallbacks.ID', $locale->ID);

        // It's not enough to just assume that these Locales are sharing this content though, we now need to go through
        // each one and determine whether the source of its content in our active Locale, or if perhaps it's content
        // is retrieved from some other Locale
        foreach ($inheritingLocales as $inheritingLocale) {
            // Find out where the source of content comes from for this inheriting Locale
            $localInformation = $this->owner->LocaleInformation($inheritingLocale->Locale);

            // Nope, the source of content comes from another Locale, which means that we *do not* want to update this
            // index's content
            if ($localInformation->getSourceLocale()->Locale !== $sourceLocaleCode) {
                continue;
            }

            // Yes! This index is the source of that Locale's content, which means that we will want to update that
            // index as well
            $updatedLocales[] = $inheritingLocale->Locale;
        }

        $updatedIndexSuffixes = [];
        $indexConfigurations = IndexConfiguration::singleton()
            ->getIndexConfigurationsForClassName($this->owner->ClassName);

        // Now that we have our Locale codes, we need to find the corresponding index suffixes
        foreach ($indexConfigurations as $indexSuffix => $indexConfiguration) {
            if (!in_array($indexConfiguration['locale'] ?? '', $updatedLocales, true)) {
                continue;
            }

            $updatedIndexSuffixes[] = $indexSuffix;
        }

        return $updatedIndexSuffixes;
    }

}
