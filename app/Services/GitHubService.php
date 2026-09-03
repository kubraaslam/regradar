<?php

namespace App\Services;

use GuzzleHttp\Client;
use Exception;

class GitHubService
{
    protected Client $client;
    protected string $token;

    public function __construct()
    {
        $this->token = config('services.github.token');
        $this->client = new Client([
            'base_uri' => 'https://api.github.com/',
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
                'User-Agent' => 'RegRadar-App',
            ],
        ]);
    }

    public function fetchDiff(string $prUrl): string
    {
        // Parse PR URL to extract owner, repo, PR number
        // e.g. https://github.com/laravel/laravel/pull/123
        preg_match('#github\.com/([^/]+)/([^/]+)/pull/(\d+)#', $prUrl, $matches);

        if (count($matches) < 4) {
            throw new Exception('Invalid GitHub PR URL format.');
        }

        [, $owner, $repo, $prNumber] = $matches;

        $response = $this->client->get(
            "repos/{$owner}/{$repo}/pulls/{$prNumber}",
            [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3.diff',
                ],
            ]
        );

        return (string) $response->getBody();
    }
}