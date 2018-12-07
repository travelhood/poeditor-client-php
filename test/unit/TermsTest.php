<?php

class TermsTest extends PoEditorTestCase
{
    protected $_projectId;

    public function SetUp()
    {
        $project = self::$client->addProject('test', 'test terms');
        $this->_projectId = $project->id;
    }

    public function TearDown()
    {
        self::$client->deleteProject($this->_projectId);
    }

    public function testAddUpdateListCommentDelete()
    {
        $response = self::$client->addTerm($this->_projectId, [['term'=>'%{n} test term','plural'=>'%{n} test terms']]);
        $this->assertEquals(1, $response['added']);

        $response = self::$client->updateTerms($this->_projectId, [['term'=>'%{n} test term','plural'=>'%{n} test terms','new_term'=>'%{n} Test Term','new_plural'=>'%{n} Test Terms']]);
        $this->assertGreaterThan(0, $response['parsed']);

        $terms = self::$client->listTerms($this->_projectId);
        $this->assertTrue(is_array($terms));
        foreach($terms as $term) {
            $this->assertInstanceOf(\Travelhood\PoEditor\Term::class, $term);
        }

        $response = self::$client->addComment($this->_projectId, [['term'=>'%{n} Test Term','plural'=>'%{n} Test Terms','comment'=>'test comment']]);
        $this->assertGreaterThan(0, $response['parsed']);

        $response = self::$client->deleteTerms($this->_projectId, [['term'=>'%{n} Test Term','plural'=>'%{n} Test Terms']]);
        $this->assertEquals(1, $response['parsed']);
    }
}