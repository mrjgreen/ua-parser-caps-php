<?php namespace UACapabilities\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UACapabilities\InputData;
use UACapabilities\Parser;

class TestCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var string[]
     */
    private $defaultSource = 'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/test/resources/test_capabilities.json';

    private $defaultLocalSource;

    protected function configure()
    {
        $defaultRegexData = __DIR__ . '/../../resources/capabilities.php';

        $this->defaultLocalSource = __DIR__ . '/../../resources/test_capabilities.json';

        $this
            ->setName('test')
            ->setDescription('Runs the tests against the parser.')
            //->addArgument('uastring', InputArgument::OPTIONAL, 'A user agent string to test')
            ->addOption('source', 's', InputOption::VALUE_REQUIRED, 'The regex source data', $defaultRegexData)
            ->addOption('test', 't', InputOption::VALUE_REQUIRED, 'The test file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;

        $regexes = include $input->getOption('source');

        $this->parser = new Parser($regexes);

        $test = $input->getOption('test');

        if(!$test)
        {
            $output->writeln('<error>No test files specified. Downloading tests from project. Specify a agent string as an argument or use --tests (-t) to specify test file locations</error>');

            if(!is_file($this->defaultLocalSource))
            {
                $output->writeln('<info>Test file does not exist locally. Downloading....</info>');

                if($data = file_get_contents($this->defaultSource))
                {
                    file_put_contents($this->defaultLocalSource, $data);
                }
            }

            $test = $this->defaultLocalSource;
        }

        return $this->runAllTests($test) ? 0 : 1;
    }

    /**
     * @param $testFile
     * @return bool
     */
    private function runAllTests($testFile)
    {
        // Read 1 object at a time
        $jsonFile = fopen($testFile, 'r');

        fgets($jsonFile); // Throw away line 1

        $total = intval(shell_exec('wc -l ' . $testFile));

        $stopOnFail = false;

        $i = 0;
        $failed = array();

        while($line = fgets($jsonFile))
        {
            $test = json_decode(rtrim(rtrim($line), ','), true);

            if(!$test) continue;

            $input = new InputData($test["string"], $test["ua"], $test["os"], $test["device"]);

            $capabilities = $this->parser->parse($input);

            $capabilities = array_intersect_key($capabilities, $test['capabilities']); // Only check keys we have in the expectation

            $i++;

            if (!$this->compare($test['capabilities'], $capabilities)) {
                $msg = "";
                $msg .= "<error>✘ $i / $total {$test["string"]}</error>";
                $msg .= "\n===================================\n";
                $msg .= "Test Data:\n";
                $msg .= $this->format(array_diff_key($test, ['capabilities' => 1]));
                $msg .= "\n===================================\n";
                $msg .= "Expected:\n";
                $msg .= $this->format($test['capabilities']);
                $msg .= "\n===================================\n";
                $msg .= "Actual:\n";
                $msg .= $this->format($capabilities);
                $msg .= "\n===================================\n";
                $msg .= "Path:\n";
                $msg .= $this->format($this->parser->path);
                $msg .= "\n===================================\n";
                $this->output->write($msg);

                if($stopOnFail) return false;
                else $failed[] = $msg;
            }else{
                $this->output->writeln("<info>✔ $i / $total {$test["string"]}</info>");
            }
        }

        $failCount = count($failed);

        $passed = $i - $failCount;

        $this->output->write(implode("\n\n", $failed));

        $this->output->writeln("<info>✔ Passed: $passed / $i</info>");
        $this->output->writeln("<error>✘ Failed: $failCount / $i</error>");
    }

    private function compare(array $expected, array $actual)
    {
        foreach($expected as $key => $item)
        {
            if(!isset($actual[$key]))
            {
                return false;
            }

            if(is_array($item) && is_array($actual[$key]))
            {
                return $this->compare($item, $actual[$key]);
            }

            if($item !== $actual[$key])
            {
                return false;
            }
        }

        return true;
    }

    private function format($data)
    {
        return trim(substr(json_encode($data, JSON_PRETTY_PRINT), 1, -1), "\n");
    }
}
