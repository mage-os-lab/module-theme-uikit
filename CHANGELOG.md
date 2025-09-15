# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## 1.5.0
### Fixed
- Fix broken uikit_whitelist xsd file on developer mode, add new resolveExpression pattern on CSS class post processor.

## 1.4.1
### Fixed
- Fix minor error on css parsing. Previously was keeping all rule's selectors if at least one wasn't containing .uk-' prefixed classes.   

## 1.4.0
### Fixed
- Fix error on xml compilation for developer mode

## 1.3.1
### Updated
- Avoid fullPath get on theme, prefer theme_path prefixed with 'frontend'

## 1.3.0
### Updated
- Avoid usage of uikit_whitelist.xml for themes using UIkit

## 1.2.0
### Fixed
- Add safelist for each UIkit component avoiding CssProcessor not compiling classes needed but not found on phtml/html/xml files

## 1.1.0
### Fixed
- Fix error on CssProcessor removing not useful dependency and fixing css purge method

## 1.0.0
### Added
- First Commit
