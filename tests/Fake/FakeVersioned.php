<?php

namespace SilverStripe\ForagerFluent\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forager\Extensions\SearchServiceExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;

/**
 * @property string $Title
 * @mixin SearchServiceExtension
 * @mixin Versioned
 */
class FakeVersioned extends DataObject implements TestOnly
{

    private static string $table_name = 'FakeVersioned';

    private static array $extensions = [
        SearchServiceExtension::class,
        Versioned::class,
    ];

    private static array $db = [
        'Title' => 'Varchar',
    ];

    public function canView(mixed $member = null): bool
    {
        return true;
    }

}
