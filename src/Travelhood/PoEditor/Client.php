<?php

namespace Travelhood\PoEditor;

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
     * @param int $projectId
     * @return Project
     * @throws Exception
     */
    public function viewProject($projectId)
    {
        return new Project($this->_call('projects/view', ['id'=>$projectId])['project']);
    }

    /**
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
     * @return Language[]
     * @throws Exception
     */
    public function availableLanguages()
    {
        $return = [];
        foreach($this->_call('languages/available')['languages'] as $languageArray) {
            $return[] = new Language($languageArray);
        }
        return $return;
    }

    /**
     * @param int $projectId
     * @return Language[]
     * @throws Exception
     */
    public function listLanguages($projectId)
    {
        $return = [];
        foreach($this->_call('languages/list', ['id'=>$projectId])['languages'] as $languageArray) {
            $return[] = new Language($languageArray);
        }
        return $return;
    }

    /**
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

}