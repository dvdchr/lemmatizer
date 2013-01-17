lemmatizer
==========

Lemmatizer for indonesian language

This experiment is PHP-based, so XAMPP, MAMP, WAMP, or whatever will do.
configuration for MySQL:

    host: localhost
    user: root
    pass: <none>
    database: lemmatizer


installation
------------

* copy everything to your web server folder, e.g. `xampp/htdocs/lemmatizer/`
* import `database/db_lemmatizer.sql` to your MySQL database

..and you're good to go.

0.7 update notes
----------------

* Branched `lemmatizer` to 2 versions: release and debug
* Added pretty demo version
* Added `parse.php` and `test.php` for testing purposes


debug
-----

The debug version is basically the previous (0.6) version; `echo` functions
are called in several key point of the code.

debug version is now separated in another folder: `/debug/`. To use debug,
just open up `localhost/<your folder>/debug/`.


parse
-----

This script is used for parsing articles into a "lemmatizable" format and store
it in a table (`word`)

test
----

This script is for lemmatizer (release version) to test out the parsed datas;
results are stored in `result` table.

database
--------

The database consists of 3 tables: `dictionary`, `result`, and `word`.