<?php

namespace SilverStripe\ForagerFluent\Helpers;

use SilverStripe\Forager\Service\IndexConfiguration;

class FluentHelper
{

    public static function getLocaleForIndexSuffix(string $indexSuffix): ?string
    {
        // This should only have 1 indexSuffix (because BaseReindexJob should restrictToIndexSuffixes()), but we'll
        // check properly anyway
        foreach (IndexConfiguration::singleton()->getIndexConfigurations() as $configIndexSuffix => $configuration) {
            if ($configIndexSuffix !== $indexSuffix) {
                continue;
            }

            return $configuration['locale'] ?? null;
        }

        return null;
    }

}
