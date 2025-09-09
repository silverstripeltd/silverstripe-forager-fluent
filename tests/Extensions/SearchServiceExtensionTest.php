<?php

namespace SilverStripe\ForagerFluent\Tests\Extensions;

use Page;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Forager\DataObject\DataObjectDocument;
use SilverStripe\Forager\Service\IndexConfiguration;
use SilverStripe\Forager\Jobs\IndexJob;
use SilverStripe\ForagerFluent\Tests\Fake\FakeFluentVersioned;
use SilverStripe\ForagerFluent\Tests\Fake\FakeFluentVersionedAlternative;
use SilverStripe\ForagerFluent\Tests\Fake\FakeVersioned;
use Symbiote\QueuedJobs\DataObjects\QueuedJobDescriptor;
use Symbiote\QueuedJobs\Services\QueuedJobService;
use TractorCow\Fluent\State\FluentState;

class SearchServiceExtensionTest extends SapphireTest
{

    protected static $fixture_file = 'SearchServiceExtensionTest.yml';

    protected static $extra_dataobjects = [
        FakeFluentVersioned::class,
        FakeFluentVersionedAlternative::class,
        FakeVersioned::class,
    ];

    public function testAddToIndexesPrimaryLocale(): void
    {
        FluentState::singleton()->withState(function (FluentState $state) {
            $state->setLocale('en_NZ');

            $record1 = $this->objFromFixture(FakeFluentVersioned::class, 'record1');
            $record2 = $this->objFromFixture(FakeFluentVersioned::class, 'record2');
            $record3 = $this->objFromFixture(FakeFluentVersionedAlternative::class, 'record1');
            $record4 = $this->objFromFixture(FakeFluentVersionedAlternative::class, 'record2');

            // Record 1 is localised in both locales, which means that updates to one localisation should *not* trigger
            // a reindex for the other; so, only en_NZ (which corresponds to the "main" index suffix) will want to be
            // updated
            $record1->addToIndexes();
            // Record 2 is only localised in en_NZ, which means that mi_NZ is inheriting that content as well, which
            // means that we'll want to update both indexes
            $record2->addToIndexes();
            // Record 3 is localised in both locales (so, mi_NZ is *not* inheriting content from en_NZ). But, en_NZ is
            // not configured with this class in its index, so there is effectively nowhere that we need to update index
            // content for this record
            $record3->addToIndexes();
            // Record 4 is only indexed in en_NZ, which means that mi_NZ is inheriting content from that Locale. But,
            // en_NZ is not configured with this class in its index, so this record should only reindex in "mi"
            $record4->addToIndexes();

            $jobDescriptors = QueuedJobDescriptor::get()->filter('Implementation', IndexJob::class);

            // Remembering that there is no Job for $record3, because there are no indexes for it to be updated in
            $this->assertCount(4, $jobDescriptors);

            $record1Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversioned_%d', $record1->ID);
            $record2Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversioned_%d', $record2->ID);
            $record4Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversionedalternative_%d', $record4->ID);

            $expected = [
                // One job for record1
                $record1Id => [
                    'main',
                ],
                // Two jobs for record2
                $record2Id => [
                    'main',
                    'mi',
                ],
                // No jobs for record3
                // One jobs for record4
                $record4Id => [
                    'mi',
                ],
            ];
            $results = [];

            foreach ($jobDescriptors as $jobDescriptor) {
                $data = unserialize($jobDescriptor->SavedJobData);

                /** @var DataObjectDocument $document */
                $document = array_pop($data->documents);
                $identifier = $document->getIdentifier();

                if (!array_key_exists($identifier, $results)) {
                    $results[$identifier] = [];
                }

                $results[$document->getIdentifier()][] = $data->indexSuffix;
            }

            $this->assertEqualsCanonicalizing($expected, $results);
        });
    }

