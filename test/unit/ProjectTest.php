<?php

class ProjectTest extends PoEditorTestCase
{
    /**
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testList()
    {
        $projects = self::$client->listProjects();
        $this->assertGreaterThan(0, count($projects));
        foreach($projects as $project) {
            $this->assertInstanceOf(\Travelhood\PoEditor\Project::class, $project);
        }
    }

    /**
     * @dataProvider provideProjectIds
     * @param $projectId
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testView($projectId)
    {
        $project = self::$client->viewProject($projectId);
        $this->assertInstanceOf(\Travelhood\PoEditor\Project::class, $project);
    }

    public function testAddUpdateDelete()
    {
        $project = self::$client->addProject('test', 'dummy description');
        $this->assertGreaterThan(0, $project->id);

        self::$client->updateProject($project->id, ['description'=>'updated description']);
        $this->assertEquals('updated description', self::$client->viewProject($project->id)->description);

        $this->assertTrue(self::$client->deleteProject($project->id));
    }

    /**
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testUploadTerms()
    {
        $project = self::$client->addProject('test', 'testing file upload');
        $this->assertGreaterThan(0, $project->id);
        $this->assertEquals('test', $project->name);
        $response = self::$client->uploadProject(
            $project->id,
            \Travelhood\PoEditor\Client::UPDATE_TERMS,
            realpath(__DIR__ . '/../fixture/sample.pot'),
            null,
            false,
            true
        );
        $this->assertGreaterThan(0, $response['terms']['parsed']);
        $this->assertTrue(self::$client->deleteProject($project->id));
    }

    /**
     * @dataProvider provideProjectIds
     * @param int $projectId
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testExport($projectId)
    {
        $languages = self::$client->listLanguages($projectId);
        if(count($languages) < 1) {
            $this->assertTrue(is_array($languages));
        }
        else {
            $url = self::$client->exportProject($projectId, $languages[0]->code, \Travelhood\PoEditor\Client::TYPE_PO);
            $this->assertRegExp('/download\/file\//', $url);
        }
    }
}