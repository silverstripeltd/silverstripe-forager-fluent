# 🧺 Silverstripe Forager: Fluent support

https://github.com/tractorcow-farm/silverstripe-fluent

```yaml
# setup IndexData Contexts
SilverStripe\Core\Injector\Injector:
  SilverStripe\ForagerFluent\Service\LocaleIndexDataContext.en:
    class: SilverStripe\ForagerFluent\Service\LocaleIndexDataContext
    constructor:
      locale: en_nz
  SilverStripe\ForagerFluent\Service\LocaleIndexDataContext.mi:
    class: SilverStripe\ForagerFluent\Service\LocaleIndexDataContext
    constructor:
      locale: mi_nz
  SilverStripe\Forager\Service\IndexData:
      properties:
        contexts:
          en:
            SilverstripeForagerLiveIndexDataContext: '%$SilverStripe\Forager\Service\LiveIndexDataContext'
            SilverstripeForagerLocaleIndexDataContext: '%$SilverStripe\ForagerFluent\Service\LocaleIndexDataContext.en'
          mi:
            SilverstripeForagerLiveIndexDataContext: '%$SilverStripe\Forager\Service\LiveIndexDataContext'
            SilverstripeForagerLocaleIndexDataContext: '%$SilverStripe\ForagerFluent\Service\LocaleIndexDataContext.mi'

# set context for indexes
SilverStripe\Forager\Service\IndexConfiguration:
  indexes:
    main:
      context: en
      locale: en_NZ
      includeClasses:
        Page: &page_defaults
          fields:
            title: true
            content: true
            summary: true
        My\Other\Class: &other_class_defaults
          fields:
            title:
              property: Title
            summary:
              property: Summary
    mi:
      context: mi
      locale: mi_NZ
      includeClasses:
        Page:
          <<: *page_defaults
          My\Other\Class:
          <<: *other_class_defaults

```
