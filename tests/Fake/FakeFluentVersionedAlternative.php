<?php

namespace SilverStripe\ForagerFluent\Tests\Fake;

use SilverStripe\Dev\TestOnly;
use SilverStripe\Forager\Extensions\SearchServiceExtension as BaseSearchServiceExtension;
use SilverStripe\ForagerFluent\Extensions\SearchServiceExtension;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use TractorCow\Fluent\Extension\FluentVersionedExtension;

/**
 * @property string $Title
 * @mixin FluentVersionedExtension
 * @mixin SearchServiceExtension
 * @mixin Versioned
 */
class FakeFluentVersionedAlternative extends DataObject implements TestOnly
{

    private static string $table_name = 'FakeFluentVersionedAlternative';

    private static array $extensions = [
        BaseSearchServiceExtension::class,
        Versioned::class,
        FluentVersionedExtension::class,
    ];

    private static array $db = [
        'Title' => 'Varchar',
    ];

    private static array $field_include = [
        'ShowInSearch',
    ];

    public function canView(mixed $member = null): bool
    {
        return true;
    }

}
