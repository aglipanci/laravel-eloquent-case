## Unreleased

### Added
- `whenColumn()` to compare two columns in a WHEN condition.
- `thenColumn()` and `elseColumn()` to return a column value instead of a literal.
- `count()`, `avg()`, `min()`, `max()` and a generic `aggregate()` method alongside the existing `sum()`.
- IDE auto-completion for the `CaseBuilder` facade via `@method` annotations.

### Fixed
- `toRaw()` no longer produces corrupted SQL when a binding value contains a `?` character.
- Calling `else()` after `elseRaw('0')` (or any falsy raw ELSE) now correctly throws, instead of silently overwriting the ELSE clause.
- `elseRaw()` now applies the same position/uniqueness guards as `else()`.
- The Pint configuration file is now named `pint.json` (previously `ping.json`, which Pint ignored).
- CI now actually tests every supported Laravel version (9–13); `orchestra/testbench` constraints previously skipped Laravel 10 and 11.

### Changed
- Source files now declare `strict_types=1` and native parameter types.
- PHPStan level raised from 4 to 8.
- Laravel support: 3.x versions dropped Laravel 8; requires PHP 8.2+.

## 3.1.0
- Adding support for NULL case values
- Adding support for dot-separation to specify table name and column name

## 3.0.0
- Adding support for Laravel 11

## 2.0.0
- Adding support for Laravel 10

## 1.4.0
- Feature: Add `caseRaw` support

## 1.3.0

- Bugfix: make the facade to always resolve a new instance of the CaseBuilder object.

## 1.2.0

- Bugfix: create a new instance of the CaseBuilder in the SC bind.

## 1.1.0

- Bugfix: fix cases where elseRaw is equal to 0.

## 1.0.0

- Initial release.

## 0.0.1

- Experimental initial release.
