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

    const MAX_RECURSION = 10;

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

            if (!$inputs[$type]) {
                $this->path[] = "Skipping $type - no '$type' item in ua data";
                continue;
            }

            if(!isset($this->tree[$type])) {
                $this->path[] = "Skipping $type - no '$type' item in tree data";
                continue;
            }

            $item = $inputs[$type];

            $tree = $this->tree[$type];

            foreach($modes as $mode) {


                if (!isset($item[$mode])) {
                    $this->path[] = "Skipping $type > $mode - no '$mode' item in ua data";
                    break;
                }

                if(!isset($tree[$mode])) {
                    $this->path[] = "Skipping $type > $mode - no '$mode' item in tree data";
                    break;
                }

                $uaData = strtolower($item[$mode]);

                $this->path[] = "Checking $type > $mode";

                $tree = $tree[$mode];

                $result->merge($this->checkAll($tree, $input, [$uaData, $input->uaString]));

                if(!isset($tree[$uaData]) && !isset($tree[$uaData = $this->normalizeName($uaData)])) {
                    break;
                }

                $this->path[] = "Checking $type > $mode > {$uaData}";

                $tree = $tree[$uaData];

                $result->merge($this->checkAll($tree, $input, [$uaData, $input->uaString]));
            }
        }

        return $result;
    }

    private function normalizeName($name)
    {
        return str_replace('_', " ", $name);
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @param array $regexItems
     * @return Result
     */
    private function checkAll(array $tree, InputData $input, array $regexItems)
    {
        $result = new Result();

        $result->merge($this->checkCapabilities($tree));
        $result->merge($this->checkRegexes($tree, $regexItems));
        $result->merge($this->checkExtends($tree, $input));
        $result->merge($this->checkOverwrites($tree, $input));

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
            $this->path[] = ["Found capabilities: ", $tree['capabilities']];

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
            foreach($tree['overwrites'] as $overwrite)
            {
                $this->path[] = ["Checking overwrite: ", $overwrite, "against input: " , $input];

                $overwriter = new static($overwrite);

                $result->addOverwrites($overwriter->parse($input));

                $this->path[] = $overwriter->path;
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
            $this->path[] = ["Found extends: ", $tree['extends']];

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
     * @param array $searchStrings
     * @return Result
     */
    private function checkRegexes(array $tree, array $searchStrings)
    {
        $result = new Result();

        if(isset($tree['regexes']))
        {
            $this->path[] = ["Found regexes: ", $tree['regexes']];

            foreach($tree['regexes'] as $regex)
            {
                if(
                    (isset($regex['regex']) && $this->anyMatch($regex['regex'], $searchStrings))
                    ||
                    (isset($regex['regex_not']) && $this->noneMatch($regex['regex_not'], $searchStrings))
                ){
                    $this->path[] = ["Matched regex: ", $regex];

                    $result->addCapabilities($regex['capabilities']);
                    break;
                }
            }
        }

        return $result;
    }

    private function anyMatch($regex, array $strings)
    {
        foreach($strings as $string)
        {
            if(preg_match("@{$regex}@i", $string)) return true;
        }

        return false;
    }

    private function noneMatch($regex, array $strings)
    {
        foreach($strings as $string)
        {
            if(preg_match("@{$regex}@i", $string)) return false;
        }

        return true;
    }
}