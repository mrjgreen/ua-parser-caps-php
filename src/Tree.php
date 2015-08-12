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
        $data = $this->yamlParser->parse($file);

        $data = $this->transformRegexes($data);

        $data = $this->changeKeyCaseRecursive($data);

        $this->data = array_replace_recursive($this->data, $data);
    }

    private function changeKeyCaseRecursive(array $arr)
    {
        return array_map(function($item){
            if(is_array($item))
                $item = $this->changeKeyCaseRecursive($item);
            return $item;
        }, array_change_key_case($arr, CASE_LOWER));
    }

    private function transformRegexes(array $data)
    {
        foreach($this->findNestedByKey($data, 'regexes') as $keys)
        {
            $ref = &$data;

            foreach($keys as $key) {
                $ref = &$ref[$key];
            }

            $newItem = array();

            foreach($ref as $regex)
            {
                $newItem[json_encode(array_intersect_key($regex, ['regex' => 1, 'regex_not' => 1]))] = $regex;
            }

            $ref = $newItem;
        }

        return $data;
    }

    private function findNestedByKey(array $items, $search)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($items), \RecursiveIteratorIterator::SELF_FIRST);

        $foundKeys = array();

        foreach($iterator as $key => $item)
        {
            if($key === $search)
            {
                $keys = array();

                for ($i = 0; $i < $iterator->getDepth(); $i++) {
                    $keys[] = $iterator->getSubIterator($i)->key();
                }

                $keys[] = $key;

                $foundKeys[] = $keys;
            }
        }

        return $foundKeys;
    }

    public function get()
    {
        return $this->data;
    }
}