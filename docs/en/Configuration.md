# Configuration of FTSearch

FTSearch is configured with a `yaml` file.

```yaml
Axllent\FTSearch\SearchEngine:
  search_limit: 1000
  title_score: 2
  content_score: 1
  min_score: 0
  excerpt_length: 200
  excerpt_ending: '...'
  excerpt_css_class: 'highlight'
  exclude_classes:
    - PrivatePage
  data_objects:
    Page:
      - Title
      - Content
    StaffPage:
      - Title
      - Content
      - getFTSearchData
```


## data_objects [array]

The list of dataObjects you wish to index. Each DataObject requires an array of fields or functions to generate
the search content from. Classes are inherited, so if you use `Page`, any class that extends Page will be included.

There are three DataObject requirements for any DataObject to be included to FTSearch:

* The DataObject must have a `Link()` function (else how will you link from the search to the result?). DataObjects without a `Link()` function are ignored.
* Any custom `onbeforeWrite`, `onAfterWrite`, `onBeforeDelete`, `onBeforePublish`, `onBeforeUnpublish` functions
you have created must extend themselves (eg: `$this->extend('onbeforeWrite')`). Failure to do this mean that the
FTSearch database won't be automatically updated when DataObjects change.
* The DataObject does not have a `->ShowInSearch=0` value. This allows you to manually exclude pages from within your CMS,
as well as set the `ShowInSearch` in any DataObject to 0 if you want it to be excluded.


The **first** value for every class is known as the `SearchTitle`, and by default carries twice the "search weight"
compared to the remaining fields (`SearchContent`).

A function may also be used, for instance when values are required from an Object's `has_one` or `has_many` fields.
The idea of each field or function is to return a string.

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

Whenever the Staff page is updated, or when a StaffMember is edited, the getFTSearchData() will be used to re-populate
the FTSearch database.


## search_limit [int]

The maximum results to return (defaults to 1000). Set to false or 0 to ignore. If you wish to display 10 results
then it is advisable to rather use a `->limit()` in your controller rathar than change this value.


## title_score / content_score [int]

The full-text search will by default prioritise `SearchTitle` results (score x 2) over `SearchContent`.
This means that results with matching SearchTitle get a higher score than results that only match
by SearchContent.


## min_score [int]

The minimum score results should have. Use sparingly as results can range from 0.000001 upwards depending on search.


## excerpt_length [int]

The number of characters (+-) to show for search result excepts of the `SearchContent`.


## excerpt_ending [int]

Search result excerpts are traditionally ended with a `...`.


## excerpt_css_class [string|false]

If you are using the built-in `$searchresult->Excerpt` in your search results, matching words are sutomatically highlighted
with `<span class="highlight">matching_word</span>`. You can customise this by setting your own css class name, or setting
it to false to ignore.


## exclude_classes [array]

Exclude certain classes from the database. Some common classes are already excluded by default, namely:

* `SilverStripe\Assets\Folder`
* `SilverStripe\CMS\Model\RedirectorPage`
* `SilverStripe\CMS\Model\VirtualPage`
* `SilverStripe\ErrorPage\ErrorPage`

You can add to this list. These classes are **not** inherited, so if you extend any of these, you need to exlude that as well
(or ensure they include a `ShowInSearch=0` value).
