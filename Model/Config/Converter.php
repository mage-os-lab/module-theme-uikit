<?php
namespace MageOS\UIkitTheme\Model\Config;

class Converter implements \Magento\Framework\Config\ConverterInterface
{

    /**
     * @param $source
     * @return array
     */
    public function convert($source)
    {
        $result = [];
        $xpath = new \DOMXPath($source);
        // Parse paths
        $result['paths'] = [];
        foreach ($xpath->query('/config/paths/path') as $pathNode) {
            $name = $pathNode->attributes->getNamedItem('name')->nodeValue;
            $value = trim($pathNode->nodeValue);
            $result['paths'][$name] = $value;
        }
        // Parse terms
        $result['terms'] = [];
        foreach ($xpath->query('/config/terms/term') as $termNode) {
            $name = $termNode->attributes->getNamedItem('name')->nodeValue;
            $value = trim($termNode->nodeValue);
            $result['terms'][$name] = $value;
        }
        return $result;
    }
}
