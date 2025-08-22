<?php

namespace SilverStripe\ForagerFluent\Tests\Extensions;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\ForagerFluent\Extensions\IndexDataExtension;


class IndexDataExtensionTest extends SapphireTest
{

    public function testGetLocale(): void
    {
        $extension = new IndexDataExtension();

        $owner = new class {
            public function getData(): array {
                return [
                    'locale' => 'en_nz'
                ];
            }
        };

        $extension->setOwner($owner);

        $this->assertEquals('en_nz', $extension->getLocale());

        $owner = new class {
            public function getData(): array {
                return [];
            }

            public function getSuffix(): string {
                return 'main';
            }
        };

        $extension->setOwner($owner);
        $this->expectExceptionMessage('No locale found on index suffix: "main"');
        $extension->getLocale();
    }

}

