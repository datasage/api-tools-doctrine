# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 2.1.1 - TBD

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [#289](https://github.com/zfcampus/zf-apigility-doctrine/pull/289) fixes
  configuration keys, which resolves issue with Apigility Admin and populating
  forms from config file and writing duplicated values into config file.

- [#290](https://github.com/zfcampus/zf-apigility-doctrine/pull/290) fixes
  Doctrine Resource listener attached via config. These are now correctly
  dispatched.

- [#298](https://github.com/zfcampus/zf-apigility-doctrine/pull/298) fixes
  data passed to listener on patch method.

- [#293](https://github.com/zfcampus/zf-apigility-doctrine/pull/293) fixes
  binding parameters with type. In case of custom field type php value was not
  converted to database value.

## 2.1.0 - 2016-10-17

### Added

- [#267](https://github.com/zfcampus/zf-apigility-doctrine/pull/267) adds
  support for version 3 releases of zend-servicemanager and zend-eventmanager,
  while retaining compatibility for v2 releases.

### Changes

- [#267](https://github.com/zfcampus/zf-apigility-doctrine/pull/267) exposes the
  module to [zendframework/zend-component-installer](https://github.com/zendframework/zend-component-installer),
  exposing both `ZF\Apigility\Doctrine\Admin` and
  `ZF\Apigility\Doctrine\Server`. The former should be isntalled in the
  development configuration, and the latter in your application modules.
- [#267](https://github.com/zfcampus/zf-apigility-doctrine/pull/267) updates
  dependency requirements for the following modules and components:
  - zfcampus/zf-apigilty-admin ^1.5
  - phpro/zf-doctrine-hydration-module ^3.0
  - doctrine/DoctrineModule ^1.2
  - doctrine/DoctrineORMModule ^1.1
  - doctrine/DoctrineMongoODMModule ^0.11

### Deprecated

- Nothing.

### Removed

- [#267](https://github.com/zfcampus/zf-apigility-doctrine/pull/267) removes
  support for PHP 5.5.

### Fixed

- [#267](https://github.com/zfcampus/zf-apigility-doctrine/pull/267) adds a ton
  of tests to the module, and fixes a number of issues encountered.