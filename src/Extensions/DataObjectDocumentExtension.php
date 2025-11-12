<?php

namespace SilverStripe\ForagerFluent\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\State\FluentState;

/**
 * Extension for DataObjectDocument to handle Fluent-specific DataObject retrieval
 *
 * @property \SilverStripe\Forager\DataObject\DataObjectDocument $owner
 */
class DataObjectDocumentExtension extends Extension
{

    /**
     * Update the isPublished check to be Fluent-aware, considering fallback locales.
     *
     * This method is called from DataObjectDocument::shouldIndex() to allow
     * content published in fallback locales to be indexed in dependent locales.
     *
     * @param DataObject $dataObject
     * @param bool $isPublished
     */
    public function updateIsPublishedForSearch(DataObject $dataObject, bool &$isPublished): void
    {
        // If the object doesn't have Fluent extension, use default behaviour
        if (!$dataObject->hasExtension(FluentExtension::class)) {
            return;
        }

        $currentLocaleCode = FluentState::singleton()->getLocale();

        if (!$currentLocaleCode) {
            return;
        }

        // Get locale information for this DataObject in the current locale
        $localInformation = $dataObject->LocaleInformation($currentLocaleCode);

        // Check if the object exists in any locale
        if (!$localInformation->Exists()) {
            $isPublished = false;

            return;
        }

        // Check if it's published in the source locale
        if ($localInformation->IsPublished(true)) {
            $isPublished = true;

            return;
        }

        // If not published in this locale, check if the source locale is published
        $sourceLocale = $localInformation->getSourceLocale();

        if (!$sourceLocale || $sourceLocale->Locale === $currentLocaleCode) {
            // No source locale or source is current locale (already checked above)
            $isPublished = false;

            return;
        }

        // Check if the content is published in the source locale
        $sourceLocalInformation = $dataObject->LocaleInformation($sourceLocale->Locale);

        $isPublished = $sourceLocalInformation->Exists() && $sourceLocalInformation->IsPublished(true);
    }

}
