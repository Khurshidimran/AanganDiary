<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Writes every Rider mobile app request to
 * storage/logs/rider-app/{Y-m-d}/{rider}.log — one file per rider per day —
 * so exactly what the mobile app sent (and what it got back) can be
 * verified independent of whatever ends up in the app's normal error logs.
 * Applied to the whole v1/rider route group (see routes/api.php), including
 * login, so a failed login attempt is still traceable.
 *
 * A framework exception (validation failure, etc.) never actually reaches
 * this middleware as a Throwable — Laravel's routing layer renders it into
 * a normal Response before control returns to route-level middleware like
 * this one — so a 4xx/5xx JSON response's body is read back out and logged
 * alongside the input instead. The try/catch below is only a defensive
 * fallback for something genuinely unhandled; this middleware never alters
 * how a request is handled either way.
 */
class LogRiderApiRequest
{
    private const REDACTED_FIELDS = ['password', 'password_confirmation'];

    public function handle(Request $request, Closure $next): Response
    {
        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->write($request, null, $e);

            throw $e;
        }

        $this->write($request, $response);

        return $response;
    }

    private function write(Request $request, ?Response $response, ?Throwable $exception = null): void
    {
        $directory = storage_path('logs/rider-app/'.now()->format('Y-m-d'));

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $entry = [
            'time' => now()->format('H:i:s.v'),
            'method' => $request->method(),
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'status' => $response?->getStatusCode() ?? 500,
            'input' => $this->sanitizedInput($request),
        ];

        if ($exception) {
            $entry['exception'] = $exception->getMessage();
        } elseif ($response && $response->getStatusCode() >= 400 && $response instanceof JsonResponse) {
            $entry['error'] = json_decode($response->getContent(), true);
        }

        File::append(
            "{$directory}/{$this->riderIdentifier($request)}.log",
            json_encode($entry, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL,
        );
    }

    /**
     * Named after the authenticated rider once a token has resolved one —
     * the login route itself runs before that exists, so a failed login
     * still lands in a traceable file keyed by whatever email was attempted.
     */
    private function riderIdentifier(Request $request): string
    {
        $rider = $request->user()?->riderProfile;

        if ($rider) {
            return $this->slug($rider->user->name).'-'.substr($rider->id, 0, 8);
        }

        return $this->slug((string) ($request->input('email') ?? $request->ip()));
    }

    private function slug(string $value): string
    {
        return trim(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '', '-') ?: 'unknown';
    }

    /**
     * @return array<string, mixed>
     */
    private function sanitizedInput(Request $request): array
    {
        $input = $request->except(self::REDACTED_FIELDS);

        // File uploads (delivery photo/signature) are replaced with a
        // placeholder — dumping raw/base64 binary into a log file would
        // bloat it for no benefit, since the point is verifying form
        // fields, not the file bytes.
        foreach ($request->allFiles() as $key => $file) {
            $input[$key] = is_array($file)
                ? array_map(fn ($f) => "[file: {$f->getClientOriginalName()}, {$f->getSize()} bytes]", $file)
                : "[file: {$file->getClientOriginalName()}, {$file->getSize()} bytes]";
        }

        return $input;
    }
}
