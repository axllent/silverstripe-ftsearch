# Searching the Database

First make sure you have set your configuration and populated the FTSearch table (you can run the BuildTask
to re-index everything).

From within your controller you can simply call `SearchEngine::Search($search_query);` to return all results
from your FTSearch table. This will fo a full-text search on all the data, returning all results in order of
`Score` (DESC).

`SearchEngine::Search()` returns an `ArrayList` in the following format:

```
'Score' => Int              // search scrore
'ObjectClass' => String     // DataObject's ClassName
'SearchTitle' => String     // First field/function string in your DataObject's array of fields
'SearchContent' => String   // Remaining fields/functions string in your DataObject's array of fields
'Link' => String            // The link based on the DO's `Link()` function
'Excerpt' => String         // Auto-generated excerpt from SearchContent
'Object' => DataObject      // The actual DataObject

```

When templating your results page, you can either use the field values from FTSearch, or the Objects's values.

## Limiting DataObjects

You may want to narrow down the search to only a particular type of DataObject in your FTSearch table. You can
do this by specifying a second parameter to the `SearchEngine::Search()` function to contain a ClassName or an
array of ClassNames.
```
SearchEngine::Search($search, ['ProductPage', 'DownloadPage'])
```

Just as easily, you can narrow down your results using standard DataList functions such as:
```
SearchEngine::Search($search)->filter('ObjectClass', ['ProductPage', 'DownloadPage']);
```
