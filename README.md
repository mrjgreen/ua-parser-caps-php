# UA-Parser-Capabilities for PHP

This library reads the ua-parser-capabilities yaml file from https://github.com/commenthol/ua-parser-caps

You can then use the output from the ua-parser-php parser to find device capabilities


Clone the repo

~~~
composer install

bin/uacaps update #Fetch the source YAML files and generate local PHP tree cache file

bin/uacaps test #Run the tests
~~~

Roadmap

* [x] Automatic update command
* [x] Symfony console commands
* [x] Refactor into "YAML Translator" and "Parser"
* [x] Add travis tests
* [ ] All tests passing (5/~62000 failing)
* [ ] Test interface with ua-parser PHP