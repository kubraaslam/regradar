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

    /**
     * Split a pull request URL into its parts.
     *
     * e.g. https://github.com/laravel/framework/pull/41969
     *   -> ['owner' => 'laravel', 'repo' => 'framework',
     *       'repository' => 'laravel/framework', 'number' => 41969]
     *
     * @return array{owner: string, repo: string, repository: string, number: int}
     */
    public static function parsePullRequestUrl(string $prUrl): array
    {
        preg_match('#github\.com/([^/]+)/([^/]+)/pull/(\d+)#', $prUrl, $matches);

        if (count($matches) < 4) {
            throw new Exception('That is not a GitHub pull request URL. Expected a link of the form https://github.com/owner/repo/pull/123.');
        }

        [, $owner, $repo, $number] = $matches;

        return [
            'owner' => $owner,
            'repo' => $repo,
            'repository' => strtolower($owner . '/' . $repo),
            'number' => (int) $number,
        ];
    }

    public function fetchDiff(string $prUrl): string
    {
        $pr = static::parsePullRequestUrl($prUrl);
        $owner = $pr['owner'];
        $repo = $pr['repo'];
        $prNumber = $pr['number'];

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