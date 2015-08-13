# UA-Parser-Capabilities for PHP

This library reads the ua-parser-capabilities yaml file from https://github.com/commenthol/ua-parser-caps

You can then use the output from the ua-parser-php parser to find device capabilities


Clone the repo

~~~
composer install

composer caps-update #Fetch the source YAML files

composer caps-generate #Generate the PHP data file from the YAML files

composer caps-test #Run the tests
~~~

Roadmap

* [x] Automatic update command
* [ ] All tests passing (15/~62000 failing)
* [ ] Refactor into "YAML Translator" and "Parser"
* [ ] Symfony console commands
* [ ] Test interface with ua-parser PHP



###Current Changes required to get to 5 passing tests
~~~
Change "Bramnd => Brand" for Nokia devices - PR outstanding
Change regex `One Touch (6040X|8000D|8008D)` to `One Touch (8000D|8008D)`
Change regex `^(Galaxy Tab|GT-P|GT-N[58]|SM-[PT]|SHW-M)` to `^(Galaxy Tab|GT-P8|GT-N[58]|SM-[PT]|SHW-M)`
~~