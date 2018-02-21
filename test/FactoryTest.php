<?php

include_once __dir__ . "/../vendor/autoload.php";
include_once __dir__ . "/../src/Maalls/DomDocument/Factory.php";

use Maalls\DomDocument\Factory;

class FactoryTest extends \PHPUnit\Framework\TestCase  {


    public function setup()
    {

   

    }        

    public function testCreateFromUrl()
    {
        $factory = new Factory();
        
        $dom = $factory->createFromUrl("http://maalls.net");

        $domXPath = new \DomXPath($dom);

        $nodes = $domXPath->query("//h1");

        $this->assertEquals(1, $nodes->length);


        foreach($nodes as $node) {

            $this->assertEquals("h1", $node->nodeName);
            $this->assertEquals("MAALLS", $node->nodeValue);

        }

        $dom = $factory->createFromUrl("http://toto.com");

        $this->assertNotEquals(false, $dom);


    }


}
