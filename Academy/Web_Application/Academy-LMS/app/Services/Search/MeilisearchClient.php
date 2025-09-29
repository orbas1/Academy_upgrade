<?php

namespace App\Services\Search;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class MeilisearchClient
{
    private string $host;

    private ?string $key;

    private int $timeout;

    public function __construct(string $host, ?string $key = null, int $timeout = 10)
    {
        $this->host = rtrim($host, '/');
        $this->key = $key ?: null;
        $this->timeout = $timeout;
    }

    public function health(): array
    {
        return $this->request('get', '/health');
    }

    public function ensureIndex(string $index, ?string $primaryKey = null): array
    {
        $payload = array_filter([
            'primaryKey' => $primaryKey,
        ]);

        return $this->request('put', "/indexes/{$index}", $payload);
    }

    public function updateSettings(string $index, array $settings): array
    {
        $allowed = [
            'displayedAttributes',
            'filterableAttributes',
            'rankingRules',
            'searchableAttributes',
            'sortableAttributes',
            'stopWords',
            'synonyms',
            'typoTolerance',
        ];

        $payload = Arr::only($settings, $allowed);

        if (empty($payload)) {
            return [];
        }

        return $this->request('patch', "/indexes/{$index}/settings", $payload);
    }

    /**
     * @param array<int, array<string, mixed>> $documents
     */
    public function upsertDocuments(string $index, array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        return $this->request('post', "/indexes/{$index}/documents", $documents);
    }

    public function search(string $index, array $options = []): array
    {
        return $this->request('post', "/indexes/{$index}/search", $options);
    }

    /**
     * @param array<int, int|string> $identifiers
     */
    public function deleteDocuments(string $index, array $identifiers): array
    {
        if (empty($identifiers)) {
            return [];
        }

        return $this->request('post', "/indexes/{$index}/documents/delete", [
            'ids' => array_values($identifiers),
        ]);
    }

    protected function request(string $method, string $path, array $payload = []): array
    {
        $request = $this->baseRequest();

        $response = match (strtolower($method)) {
            'get' => $request->get($path),
            'post' => $request->post($path, $payload),
            'put' => $request->put($path, $payload),
            'patch' => $request->patch($path, $payload),
            'delete' => $request->delete($path, $payload),
            default => throw new RuntimeException("Unsupported HTTP method [{$method}]"),
        };

        if ($response->failed()) {
            throw new RuntimeException(
                sprintf(
                    'Meilisearch request to %s failed with status %s: %s',
                    $path,
                    $response->status(),
                    $response->body()
                )
            );
        }

        return $response->json() ?? [];
    }

    protected function baseRequest(): PendingRequest
    {
        $request = Http::baseUrl($this->host)
            ->timeout($this->timeout)
            ->acceptJson();

        if ($this->key) {
            $request = $request->withToken($this->key);
        }

        return $request;
    }
}
