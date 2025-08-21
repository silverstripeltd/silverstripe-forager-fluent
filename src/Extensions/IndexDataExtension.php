<?php

namespace SilverStripe\ForagerFluent\Extensions;

use Aws\Arn\Exception\InvalidArnException;
use SilverStripe\Core\Extension;
use SilverStripe\Forager\Service\IndexData;

/**
 * @property IndexData $owner
 */
class IndexDataExtension extends Extension {

    public const string INDEX_LOCALE_PROP = 'locale';

    public function getLocale(): string
    {
        $data = $this->owner->getData();
        if (!array_key_exists(self::INDEX_LOCALE_PROP, $data)) {
            throw new InvalidArnException(sprintf('No locale found on index suffix: "%s"', ));
        }

        return $data[self::INDEX_LOCALE_PROP] ?? '';
    }
}
