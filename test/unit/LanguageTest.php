<?php

class LanguageTest extends PoEditorTestCase
{
    public function testAvailable()
    {
        $languages = self::$client->availableLanguages();
        $this->assertGreaterThan(0, count($languages));
        foreach($languages as $language) {
            $this->assertInstanceOf(\Travelhood\PoEditor\Language::class, $language);
        }
    }


    /**
     * @dataProvider provideProjectIds
     * @param $projectId
     * @throws \Travelhood\PoEditor\Exception
     */
    public function testList($projectId)
    {
        $languages = self::$client->listLanguages($projectId);
        $this->assertTrue(is_array($languages));
        foreach($languages as $language) {
            $this->assertInstanceOf(\Travelhood\PoEditor\Language::class, $language);
        }
    }

    public function testAddUpdateDelete()
    {
        $languageCode = 'en';
        $project = self::$client->addProject('test');

        $this->assertTrue(self::$client->addLanguage($project->id, $languageCode));

//      @todo permission error
//        $response = self::$client->updateLanguage($project->id, $languageCode, [['term'=>'label','translation'=>['content'=>'Label','fuzzy'=>0]]]);
//        $this->assertGreaterThan(0, $response['parsed']);

        $this->assertTrue(self::$client->deleteLanguage($project->id, $languageCode));

        $this->assertTrue(self::$client->deleteProject($project->id));
    }
}