    public function testAddToIndexesFallbackLocale(): void
    {
        FluentState::singleton()->withState(function (FluentState $state) {
            $state->setLocale('mi_NZ');

            $record1 = $this->objFromFixture(FakeFluentVersioned::class, 'record1');
            $record2 = $this->objFromFixture(FakeFluentVersioned::class, 'record2');
            $record3 = $this->objFromFixture(FakeFluentVersionedAlternative::class, 'record1');
            $record4 = $this->objFromFixture(FakeFluentVersionedAlternative::class, 'record2');

            // Record 1 is localised in both locales, furthermore, mi_NZ has no other Locales that depend on it, so we
            // should only see "mi" as the index to be updated
            $record1->addToIndexes();
            // Record 2 has valid content which it inherits content from en_NZ, so the "mi" index will want to update
            $record2->addToIndexes();
            // Record 3 will update in the "mi" index, because it is explicitly localed in mi_NZ
            $record3->addToIndexes();
            // Record 4 has valid content which it inherits content from en_NZ, so the "mi" index will want to update
            $record4->addToIndexes();

            $jobDescriptors = QueuedJobDescriptor::get()->filter('Implementation', IndexJob::class);

            // Each record should have a Job
            $this->assertCount(4, $jobDescriptors);

            $record1Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversioned_%d', $record1->ID);
            $record2Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversioned_%d', $record2->ID);
            $record3Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversionedalternative_%d', $record3->ID);
            $record4Id = sprintf('silverstripe_foragerfluent_tests_fake_fakefluentversionedalternative_%d', $record4->ID);

            $expected = [
                $record1Id => [
                    'mi',
                ],
                $record2Id => [
                    'mi',
                ],
                $record4Id => [
                    'mi',
                ],
                $record3Id => [
                    'mi',
                ],
            ];
            $results = [];

            foreach ($jobDescriptors as $jobDescriptor) {
                $data = unserialize($jobDescriptor->SavedJobData);

                /** @var DataObjectDocument $document */
                $document = array_pop($data->documents);
                $identifier = $document->getIdentifier();

                if (!array_key_exists($identifier, $results)) {
                    $results[$identifier] = [];
                }

                $results[$document->getIdentifier()][] = $data->indexSuffix;
            }

            $this->assertEqualsCanonicalizing($expected, $results);
        });
    }

    public function testAddToIndexesInheritance(): void
    {
        FluentState::singleton()->withState(function (FluentState $state) {
            $state->setLocale('en_NZ');

            $record = FakeFluentVersioned::create();
            $record->Title = 'English Title';
            $record->write();
            $record->publishRecursive();

            $jobDescriptors = QueuedJobDescriptor::get()->filter('Implementation', IndexJob::class);

            $this->assertCount(2, $jobDescriptors);

            $indexSuffixes = [];

            foreach ($jobDescriptors as $jobDescriptor) {
                $data = unserialize($jobDescriptor->SavedJobData);

                $indexSuffixes[] = $data->indexSuffix;
            }

            $this->assertEqualsCanonicalizing(
                [
                    'main',
                    'mi'
                ],
                $indexSuffixes
            );
        });
    }

    public function testAddToIndexesLocalised(): void
    {
        FluentState::singleton()->withState(function (FluentState $state) {
            $state->setLocale('mi_NZ');

            $record = FakeFluentVersioned::create();
            $record->Title = 'Te Reo Title';
            $record->write();
            $record->publishRecursive();

            $jobDescriptors = QueuedJobDescriptor::get()->filter('Implementation', IndexJob::class);

            $this->assertCount(1, $jobDescriptors);

            $indexSuffixes = [];

            foreach ($jobDescriptors as $jobDescriptor) {
                $data = unserialize($jobDescriptor->SavedJobData);

                $indexSuffixes[] = $data->indexSuffix;
            }

            $this->assertEqualsCanonicalizing(
                [
                    'mi'
                ],
                $indexSuffixes
            );
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        // The shutdown handler doesn't play nicely with SapphireTest's database handling
        QueuedJobService::config()->set('use_shutdown_function', false);

        // The field configuration that we want to use for our classes and tests
        IndexConfiguration::config()->set(
            'indexes',
            [
                'main' => [
                    'locale' => 'en_NZ',
                    'includeClasses' => [
                        Page::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                        FakeFluentVersioned::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                        FakeVersioned::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                    ],
                ],
                'mi' => [
                    'locale' => 'mi_NZ',
                    'includeClasses' => [
                        Page::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                        FakeFluentVersioned::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                        FakeFluentVersionedAlternative::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                        FakeVersioned::class => [
                            'fields' => [
                                'title' => true,
                            ],
                        ],
                    ],
                ],
            ]
        );

        IndexConfiguration::config()->set('crawl_page_content', false);
    }

}
