## Cegeka Elastic search API

# About

This module provides a way to easily created faceted search pages. Depends on search_api and elasticsearch_connector to index content.

Features:
- ajax powered faceted search
- synonyms
- autocompletion
- search suggestions

# Installation

Add both this module and the block ui library to your project's composer.json, which is a dependency of this module:
```
"repositories": [
    {
        "type": "git",
        "url": "https://github.com/cegeka/cgk_elastic_api.git"
    },
    {
      "type": "package",
      "package": {
        "name": "blockui",
        "version": "v2.70",
        "type": "drupal-library",
        "dist": {
          "url": "https://github.com/malsup/blockui/archive/2.70.zip",
          "type": "zip"
        }
      }
    }
  ]
```
And install it as usual: `composer require drupal/cgk_elastic_api`
