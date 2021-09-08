<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SalesforceApi
{
    public $http;
    public string $module;
    public string $token;

    public function __construct()
    {
        if(! $token = Cache::get('salesforce_token')) {
            try {
                $url = config('salesforce.base_api_url').'login/';
                $token = json_decode($http = Http::asMultipart()->retry(5, 2000)->send('post', $url, ['form_params'=>[
                    'email' => config('salesforce.api_auth_email'),
                    'password' => config('salesforce.api_auth_password'),
                ]])->body())->token;
                Cache::put('salesforce_token', $token, config('salesforce.token_ttl'));
            } catch (\Throwable $th) {
                throw new Exception('The salesforce user and pass is incorrect or there is some other issue authenticating');
            }
        }

        $this->http = Http::withHeaders([
            'authorization' => $token,
        ])->withOptions([
            'base_uri' => config('salesforce.base_api_url'),
        ])->retry(5, 2000);
    }

    /**
     * Sets access to contacts module
     *
     * @return self
     */
    public function contacts() : self
    {
        $this->module = 'contacts';

        return $this;
    }

    /**
     * Fetch all from a base module url
     *
     * @return \Illuminate\Support\Collection
     */
    public function all() : Collection
    {
       return collect(
           json_decode($this->http->get($this->module.'/')->body())->records
       );

    }

    /**
     * Get a record by id on a base module url
     *
     * @param string $id
     * @return object
     */
    public function getById(string $id) : object
    {
       return json_decode($this->http->get($this->module.'/'.$id)->body());
    }

    public function create(array $data) : object
    {
        return json_decode($this->http->asMultipart()->post($this->module.'/', $data)->body());
    }
}

