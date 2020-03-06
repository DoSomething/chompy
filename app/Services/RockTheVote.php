<?php

namespace Chompy\Services;

class RockTheVote
{
    /**
     * The Rock The Vote API client.
     *
     * @var Client
     */
    protected $client;

    /**
     * Create a new Rock The Vote API client.
     */
    public function __construct()
    {
        $config = config('services.rock_the_vote');

        $this->client = new \GuzzleHttp\Client([
            'base_uri' => $config['url'] . '/api/v4/',
        ]);
        $this->authQuery = [
            'partner_API_key' => $config['api_key'],
            'partner_id' => $config['partner_id'],
        ];
    }

    /**
     * Get a Rock The Vote report by ID.
     * @see https://rock-the-vote.github.io/Voter-Registration-Tool-API-Docs/?emci=da3e348b-feda-e911-b5e9-2818784d6d68&emdi=925c56a9-00db-e911-b5e9-2818784d6d68&ceid=2948039#report_status
     *
     * @param int $id
     * @return array
     */
    public function getReportById($id)
    {
        $response = $this->client->get('registrant_reports/'.$id, [
            'query' => $this->authQuery,
        ]);

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Download a Rock The Vote report by ID.
     *
     * @param int $id
     * @return string
     */
    public function downloadReportById($id)
    {
        $response = $this->client->get('registrant_reports/'.$id.'/download', [
            'query' => $this->authQuery,
        ]);

        return $response->getBody();
    }
}
