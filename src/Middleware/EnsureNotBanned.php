<?php

namespace EloquentWorks\Exile\Middleware;

use Closure;
use EloquentWorks\Exile\Exceptions\BannedException;
use EloquentWorks\Exile\Services\ExileManager;
use EloquentWorks\Exile\Support\EnforcementContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure that the user is not banned.
 */
final class EnsureNotBanned
{
    /**
     * Create a new middleware instance.
     *
     * @param  ExileManager  $exile  The ExileManager instance.
     */
    public function __construct(private readonly ExileManager $exile) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware in the pipeline.
     * @return Response The HTTP response after processing the request.
     *
     * @throws BannedException If the user is banned.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Retrieve the authenticated user from the request
        $user = $request->user();

        // Determine the account based on the authenticated user
        $account = $user instanceof Model ? $user : null;

        // Retrieve the device fingerprint from the request headers
        $deviceHeader = (string) config('exile.security.device_header', 'X-Device-Fingerprint');

        // Get the device fingerprint from the request header
        $device = $request->header($deviceHeader);

        // Determine the IP address based on the configuration
        $ipAddress = config('exile.security.trust_request_ip', true) ? $request->ip() : null;

        // Resolve any active ban for the given account, IP address, and device fingerprint
        $ban = $this->exile->resolveActiveBan(new EnforcementContext(
            account: $account,
            ipAddress: $ipAddress,
            deviceFingerprint: is_string($device) && $device !== '' ? $device : null,
        ));

        if ($ban !== null) {
            // If an active ban is found, throw a BannedException to prevent further action
            throw new BannedException($ban);
        }

        /** @var Response $response */
        $response = $next($request);

        // Return the response after processing the request
        return $response;
    }
}
