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
        $factory = new Factory(null, new LogTest());
        
        $dom = $factory->createFromUrl("http://maalls.net");

        $domXPath = new \DomXPath($dom);

        $nodes = $domXPath->query("//h1");

        $this->assertEquals(1, $nodes->length);


        foreach($nodes as $node) {

            $this->assertEquals("h1", $node->nodeName);
            $this->assertEquals("TODO: make a website ;)", $node->nodeValue);

        }


    }


}

class LogTest {

    public function log($msg, $level = "info")
    {

        var_dump($level . " : " . $msg);

    }

}