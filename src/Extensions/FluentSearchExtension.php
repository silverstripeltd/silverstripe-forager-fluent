<?php

namespace SilverStripe\ForagerFluent\Extensions;

use Psr\Log\LoggerInterface;
use SilverStripe\Core\Extension;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forager\Extensions\SearchServiceExtension;
use SilverStripe\Forager\Interfaces\DataObjectDocumentInterface;
use SilverStripe\Forager\Service\IndexConfiguration;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

/**
 * @property SearchServiceExtension $owner
 */
class FluentSearchExtension extends Extension
{

    public function updateAddToIndexes(array &$indexSuffixes, DataObjectDocumentInterface $doc): void
    {
        $this->updateSuffixesForLocalisedDoc($indexSuffixes, $doc);
    }

    public function updateRemoveFromIndexes(array &$indexSuffixes, DataObjectDocumentInterface $doc): void
    {
        $this->updateSuffixesForLocalisedDoc($indexSuffixes, $doc);
    }

    private function updateSuffixesForLocalisedDoc(array &$indexSuffixes, DataObjectDocumentInterface $doc): void
    {
        // Find out which indexes this content needs to be updated in - may be smaller than configured
        $suffixesForLocalisation = $this->getIndexSuffixesForLocalisation($doc);

        if ($suffixesForLocalisation === null) {
            // null means fall back to forager standard behaviour
            return;
        }

        // note this may be an empty array if no updates are required
        $indexSuffixes = $suffixesForLocalisation;
    }

    /**
     * Identifies the indexes a localised object should be updated in based on the current context.
     * This is normally a reduced list as objects can be published etc in a single locale
     *
     * @param DataObjectDocumentInterface $doc
     * @return array|null
     */
    private function getIndexSuffixesForLocalisation(DataObjectDocumentInterface $doc): ?array
    {
        $currentLocaleCode = FluentState::singleton()->getLocale();
        $dataObject = $doc->getDataObject();
        $fluentApplicable = $dataObject->hasExtension(FluentExtension::class);

        // if we don't have a dataobject at this stage we can't look anything up so just bail
        if (!$dataObject) {
            return null;
        }

        // This object is not localised, which (practically) means that this content is available and shared across all
        // of our Locales. We simply want to fall back to what's configured for this class
        if (!$fluentApplicable) {
            return null;
        }

        // This class is localised with Fluent, which means that we expect it to be saved with a Locale state active. If
        // there is no Locale state active, then this would mean that the base table record is what is being saved
        //
        // This is not a use case that we are going to cover, as determining what content should be sent to which index
        // becomes incredibly complicated and in this case we will skip indexing this object because we don't want to
        // potentially override other locales' content
        if (!$currentLocaleCode) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                sprintf(
                    'Class "%s" with ID "%s" could not be indexed, as it was saved with no Locale state active',
                    $dataObject->ClassName,
                    $dataObject->ID,
                )
            );

            return [];
        }

        $locale = Locale::get()->filter('Locale', $currentLocaleCode)->first();

        if (!$locale) {
            Injector::inst()->get(LoggerInterface::class)->warning(
                sprintf(
                    'Unable to find Locale with code "%s"',
                    $currentLocaleCode,
                )
            );

            return [];
        }

        // Start tracking which Locales use this content as their source of truth
        $updatedLocaleCodes = [$currentLocaleCode];
        // First, find out which Locales has this active Locale as a potential Fallback
        $inheritingLocales = Locale::get()->filter('Fallbacks.ID', $locale->ID);

        // It's not enough to just assume that these Locales are sharing this content though, we now need to go through
        // each one and determine whether the source of its content in our active Locale, or if perhaps it's content
        // is retrieved from some other Locale
        foreach ($inheritingLocales as $inheritingLocale) {
            // Find out where the source of content comes from for this inheriting Locale
            $localInformation = $dataObject->LocaleInformation($inheritingLocale->Locale);

            // Nope, the source of content comes from another Locale (normally the locale itself),
            // which means that we *do not* want to update this index's content
            if ($localInformation->getSourceLocale()->Locale !== $currentLocaleCode) {
                continue;
            }

            // Yes! This locale inherits content from the current Locale, which means that we will want to update that
            // index as well
            $updatedLocaleCodes[] = $inheritingLocale->Locale;
        }

        $updatedIndexSuffixes = [];
        $indexConfigurations = IndexConfiguration::singleton()
            ->getIndexConfigurationsForClassName($dataObject->ClassName);

        // Now that we have our Locale codes, we need to find the corresponding index suffixes
        foreach ($indexConfigurations as $indexSuffix => $indexConfiguration) {
            $indexLocaleCode = $indexConfiguration[IndexDataExtension::INDEX_LOCALE_PROP] ?? '';

            if (!in_array($indexLocaleCode, $updatedLocaleCodes, true)) {
                continue;
            }

            $updatedIndexSuffixes[] = $indexSuffix;
        }

        return $updatedIndexSuffixes;
    }

}
