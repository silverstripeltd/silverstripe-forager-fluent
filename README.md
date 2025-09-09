# 🧺 Silverstripe Forager: Fluent support

Support for the [fluent](https://github.com/tractorcow-farm/silverstripe-fluent) module.

This module sets the Locale context for indexing operations. The core assumption is that one index has content from one locale.
I does this with IndexDataContextProvider implementations to set the Locale context for indexing operations. Refer to the [index context](https://github.com/silverstripeltd/silverstripe-forager/blob/main/docs/en/03_usage.md#index-contexts) documentation for more information.

Install with `composer require silverstripe/forager-fluent`.

To configure add the `fluent` context and a `locale` property to your index configurations:

```yaml
SilverStripe\Forager\Service\IndexConfiguration:
  indexes:
    main:
      context: fluent
      locale: en_NZ
      includeClasses:
        ...
    mi:
      context: fluent
      locale: mi_NZ
      includeClasses:
        ...
```

This will use the default `fluent` context created by this module to set the locale. It will also use the LiveIndexDataContext from the forager module as seen in the configuration below:

```yaml
---
Name: forager-fluent-context
---
SilverStripe\Core\Injector\Injector:
  SilverStripe\Forager\Service\IndexData:
      properties:
        contexts:
          fluent:
            SilverstripeForagerLiveIndexDataContext: '%$SilverStripe\Forager\Service\LiveIndexDataContext'
            SilverstripeForagerLocaleIndexDataContext: '%$SilverStripe\ForagerFluent\Service\LocaleIndexDataContext'

```

You can customise this context by overriding it in yaml or providing a new custom [index context](https://github.com/silverstripeltd/silverstripe-forager/blob/main/docs/en/03_usage.md#index-contexts) implementation.


## Search service extension

This module will replace the SearchServiceExtension provided by the forager module. The [replacement extension](./src/Extensions/SearchServiceExtension.php) will create a job for each locale (and therefore index) an object has a localisation for.
