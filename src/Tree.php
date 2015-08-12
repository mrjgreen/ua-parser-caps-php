<?php namespace UACapabilities;

use Symfony\Component\Yaml\Yaml;


class Tree {

    private $yamlParser;

    private $data = array();

    public function __construct()
    {
        $this->yamlParser = new Yaml();
    }

    public function load($file)
    {
        $data = $this->transform($this->yamlParser->parse($file));

        $this->data = array_replace_recursive($this->data, $data);
    }

    private function transform(array $data)
    {
        $array = new \RecursiveArrayIterator($data);
        $iterator = new \RecursiveIteratorIterator($array, \RecursiveIteratorIterator::SELF_FIRST);

        $new = array();

        foreach($iterator as $key => $item)
        {
            if($key === 'regexes')
            {
                $newItem = array();

                foreach($item as $regex)
                {
                    $newItem[json_encode(array_intersect_key($regex, ['regex' => 1, 'not_regex' => 1]))] = $regex;
                }

                $item = $newItem;
            }

            $new[$key] = $item;
        }

        die();

        return $iterator->getArrayCopy();
    }

    public function get()
    {
        return $this->data;
    }
}