<?php

namespace GestRisk;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class InjectFaviconLinks
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldInject($response)) {
            return $response;
        }

        $content = (string) $response->getContent();
        if ($content === '' || str_contains($content, 'cardioprenatal-favicon')) {
            return $response;
        }

        $links = trim(View::make('partials.favicon')->render());
        if ($links === '') {
            return $response;
        }

        $content = preg_replace(
            '#<link[^>]+rel=["\']icon["\'][^>]*>\s*#i',
            '',
            $content
        ) ?? $content;

        $updated = preg_replace(
            '/<head(\s[^>]*)?>/i',
            '<head$1>'."\n    ".$links,
            $content,
            1
        );

        if ($updated === null || $updated === $content) {
            return $response;
        }

        $response->setContent($updated);

        return $response;
    }

    private function shouldInject(Response $response): bool
    {
        if ($response->isRedirection()) {
            return false;
        }

        $contentType = strtolower((string) $response->headers->get('Content-Type', ''));

        return str_contains($contentType, 'text/html')
            || ($contentType === '' && str_contains((string) $response->getContent(), '<html'));
    }
}
