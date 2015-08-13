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
* [ ] All tests passing (14/~62000 failing)
* [ ] Test interface with ua-parser PHP



###Current Changes required to get to 5 failing tests
~~~
Change "Bramnd => Brand" for Nokia devices - PR outstanding -
Change regex `^(Galaxy Tab|GT-P|GT-N[58]|SM-[PT]|SHW-M)` to `^(Galaxy Tab|GT-P8|GT-N[58]|SM-[PT]|SHW-M)`
Change regex `One Touch (6040X|8000D|8008D)` to `One Touch (8000D|8008D)`
~~~