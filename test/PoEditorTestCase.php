<?php

class PoEditorTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var \Travelhood\PoEditor\Client */
    static protected $client;

    static function setUpBeforeClass()
    {
        self::$client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));
    }
}