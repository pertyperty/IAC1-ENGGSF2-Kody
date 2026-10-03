<?php

namespace App\Services\Challenges\Judge0;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class Judge0Client
{
    public function fingerprint(): string
    {
        return hash('sha256', json_encode(config('judge0'), JSON_THROW_ON_ERROR));
    }

    public function configured(): bool
    {
        $url = parse_url(config('judge0.url'));

        return config('judge0.enabled') && is_array($url) && ($url['scheme'] ?? '') === 'https'
            && ! empty($url['host']) && ! isset($url['user']) && ! isset($url['pass'])
            && ! isset($url['query']) && ! isset($url['fragment'])
            && count(array_filter(config('judge0.languages'), fn ($id) => is_int($id) && $id > 0)) === 3;
    }

    public function languages(): array
    {
        return $this->request('GET', '/languages/');
    }

    public function limits(): array
    {
        return $this->request('GET', '/config_info');
    }

    public function create(string $source, int $languageId, string $input, string $expected, int $cpuMs, int $memory): string
    {
        $data = $this->request('POST', '/submissions?base64_encoded=true&wait=false', [
            'source_code' => base64_encode($source), 'language_id' => $languageId,
            'stdin' => base64_encode($input), 'expected_output' => base64_encode($expected),
            'cpu_time_limit' => $cpuMs / 1000, 'cpu_extra_time' => 0.5, 'wall_time_limit' => 15,
            'memory_limit' => $memory, 'stack_limit' => min(64000, $memory),
            'max_processes_and_or_threads' => 60, 'max_file_size' => 1024,
            'enable_per_process_and_thread_time_limit' => false,
            'enable_per_process_and_thread_memory_limit' => false,
            'enable_network' => false, 'number_of_runs' => 1, 'redirect_stderr_to_stdout' => false,
        ]);
        if (! is_string($data['token'] ?? null) || ! Str::isUuid($data['token'])) {
            throw new ProviderUnavailable;
        }

        return $data['token'];
    }

    public function result(string $token): EvaluationResult
    {
        if (! Str::isUuid($token)) {
            throw new ProviderUnavailable;
        }
        // Request only the verdict. Program output can reproduce hidden inputs.
        $data = $this->request('GET', '/submissions/'.$token.'?fields=status&base64_encoded=true');
        $status = $data['status']['id'] ?? null;
        if (! is_int($status) || $status < 1 || $status > 14) {
            throw new ProviderUnavailable;
        }

        return new EvaluationResult($status);
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (! $this->configured()) {
            throw new ProviderUnavailable;
        }
        try {
            $headers = array_filter(['X-RapidAPI-Key' => config('judge0.rapidapi_key'),
                'X-RapidAPI-Host' => config('judge0.rapidapi_host'), 'X-Auth-Token' => config('judge0.auth_token')]);
            $request = Http::acceptJson()->asJson()->withHeaders($headers)->connectTimeout(3)->timeout(5)
                ->withOptions(['allow_redirects' => false, 'stream' => true, 'read_timeout' => 5]);
            $response = $request->send($method, rtrim(config('judge0.url'), '/').$path,
                $method === 'POST' ? ['json' => $payload] : []);
            $stream = $response->toPsrResponse()->getBody();
            try {
                if (! $response->successful()) {
                    throw new ProviderUnavailable;
                }
                $body = '';
                while (! $stream->eof() && strlen($body) <= config('judge0.response_bytes')) {
                    $chunk = $stream->read(min(8192, config('judge0.response_bytes') + 1 - strlen($body)));
                    if ($chunk === '' && ! $stream->eof()) {
                        throw new ProviderUnavailable;
                    }
                    $body .= $chunk;
                }
                if (strlen($body) > config('judge0.response_bytes')) {
                    throw new ProviderUnavailable;
                }
                $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
                if (! is_array($data)) {
                    throw new ProviderUnavailable;
                }

                return $data;
            } finally {
                $stream->close();
            }
        } catch (Throwable) {
            throw new ProviderUnavailable;
        }
    }
}
