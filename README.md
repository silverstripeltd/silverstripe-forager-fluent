# 🧺 Silverstripe Forager: Fluent support

https://github.com/tractorcow-farm/silverstripe-fluent

```yaml
SilverStripe\Forager\Service\IndexConfiguration:
  indexes:
    main:
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
      locale: mi_NZ
      includeClasses:
        Page:
          <<: *page_defaults
          My\Other\Class:
          <<: *other_class_defaults

```
