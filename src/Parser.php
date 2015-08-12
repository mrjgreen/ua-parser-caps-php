<?php namespace UACapabilities;

class Parser {

    private $tree;

    public $path;

    public function __construct(array $tree)
    {
        $this->tree = $tree;
    }

    public function parse(InputData $input)
    {
        $inputs = array(
            'ua' => $input->userAgent,
            'os' => $input->os,
            'device' => $input->device,
        );


        $caps = isset($this->tree['default']) ? $this->tree['default']['capabilities'] : array();

        $types = [
            ['ua', ['family', 'major', 'minor']],
            ['os', ['family', 'major', 'minor']],
            ['device', ['family']],
            ['device', ['brand', 'model']],
        ];

        $this->path = array();

        foreach($types as $typesModes)
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

                $this->path[] = "Checking $type > $mode";

                $tree = $tree[$mode];

                $caps = $this->checkAll($caps, $tree, $input);

                if(!isset($tree[$item[$mode]])) {
                    break;
                }

                $this->path[] = "Checking $type > $mode > {$item[$mode]}";

                $tree = $tree[$item[$mode]];

                $caps = $this->checkAll($caps, $tree, $input);
            }
        }

        return $caps;
    }

    /**
     * @param array $caps
     * @param array $tree
     * @param InputData $input
     * @return array
     */
    private function checkAll(array $caps, array $tree, InputData $input)
    {
        return array_replace_recursive(
            $caps,
            $this->checkCapabilities($tree),
            $this->checkRegexes($tree, $input),
            $this->checkExtends($tree, $input),
            $this->checkOverwrites($tree, $input)
        );
    }

    /**
     * @param array $tree
     * @return array
     */
    private function checkCapabilities(array $tree)
    {
        $caps = array();

        if(isset($tree['capabilities']))
        {
            $this->path[] = "Found capabilities " . json_encode($tree['capabilities']);

            $caps = array_replace_recursive($caps, $tree['capabilities']);
        }

        return $caps;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @return array
     */
    private function checkOverwrites(array $tree, InputData $input)
    {
        $caps = array();

        if(isset($tree['overwrites']))
        {
            $this->path[] = "Found overwrites " . json_encode($tree['overwrites']);

            foreach($tree['overwrites'] as $overwrite)
            {
                $overwriter = new static($overwrite);

                $caps = array_replace_recursive($caps, $overwriter->parse($input));
            }
        }

        return $caps;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @return array
     */
    private function checkExtends(array $tree, InputData $input)
    {
        $caps = array();

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


                $caps = array_replace_recursive($caps, $this->parse($extendInput));
            }
        }

        return $caps;
    }

    /**
     * @param array $tree
     * @param InputData $input
     * @return array
     */
    private function checkRegexes(array $tree, InputData $input)
    {
        $caps = array();

        if(isset($tree['regexes']))
        {
            $this->path[] = "Found regexes  " . json_encode($tree['regexes']);

            foreach($tree['regexes'] as $regex)
            {
                if(
                    (isset($regex['regex']) && preg_match("/{$regex['regex']}/", $input->uaString))
                    ||
                    (isset($regex['regex_not']) && !preg_match("/{$regex['regex_not']}/", $input->uaString))
                ){
                    $this->path[] = "Matched regex " . json_encode($regex);

                    $caps = array_replace_recursive($caps, $regex['capabilities']);
                    break;
                }
            }
        }

        return $caps;
    }
}