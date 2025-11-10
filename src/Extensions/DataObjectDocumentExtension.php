<?php

namespace SilverStripe\ForagerFluent\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use TractorCow\Fluent\Extension\FluentExtension;

/**
 * Extension for DataObjectDocument to handle Fluent-specific DataObject retrieval
 *
 * @property \SilverStripe\Forager\DataObject\DataObjectDocument $owner
 */
class DataObjectDocumentExtension extends Extension
{

    /**
     * Provide a fallback for retrieving Fluent-enabled DataObjects that may not exist in the current locale.
     *
     * This is particularly useful when RemoveDataObjectJob is trying to find dependent documents
     * in archived mode, where the locale context might cause objects to not be found.
     *
     * @param DataObject|null $dataObject The DataObject (will be null when this extension is called)
     * @param string $className The class name of the DataObject
     * @param int $id The ID of the DataObject
     * @param bool $isVersioned Whether the DataObject is versioned
     * @param bool $shouldFallbackToLatestVersion Whether to fallback to latest version
     */
    public function updateGetDataObject(
        ?DataObject &$dataObject,
        string $className,
        int $id,
        bool $isVersioned,
        bool $shouldFallbackToLatestVersion
    ): void {
        // Only handle if DataObject is still null and class has Fluent extension
        if ($dataObject || !DataObject::has_extension($className, FluentExtension::class)) {
            return;
        }

        // Try to get the base record without locale filtering
        $dataObject = DataObject::get($className)
            ->setDataQueryParam('Fluent.Locale', null)
            ->byID($id);

        // If still not found and versioned, try latest version without locale filtering
        if (!$dataObject && $isVersioned) {
            $dataObject = Versioned::get_latest_version($className, $id);
        }
    }

}

