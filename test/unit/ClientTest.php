<?php

class ClientTest extends PoEditorTestCase
{
    public function testCreate()
    {
        $client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));
        $this->assertInstanceOf(\Travelhood\PoEditor\Client::class, $client);
    }
}