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

## Why aren't short words (or common words) being found?

MySQL / MariaDB's MyISAM full-text search ignores any word shorter than `ft_min_word_len`, which
defaults to **4** characters. Words like `cat`, `AI` or `3D` will never appear in search results
until this is lowered.

It also ignores a built-in list of "stopwords" (common words such as `and`, `the`, `about`, `you`,
etc), which cannot be changed without providing a custom stopword file via `ft_stopword_file`.

To lower the minimum word length, add the following to your MySQL / MariaDB server configuration
(eg: `/etc/mysql/my.cnf` or a file in `/etc/mysql/conf.d/`) under the `[mysqld]` section:

```ini
[mysqld]
ft_min_word_len = 2
```

Restart the database server, then rebuild all existing FULLTEXT indexes (the setting only applies
to indexes built after the change):

```shell
vendor/bin/sake tasks:RebuildFTSearchDB
```

You can confirm the active value with:

```sql
SHOW VARIABLES LIKE 'ft_min_word_len';
```

Note: `ft_min_word_len` applies to MyISAM tables (which this module uses). InnoDB uses a separate
setting called `innodb_ft_min_token_size` (default `3`).


## My DataObject isn't registering changes to the full-text search database

Any custom `onBeforeWrite`, `onAfterWrite`, `onBeforeDelete`, `onBeforePublish`, `onBeforeUnpublish` functions
you have created in your DataObject must extend themselves (eg: `$this->extend('onBeforeWrite')`). eg:

```php
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // your custom functionality here
        $this->extend('onBeforeWrite');
    }
```
