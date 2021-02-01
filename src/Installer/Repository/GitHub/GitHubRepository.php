<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Installer\Repository\GitHub;

use Spiral\RoadRunner\Installer\Repository\ReleasesCollection;
use Spiral\RoadRunner\Installer\Repository\RepositoryInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GitHubRepository implements RepositoryInterface
{
    /**
     * @var string
     */
    public const OFFICIAL_REPOSITORY_OWNER = 'spiral';

    /**
     * @var string
     */
    public const OFFICIAL_REPOSITORY_NAME = 'roadrunner';

    /**
     * @var string
     */
    private const URL_RELEASES = 'https://api.github.com/repos/%s/releases';

    /**
     * @var HttpClientInterface
     */
    private HttpClientInterface $client;

    /**
     * @var string
     */
    private string $name;

    /**
     * @var array|string[]
     */
    private array $headers = [
        'accept' => 'application/vnd.github.v3+json',
    ];

    /**
     * @param string $owner
     * @param string $repository
     * @param HttpClientInterface|null $client
     */
    public function __construct(string $owner, string $repository, HttpClientInterface $client = null)
    {
        $this->name = $owner . '/' . $repository;
        $this->client = $client ?? HttpClient::create();
    }

    /**
     * @param HttpClientInterface|null $client
     * @param array|null $variables
     * @return self
     */
    public static function createFromGlobals(HttpClientInterface $client = null, array $variables = null): self
    {
        $factory = new Factory($client);

        return $factory->createFromGlobals($variables);
    }

    /**
     * {@inheritDoc}
     * @throws ExceptionInterface
     */
    public function getReleases(): ReleasesCollection
    {
        return ReleasesCollection::from(function () {
            $page = 0;

            do {
                $response = $this->releasesRequest(++$page);

                foreach ($response->toArray() as $data) {
                    $release = GitHubRelease::fromApiResponse($this->client, $data);

                    yield $release->getVersion() => $release;
                }
            } while ($this->hasNextPage($response));
        });
    }

    /**
     * @param positive-int $page
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    private function releasesRequest(int $page): ResponseInterface
    {
        return $this->request('GET', $this->uri(self::URL_RELEASES), [
            'query' => [
                'page' => $page,
            ],
        ]);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     * @see HttpClientInterface::request()
     */
    protected function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        // Merge headers with defaults
        $options['headers'] = \array_merge($this->headers, $options['headers'] ?? []);

        return $this->client->request($method, $uri, $options);
    }

    /**
     * @param string $pattern
     * @return string
     */
    private function uri(string $pattern): string
    {
        return \sprintf($pattern, $this->getName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param ResponseInterface $response
     * @return bool
     * @throws ExceptionInterface
     */
    private function hasNextPage(ResponseInterface $response): bool
    {
        $headers = $response->getHeaders();
        $link = $headers['link'] ?? [];

        if (! isset($link[0])) {
            return false;
        }

        return \str_contains($link[0], 'rel="next"');
    }
}
