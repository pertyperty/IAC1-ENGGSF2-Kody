<?php

namespace App\Services\Content;

class VideoEmbed
{
    /** Exact HTTPS host/identifier matching; arbitrary iframe HTML is never stored or fetched. */
    public function url(?string $link): ?string
    {
        $parts = parse_url($link ?? '');
        if (! is_array($parts) || ($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);
        $id = null;
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            $id = $path === '/watch' ? ($query['v'] ?? null) : (preg_match('~^/(?:embed|shorts)/([A-Za-z0-9_-]{11})/?$~D', $path, $match) ? $match[1] : null);
        } elseif ($host === 'youtu.be') {
            $id = trim($path, '/');
        } elseif ($host === 'www.youtube-nocookie.com' && preg_match('~^/embed/([A-Za-z0-9_-]{11})/?$~D', $path, $match)) {
            $id = $match[1];
        } elseif (in_array($host, ['vimeo.com', 'www.vimeo.com', 'player.vimeo.com'], true) && preg_match('~^/(?:video/)?([0-9]{1,12})/?$~D', $path, $match)) {
            return 'https://player.vimeo.com/video/'.$match[1];
        }

        return is_string($id) && preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id) ? 'https://www.youtube-nocookie.com/embed/'.$id : null;
    }
}
