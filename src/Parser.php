<?php namespace UACapabilities;

class Result {

    public $capabilities = array();

    public function addCapabilities(array $capabilities)
    {
        $this->capabilities = array_replace_recursive($this->capabilities, $capabilities);
    }

    public function addOverwrites(array $overwrites)
    {
        $this->capabilities = array_replace_recursive($this->capabilities, $overwrites);
    }

    public function merge(Result $result)
    {
        $this->addCapabilities($result->capabilities);
    }
}

class Parser {

    const MAX_RECURSION = 5;

    public $path;

    private $tree;

    private $recursionDepth = 0;

    private static $types = [
        ['ua', ['family', 'major', 'minor']],
        ['os', ['family', 'major', 'minor']],
        ['device', ['family']],
        ['device', ['brand', 'model']],
    ];

    public function __construct(array $tree)
    {
        $this->tree = $tree;
    }

    public function parse(InputData $input)
    {
        $this->recursionDepth = 0;

        $this->path = array();

        $result = $this->parseInternal($input);

        return $result->capabilities;
    }

    private function parseInternal(InputData $input)
    {
        $result = new Result();

        if(++$this->recursionDepth >= self::MAX_RECURSION){
            return $result;
        }

        $inputs = [
            'ua' => $input->userAgent,
            'os' => $input->os,
            'device' => $input->device,
        ];

        isset($this->tree['default']) and $result->addCapabilities($this->tree['default']['capabilities']);

        foreach(static::$types as $typesModes)
        {
            list($type, $modes) = $typesModes;

            if(!$inputs[$type] || !isset($this->tree[$type]))
            {
                continue;
            }

            $item = $inputs[$type];

            $tree = $this->tree[$type];

            foreach($modes as $mode) {

                if (!isset($item[$mode]) || !isset($tree[$mode])) {
                    break;
                }

                $uaData = strtolower($item[$mode]);

                $this->path[] = "Checking $type > $mode";

                $tree = $tree[$mode];

                $result->merge($this->checkAll($tree, $input, $uaData . $input->uaString));

                if(!isset($tree[$uaData])) {
                    break;
                }

                $this->path[] = "Checking $type > $mode > {$uaData}";

                $tree = $tree[$uaData];

                $result->merge($this->checkAll($tree, $input, $uaData . $input->uaString));
            }
        }

        return $result;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @param $regexItem
     * @return Result
     */
    private function checkAll(array $tree, InputData $input, $regexItem)
    {
        $result = new Result();

        $result->merge($this->checkCapabilities($tree, $input));
        $result->merge($this->checkRegexes($tree, $regexItem));
        $result->merge($this->checkOverwrites($tree, $input));
        $result->merge($this->checkExtends($tree, $input));

        return $result;
    }

    /**
     * @param array $tree
     * @return Result
     */
    private function checkCapabilities(array $tree)
    {
        $result = new Result();

        if(isset($tree['capabilities']))
        {
            $this->path[] = "Found capabilities " . json_encode($tree['capabilities']);

            $result->addCapabilities($tree['capabilities']);
        }

        return $result;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @return Result
     */
    private function checkOverwrites(array $tree, InputData $input)
    {
        $result = new Result();

        if(isset($tree['overwrites']))
        {
            $this->path[] = "Found overwrites " . json_encode($tree['overwrites']);

            foreach($tree['overwrites'] as $overwrite)
            {
                $overwriter = new static($overwrite);

                $result->addOverwrites($overwriter->parse($input));
            }
        }

        return $result;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @return Result
     */
    private function checkExtends(array $tree, InputData $input)
    {
        $result = new Result();

        if(isset($tree['extends']))
        {
            $this->path[] = "Found extends " . json_encode($tree['extends']);

            foreach($tree['extends'] as $extend)
            {
                $extendInput = new InputData();

                $extendInput->uaString = $input->uaString;
                isset($extend['device']) and $extendInput->device = $extend['device'];
                isset($extend['os']) and $extendInput->os = $extend['os'];
                isset($extend['ua']) and $extendInput->userAgent = $extend['ua'];

                $result->merge($this->parseInternal($extendInput));
            }
        }

        return $result;
    }

    /**
     * @param array $tree
     * @param $searchString
     * @return Result
     */
    private function checkRegexes(array $tree, $searchString)
    {
        $result = new Result();

        if(isset($tree['regexes']))
        {
            $this->path[] = "Found regexes  " . json_encode($tree['regexes']);

            foreach($tree['regexes'] as $regex)
            {
                if(
                    (isset($regex['regex']) && preg_match("@{$regex['regex']}@i", $searchString))
                    ||
                    (isset($regex['regex_not']) && !preg_match("@{$regex['regex_not']}@i", $searchString))
                ){
                    $this->path[] = "Matched regex " . json_encode($regex);

                    $result->addCapabilities($regex['capabilities']);
                    break;
                }
            }
        }

        return $result;
    }
}