<?php

namespace Chompy\Services;

use Exception;
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

        parent::__construct($base_url, ['verify' => env('VERIFY_ROGUE_SSL_CERTIFICATE', true)]);
    }

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

        throw new ValidationException($errors, $endpoint);
    }

    /**
     * Get a post from Rogue give a set of filters.
     * TODO: Rename this as getPosts, or refactor it to return the first array item.
     * @see https://github.com/DoSomething/chompy/pull/58/files#r258543126
     *
     * @param array $inputs - The filters to use to grab the post.
     * @return array $post - The found post.
     */
    public function getPost($inputs)
    {
        $post = $this->asClient()->get('v3/posts', [
            'filter' => $inputs,
        ]);

        return $post;
    }

    /**
     * Create a post in rogue.
     *
     * @param array $data - The data to create the post with.
     * @return array $post - The created post.
     */
    public function createPost($data)
    {
        $multipartData = collect($data)->map(function ($value, $key) {
            return ['name' => $key, 'contents' => $value];
        })->values()->toArray();

        $post = $this->asClient()->send('POST', 'v3/posts', ['multipart' => $multipartData]);

        if (! $post['data']) {
            throw new Exception(500, 'Unable to create post for user: ' . $data['northstar_id']);
        }

        return $post;
    }

    /**
     * Update a post in rogue
     *
     * @param string $postId - The ID of the post to update.
     * @return array $post - The updated post.
     */
    public function updatePost($postId, $input)
    {
        $post = $this->asClient()->patch('v3/posts/'.$postId, $input);

        if (! $post['data']) {
            throw new Exception(500, 'Unable to update post for post: ' . $postId);
        }

        return $post;
    }

    /**
     * Get an action from Rogue based on CallPower campaign id.
     *
     * @param int $callpowerCampaignId
     * @return array $action
     */
    public function getActionFromCallPowerCampaignId($callpowerCampaignId)
    {
        $action = $this->asClient()->get('v3/actions', [
            'filter' => ['callpower_campaign_id' => $callpowerCampaignId],
        ]);

        if (! $action['data']) {
            throw ValidationException::withMessages('Unable to get action data for CallPower campaign id : ' . $callpowerCampaignId);
        }

        return $action;
    }
}
