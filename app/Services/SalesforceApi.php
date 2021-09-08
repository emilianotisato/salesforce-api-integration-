<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class SalesforceApi
{
    /**
     * Http client
     */
    public $http;

    /**
     * Salesforce module
     *
     * @var string
     */
    public string $module = '';

    /**
     * Salesforce token
     *
     * @var string
     */
    public string $token;

    public function __construct()
    {
        if (!$token = Cache::get('salesforce_token')) {
            try {
                $url = config('salesforce.base_api_url') . 'login/';
                $token = json_decode($http = Http::asMultipart()->retry(5, 2000)->send('post', $url, ['form_params' => [
                    'email' => config('salesforce.api_auth_email'),
                    'password' => config('salesforce.api_auth_password'),
                ]])->body())->token;
                Cache::put('salesforce_token', $token, config('salesforce.token_ttl'));
            } catch (\Throwable $th) {
                // TODO create this as a custom exception
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
     * Sets access to base module api
     *
     * @return self
     */
    public function module($module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Fetch all from a base module url
     *
     * @return \Illuminate\Support\Collection
     */
    public function all(): Collection
    {
        return collect(
            json_decode($this->http->get($this->module . '/')->body())->records
        );
    }

    /**
     * Get a record by id on a base module url
     *
     * @param string $id
     * @return object
     */
    public function getById(string $id): object
    {
        // TODO if module not defined trw custom exception
        return json_decode($this->http->get($this->module . '/' . $id . '/')->body());
    }

    /**
     * Create a record
     *
     * @param array $data
     * @return object
     */
    public function create(array $data): object
    {
        return json_decode($this->http->asMultipart()->post($this->module . '/', $data)->body());
    }

    /**
     * Update a record
     *
     * @param string $id
     * @param array $data
     * @return object
     */
    public function update(string $id, array $data): object
    {
        return json_decode($this->http->asMultipart()->patch($this->module . '/' . $id . '/', $data)->body());
    }

    /**
     * Delete a record
     *
     * @param string $id
     * @return object
     */
    public function delete(string $id): object
    {
        return json_decode($this->http->asMultipart()->delete($this->module . '/' . $id . '/')->body());
    }
}
