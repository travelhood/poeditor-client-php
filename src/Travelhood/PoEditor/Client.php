<?php

namespace Travelhood\PoEditor;

use InvalidArgumentException;

class Client
{
    const URL_SERVICE = 'https://api.poeditor.com/v2';

    protected $_apiToken;
    protected $_curl;

    /**
     * @param string $endpoint
     * @param array $data (optional)
     * @return array
     * @throws Exception
     */
    protected function _call($endpoint, $data=null)
    {
        $endpoint = trim($endpoint);
        if(substr($endpoint,0,1) !== '/') {
            $endpoint = '/'.$endpoint;
        }
        $url = self::URL_SERVICE.$endpoint;
        $data['api_token'] = $this->_apiToken;
        curl_setopt($this->_curl, CURLOPT_URL, $url);
        curl_setopt($this->_curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $return = curl_exec($this->_curl);
        $response = json_decode($return, true);
        if($response['response']['status'] != 'success') {
            throw new Exception($response['response']['message'], $response['response']['code']);
        }
        return $response['result'];
    }

    /**
     * @param string $apiToken
     * @throws Exception
     */
    public function __construct($apiToken)
    {
        if(!is_string($apiToken) || strlen($apiToken) < 1) {
            throw new Exception('Invalid API token provided');
        }
        $this->_apiToken = $apiToken;
        $this->_curl = curl_init();
        curl_setopt($this->_curl, CURLOPT_HEADER, false);
        curl_setopt($this->_curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->_curl, CURLOPT_POST, 1);
        curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, true);
    }

    public function __destruct()
    {
        if(is_resource($this->_curl)) {
            curl_close($this->_curl);
        }
    }

    /**
     * Returns the list of projects owned by user.
     * @return Project[]
     * @throws Exception
     */
    public function listProjects()
    {
        $return = [];
        foreach($this->_call('projects/list')['projects'] as $projectArray) {
            $return[] = new Project($projectArray);
        }
        return $return;
    }

    /**
     * Returns project's details.
     * @param int $projectId
     * @return Project
     * @throws Exception
     */
    public function viewProject($projectId)
    {
        return new Project($this->_call('projects/view', ['id'=>$projectId])['project']);
    }

    /**
     * Creates a new project.
     * Returns project details (if successful).
     * @param string $name
     * @param string $description (optional)
     * @return Project
     * @throws Exception
     */
    public function addProject($name, $description="")
    {
        return new Project($this->_call('projects/add', [
            'name' => $name,
            'description' => $description,
        ])['project']);
    }

    /**
     * Updates project settings (name, description, reference language)
     * If optional parameters are not sent, their respective fields are not updated.
     * @param int $projectId
     * @param array $data
     * @return Project
     * @throws Exception
     */
    public function updateProject($projectId, array $data)
    {
        $data['id'] = $projectId;
        return new Project($this->_call('projects/update', $data)['project']);
    }

    /**
     * Deletes the project from the account.
     * You must be the owner of the project.
     * @param int $projectId
     * @return bool
     * @throws Exception
     */
    public function deleteProject($projectId)
    {
        $this->_call('projects/delete', ['id'=>$projectId]);
        return true;
    }

    /**
     * Updates terms / translations - No more than one request every 30 seconds.
     * @param int $projectId
     * @param string $updating
     * @param string $file
     * @param string $language
     * @param bool $overwrite
     * @param bool $sync_terms
     * @param array $tags
     * @param bool $read_from_source
     * @param bool $fuzzy_trigger
     * @return array
     * @throws Exception
     */
    protected function uploadProject($projectId, $updating, $file, $language, $overwrite, $sync_terms, array $tags, $read_from_source, $fuzzy_trigger)
    {
        switch($updating) {
            case 'terms':
                break;
            case 'terms_translations':
            case 'translations':
                if(!$language) {
                    throw new Exception('Missing parameter: language');
                }
                break;
            default:
                throw new Exception('Invalid value for parameter: updating');
        }
        $data = [
            'id' => $projectId,
            'updating' => $updating,
            'file' => curl_file_create($file),
        ];
        if($language) {
            $data['language'] = $language;
        }
        if($overwrite) {
            $data['overwrite'] = $overwrite ? 1 : 0;
        }
        if($sync_terms) {
            $data['sync_terms'] = $sync_terms ? 1 : 0;
        }
        if($read_from_source) {
            $data['read_from_source'] = $read_from_source ? 1 : 0;
        }
        if($fuzzy_trigger) {
            $data['fuzzy_trigger'] = $fuzzy_trigger ? 1 : 0;
        }
        if($tags) {
            $data['tags'] = json_encode($tags);
        }
        return $this->_call('projects/upload', $data);
    }

    /**
     * Syncs your project with the array you send (terms that are not found in the JSON object will be deleted from project and the new ones added).
     * Please use with caution. If wrong data is sent, existing terms and their translations might be irreversibly lost.
     * @param int $projectId
     * @param array $terms
     * @return array
     * @throws Exception
     */
    public function syncProject($projectId, array $terms)
    {
        return $this->_call('projects/sync', [
            'id' => $projectId,
            'data' => json_encode($terms),
        ]);
    }

