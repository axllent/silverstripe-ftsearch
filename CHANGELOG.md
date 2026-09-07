# Changelog

Notable changes to silverstripe-ftsearch will be documented in this file.

## [2.0.0]

- Add support for Silverstripe 6 (drops Silverstripe 4 & 5)
- Add UTF-8 support for search result excerpts and highlighting
- Fix search record cleanup when a DataObject's ClassName changes
- Use bound parameters for search filter, classname and score clauses
- Update documentation, including notes on MySQL's `ft_min_word_len`

## [1.2.1]

- Replace deprecated DataExtension with Extension

## [1.2.0]

- Support for Silverstripe 5

## [1.1.2]

- Limit extremely long searches to 150 characters

## [1.1.1]

- Looser image shorttag matching due to syntax changes in upgraded websites

## [1.1.0]

- Prevent issues in some cases with Silverstripe >=4.2.2

## [1.0.8]

- Add check for $do->manyMany() returning nested array

## [1.0.7]

- Use preg_split to prevent empty strings

## [1.0.6]

- Switch to silverstripe-vendormodule

## [1.0.5]

- Ensure classname exists when attaching search trigger

## [1.0.4]

- Fix preg_quote issue

## [1.0.3]

- Strip out short-tag images

## [1.0.2]

- Skip when ClassName isn't set

## [1.0.1]

- Skip if object is not a database record

## [1.0.0]

- Initial release
- Documentation
