<?php

declare(strict_types=1);

namespace EloquentWorks\Exile\Middleware;

use Closure;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to mark requests from shadow-banned users.
 */
final class MarkShadowBanned
{
    /**
     * Constructor.
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
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if the user is authenticated and is an instance of Model
        $user = $request->user();

        // If the user is authenticated, check for an active shadow restriction and set request attributes accordingly
        if ($user instanceof Model) {
            $restriction = $this->exile->activeRestrictionFor($user, RestrictionType::Shadow);
            $request->attributes->set('exile.shadowed', $restriction !== null);
            $request->attributes->set('exile.shadow_restriction', $restriction);
        }

        /** @var Response $response */
        $response = $next($request);

        // Return the next middleware's response, which may have been modified by the shadow ban check
        return $response;
    }
}
