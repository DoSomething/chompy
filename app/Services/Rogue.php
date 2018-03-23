<?php

namespace App\Services;

use DoSomething\Gateway\AuthorizesWithOAuth2;
use DoSomething\Gateway\Common\RestApiClient;
use DoSomething\Gateway\Exceptions\ValidationException;

class Rogue extends RestApiClient
{
    use AuthorizesWithOAuth2;

    /**
     * Create a new RogueClient instance.
     */
    public function __construct()
    {
        $this->authorizationServerUri = config('services.northstar.url');
        $this->bridge = config('services.northstar.bridge');
        $this->grant = config('services.northstar.grant');
        $this->config = config('services.northstar');

        $base_url = config('services.rogue.url') . '/api/';

        parent::__construct($base_url);
    }


    /**
     * Store post in Rogue.
     *
     * @param  array  $payload
     * @return array - JSON response
     */
    // public function storePost($payload = [])
    // {
    //     // unset($payload['media']);
    //     // // Guzzle expects specific file object for multipart form.
    //     // // @TODO: upadte Gateway to handle multipart form data.
    //     // $payload['file'] = fopen($payload['file']->getPathname(), 'r');
    //     // $multipartData = collect($payload)->map(function ($value, $key) {
    //     //     return ['name' => $key, 'contents' => $value];
    //     // })->values()->toArray();
    //     return $this->withToken(token())->send('POST', 'v3/posts', $payload);
    // }

    /**
     * Handle validation exceptions.
     *
     * @param string $endpoint - The human-readable route that triggered the error.
     * @param array $response - The body of the response.
     * @param string $method - The HTTP method for the request that triggered the error, for optionally resending.
     * @param string $path - The path for the request that triggered the error, for optionally resending.
     * @param array $options - The options for the request that triggered the error, for optionally resending.
     * @return \GuzzleHttp\Psr7\Response|void
     * @throws UnauthorizedException
     */
    public function handleValidationException($endpoint, $response, $method, $path, $options)
    {
        $errors = $response['errors'];
        dd($endpoint, $response, $method, $path, $options);
        throw new ValidationException($errors, $endpoint);
    }

}
