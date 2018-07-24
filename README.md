# Calista core

Provides a backend-agnostic set of tools for building user interface or dataset queries.

This package provide:

 * calista-query: provide a simple Query object that normalizes user input from
   Symfony's HTTP Foundation request's object data, based upon a configuration,
   suitable for datasource security filtering,

 * calista-datasource: simple datasource interface, suitable for searching or
   listing data, can be used to implement generic administration screens, CSV
   or XLS data export, autocomplete backend queries,...

 * calista-twig: plugs the calista-view package with Twig,

 * calista-view: dynamic arbitrary object properties definition introspection
   and definition, can be used to build dynamic and generic displays for
   administration screens, CSV or XLS data exports, etc...
