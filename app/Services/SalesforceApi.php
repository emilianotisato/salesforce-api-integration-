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

    public function all() : Collection
    {
       return collect(
           json_decode($this->http->get($this->module.'/')->body())->records
       );

    }
}