    /**
     * Returns the link of the file (expires after 10 minutes).
     * @param int $projectId
     * @param string $language
     * @param string $type
     * @param array $filters
     * @param array $tags
     * @return string
     * @throws Exception
     */
    public function exportProject($projectId, $language, $type, $filters=[], $tags=[])
    {
        $data = [
            'id' => $projectId,
            'language' => $language,
            'type' => $type,
        ];
        if(count($filters)>0) {
            $data['filters'] = json_encode($filters);
        }
        if(count($tags)>0) {
            $data['tags'] = json_encode($tags);
        }
        return $this->_call('projects/export', $data)['url'];
    }

    /**
     * Returns a comprehensive list of all languages supported by POEditor.
     * @see https://poeditor.com/docs/languages
     * @return Language[]
     * @throws Exception
     */
    public function availableLanguages()
    {
        return $this->_call('languages/available')['languages'];
    }

    /**
     * Returns project languages, percentage of translation done for each and the datetime (UTC - ISO 8601) when the last change was made.
     * @param int $projectId
     * @return Language[]
     * @throws Exception
     */
    public function listLanguages($projectId)
    {
        return $this->_call('languages/list', [
            'id'=>$projectId,
        ])['languages'];
    }

    /**
     * Adds a new language to project.
     * @param int $projectId
     * @param string $languageCode
     * @return bool
     * @throws Exception
     */
    public function addLanguage($projectId, $languageCode)
    {
        $this->_call('languages/add', [
            'id' => $projectId,
            'language' => $languageCode,
        ]);
        return true;
    }

    /**
     * Inserts / overwrites translations.
     * @param int $projectId
     * @param string $languageCode
     * @param array $data
     * @param bool $fuzzy_trigger (optional)
     * @return array
     * @throws Exception
     */
    public function updateLanguage($projectId, $languageCode, array $data, $fuzzy_trigger=null)
    {
        $data = [
            'id' => $projectId,
            'language' => $languageCode,
            'data' => json_encode($data),
        ];
        if($fuzzy_trigger !== null) {
            $data['fuzzy_trigger'] = $fuzzy_trigger;
        }
        return $this->_call('languages/update')['translations'];
    }

    /**
     * Deletes existing language from project.
     * @param $projectId
     * @param $languageCode
     * @return bool
     * @throws Exception
     */
    public function deleteLanguage($projectId, $languageCode)
    {
        $this->_call('languages/delete', [
            'id' => $projectId,
            'language' => $languageCode,
        ]);
        return true;
    }

    /**
     * Returns project's terms and translations if the argument language is provided.
     * @param int $projectId
     * @param string $languageCode (optional)
     * @return Term[]
     * @throws Exception
     */
    public function listTerms($projectId, $languageCode=null)
    {
        $data = [
            'id' => $projectId,
        ];
        if($languageCode !== null) {
            $data['language'] = $languageCode;
        }
        $return = [];
        foreach($this->_call('terms/list', $data)['terms'] as $termArray) {
            $return[] = new Term($termArray);
        }
        return $return;
    }

    /**
     * Adds terms to project.
     * @param int $projectId
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function addTerm($projectId, array $data)
    {
        return $this->_call('terms/add', [
            'id' => $projectId,
            'data' => json_encode($data),
        ])['terms'];
    }

    /**
     * Updates project terms. Lets you change the text, context, reference, plural and tags.
     * @param int $projectId
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function updateTerms($projectId, array $data)
    {
        return $this->_call('terms/update', [
            'id' => $projectId,
            'data' => json_encode($data),
        ])['terms'];
    }

    /**
     * Deletes terms from project.
     * @param int $projectId
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function deleteTerms($projectId, array $data)
    {
        return $this->_call('terms/delete', [
            'id' => $projectId,
            'data' => json_encode($data),
        ])['terms'];
    }

    /**
     * Adds comments to existing terms.
     * @param int $projectId
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function addComment($projectId, array $data)
    {
        return $this->_call('terms/add_comment', [
            'id' => $projectId,
            'data' => json_encode($data),
        ])['terms'];
    }

    /**
     * Returns the list of contributors.
     * @param int $projectId (optional)
     * @param string $languageCode (optional)
     * @return array
     * @throws Exception
     */
    public function listContributors($projectId=null, $languageCode=null)
    {
        if($languageCode && !$projectId) {
            throw new InvalidArgumentException('$projectId is required');
        }
        $data = [];
        if($projectId) {
            $data['id'] = $projectId;
        }
        if($languageCode) {
            $data['language'] = $languageCode;
        }
        return $this->_call('contributors/list', $data)['contributors'];
    }

    /**
     * Adds a contributor to a project language or an administrator to a project.
     * @param int $projectId
     * @param string $name
     * @param string $email
     * @param string $languageCode
     * @param bool $isAdmin
     * @return bool
     * @throws Exception
     */
    public function addContributor($projectId, $name, $email, $languageCode, $isAdmin=false)
    {
        $data = [
            'id' => $projectId,
            'name' => $name,
            'email' => $email,
        ];
        if($isAdmin) {
            $data['admin'] = 1;
        }
        else {
            $data['language'] = $languageCode;
        }
        $this->_call('contributors/add');
        return true;
    }

    /**
     * Removes a contributor from a project language or an admin from a project, if the language is not specified.
     * @param int $projectId
     * @param string $email
     * @param string $languageCode
     * @return bool
     * @throws Exception
     */
    public function removeContributor($projectId, $email, $languageCode=null)
    {
        $data = [
            'id' => $projectId,
            'email' => $email,
        ];
        if($languageCode) {
            $data['language'] = $languageCode;
        }
        $this->_call('contributors/remove', $data);
        return true;
    }

}