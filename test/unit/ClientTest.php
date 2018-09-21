<?php

class ClientTest extends PoEditorTestCase
{
    public function testCreate()
    {
        $client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));
        $this->assertInstanceOf(\Travelhood\PoEditor\Client::class, $client);
    }

    public function testListProjects()
    {
        $projects = self::$client->listProjects();
        $this->assertGreaterThan(0, count($projects));
        foreach($projects as $project) {
            $this->assertInstanceOf(\Travelhood\PoEditor\Project::class, $project);
        }
    }

    public function provideProjectIds()
    {
        $client = new \Travelhood\PoEditor\Client(getenv('POEDITOR_TOKEN'));
        $list = [];
        foreach($client->listProjects() as $project) {
            $list[] = [$project->id];
        }
        return $list;
    }

    /**
     * @dataProvider provideProjectIds
     * @param $projectId
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testViewProject($projectId)
    {
        $project = self::$client->viewProject($projectId);
        $this->assertInstanceOf(\Travelhood\PoEditor\Project::class, $project);
    }
}