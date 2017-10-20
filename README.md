This module is intended to log query and result events passing in and out of Search API.

Configuration:
- create server
- in server settings, you can select backend and connector
- in connector's advanced settings, you find 2 new options:
    - log search requests
    - log search results (solr only)
- these options can be set up differently per connector


TODO's and limitations:
- currently, only drupal watchdog and devel is supported. Generic support for other channels should be added
- tests
