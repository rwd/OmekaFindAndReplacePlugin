# Find and Replace plugin for Omeka

This plugin provides an Omeka admin interface to find and replace the values of
element text fields.

It only replaces if the full text of an element is equal to what you search for.
It does not do find and replace *within* element text fields.

## Requirements

* Omeka 2.0+

## Installation

1. Download the plugin
2. Extract to the plugins directory of your Omeka installation
3. Install the plugin at `/admin/plugins`

## Usage

1. Access the admin interface at `/admin/find-and-replace`
2. Select the element to operate on
3. Enter your find and replace values
4. Click `Preview` to see how many records will be affected by the find and
  replace
5. **Warning:** Find and replace is irreversible! Be sure that you want to
  proceed before clicking that button. If in doubt, back up your database.
6. If you're sure, click `Find And Replace!`
