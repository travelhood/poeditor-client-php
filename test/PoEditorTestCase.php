<?php

class PoEditorTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var \Travelhood\PoEditor\Client */
    static protected $client;

    static function setUpBeforeClass()
    {
        self::$client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));;
    }

    /**
     * @return array
     * @throws \Travelhood\PoEditor\Exception
     */
    public function provideProjectIds()
    {
        $client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));
        $list = [];
        foreach($client->listProjects() as $project) {
            $list[] = [$project->id];
        }
        return $list;
    }
}