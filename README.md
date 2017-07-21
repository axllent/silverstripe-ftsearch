# Flexible Full-Text Search for SilverStripe

FTSearch enables you to easily build your own custom full-text search of all/any of your SilverStripe
website's pages and/or DataObjects.

It generally requires no modifications to your code (see [Configuration](docs/en/Configuration.md)),
and allows you to include and DataObject's relations (has_one, has_many, many_many, belongs_to etc).


## Features

- Easily determine which DataObjects to automatically index
- Include object relations (has_one, has_many, many_many, belongs_to etc)
- Supports `Versioned` DataObjects
- Full-text weight - search index has two fields, `SearchTitle` & `SearchContent`
- Saving/deleting, publishing, unpublishing of indexed or relating DataObjects triggers re-index of DataObject
- Search results return an `ArrayList` with `SearchTitle`, `SearchContent`, `Excerpt` (optionally highlighted to set length), `Link`, and the original `Object`
- BuildTask to manually re-populate your search database based on your configuration

It does not include a search interface / controller as this is generally custom, and easy to implement in your controller (see [Searching the Database](docs/en/Searching-the-Database.md)).


## Requirements

- SilverStripe ^4
- MySQL or MariaDB as your database engine


## Documentation

- [Installation](docs/en/Installation.md)
- [Configuration](docs/en/Configuration.md)
- [Searching the Database](docs/en/Searching-the-Database.md)
- [Frequently Asked Questions](docs/en/FAQ.md)
