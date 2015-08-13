<?php namespace UACapabilities\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use UACapabilities\FileLoader;
use UACapabilities\FileLoadException;
use UACapabilities\Translator;

class UpdateCommand extends Command
{
    /**
     * @var string
     */
    private $translator;

    /**
     * @var FileLoader
     */
    private $fileLoader;

    /**
     * @var string
     */
    private $defaultTarget;

    /**
     * @var string[]
     */
    private $defaultSource = array(
        'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/caps_device_type.yaml',
        'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/caps_user_view.yaml',
        'https://raw.githubusercontent.com/commenthol/ua-parser-caps/master/caps_ie_compatibility.yaml',
    );

    public function __construct()
    {
        $this->translator = new Translator();

        $this->fileLoader = new FileLoader();

        $this->defaultTarget = __DIR__ . '/../../resources/capabilities.php';

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('update')
            ->setDescription('Fetches an updated YAML file for ua-parser-caps and overwrites the current PHP file.')
            ->addOption('source','s', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'The download sources for the capabilites files', $this->defaultSource)
            ->addOption('output','o', InputOption::VALUE_REQUIRED, 'The target file for the built tree', $this->defaultTarget)
            ->addOption('patch','p', InputOption::VALUE_REQUIRED, 'Include any yaml files in the given directory')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = array();

        foreach($input->getOption('source') as $source)
        {
            if(!$parsed = $this->loadRegex(new \SplFileInfo($source), $output))
            {
                return 2;
            }

            $data = array_replace_recursive($data, $parsed);

            $output->writeln("Loaded source file $source");
        }


        // See if we have an additional file or directory to look through
        if($patchFile = $input->getOption('patch'))
        {
            if(!$parsed = $this->loadRegex(new \SplFileInfo($patchFile), $output))
            {
                return 2;
            }

            $data = array_replace_recursive($data, $parsed);

            $output->writeln("Loaded patch file $patchFile");
        }

        $outputTarget = $input->getOption('output');

        $this->write($outputTarget, $data);

        $output->writeln('<info>Success: ' . $outputTarget . ' has been updated</info>');

        return 0;
    }

    /**
     * @param $out
     * @param $data
     * @return bool
     */
    private function write($out,$data)
    {
        is_dir(dirname($out)) or mkdir(dirname($out), 0777, true);

        file_put_contents($out,'<?php return ' . var_export($data, 1). ';');

        return true;
    }

    /**
     * @param \SplFileInfo $patchFile
     * @param OutputInterface $output
     * @return array
     * @throws \Exception
     */
    private function loadRegex(\SplFileInfo $patchFile, OutputInterface $output)
    {
        $arr = array();

        try
        {
            $files = $this->fileLoader->load($patchFile);
        }
        catch(FileLoadException $e)
        {
            $output->writeln('<error>Regex patch load failed with message '.$e->getMessage().'</error>');

            return false;
        }

        foreach($files as $filename => $content)
        {
            $output->writeln("Loaded file: $filename");

            $arr = array_merge_recursive($arr, $this->translator->translate($content));
        }

        if(!$arr)
        {
            $output->writeln("<error>No regexes were found</error>");

            return false;
        }

        return $arr;
    }
}
