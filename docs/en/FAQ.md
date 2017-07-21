# Frequently Asked Questions

## How do I add `has_one`, `has_many`, etc values

You need to create a custom function in your DataObject to generate a string, and configure
FTSearch to use that string.

```yaml
Axllent\FTSearch\SearchEngine:
  data_objects:
    StaffPage:
      - Title
      - Content
      - getFTSearchData
```

A function may be something like:
```php
    public function getFTSearchData()
    {
        $content = [];
        foreach ($this->StaffMembers() as $staff_member) {
            array_push($content, $staff_member->Title . ' ' . $staff_member->Profile);
        }
        return implode(' ', $content);
    }
```


## My DataObject isn't registering changes to the full-text search database

Any custom `onbeforeWrite`, `onAfterWrite`, `onBeforeDelete`, `onBeforePublish`, `onBeforeUnpublish` functions
you have created in your DataObject must extend themselves (eg: `$this->extend('onbeforeWrite')`). eg:

```php
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // your custom functionality here
        $this->extend('onBeforeWrite');
    }
```
