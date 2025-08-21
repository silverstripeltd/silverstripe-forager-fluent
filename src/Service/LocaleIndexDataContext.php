<?php

namespace SilverStripe\ForagerFluent\Service;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forager\Interfaces\IndexDataContextProvider;
use SilverStripe\Forager\Service\IndexData;
use SilverStripe\ForagerFluent\Extensions\IndexDataExtension;
use TractorCow\Fluent\State\FluentState;

class LocaleIndexDataContext implements IndexDataContextProvider
{
    use Injectable;

    public function __construct(
        private string $locale
    )
    {}

    public function getContext(): callable {
        return function (callable $next, IndexData $indexData): mixed {
            return FluentState::singleton()->withState(function (FluentState $state) use ($next, $indexData): mixed {
                $state->setIsFrontend(true);
                /** @var IndexDataExtension $indexData */
                $state->setLocale($indexData->getLocale());

                return $next();
            });
        };
    }
}
