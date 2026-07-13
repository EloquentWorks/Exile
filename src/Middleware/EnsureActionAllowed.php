<?php

namespace EloquentWorks\Exile\Middleware;

use Closure;
use EloquentWorks\Exile\Enums\RestrictionType;
use EloquentWorks\Exile\Exceptions\RestrictedException;
use EloquentWorks\Exile\Services\ExileManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to ensure that a user is allowed to perform a specific action based on their restrictions.
 */
final class EnsureActionAllowed
{
    /**
     * Create a new middleware instance.
     *
     * @param  ExileManager  $exile  The ExileManager instance for managing restrictions.
     */
    public function __construct(private readonly ExileManager $exile) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Closure  $next  The next middleware or request handler.
     * @param  string  $type  The type of restriction to check against.
     * @return Response The HTTP response after processing the request.
     *
     * @throws InvalidArgumentException If the provided restriction type is unknown.
     * @throws RestrictedException If the user has an active restriction of the specified type.
     */
    public function handle(Request $request, Closure $next, string $type): Response
    {
        // Retrieve the authenticated user from the request
        $user = $request->user();

        // If the user is not an instance of Model, proceed to the next middleware without restriction checks
        if (! $user instanceof Model) {
            /** @var Response $response */
            $response = $next($request);

            // Return the response from the next middleware or request handler
            return $response;
        }

        // Attempt to convert the provided restriction type string into a RestrictionType enum instance
        $restrictionType = RestrictionType::tryFrom($type);

        // If the restriction type is unknown, throw an InvalidArgumentException
        if ($restrictionType === null) {
            throw new InvalidArgumentException("Unknown Exile restriction type [{$type}].");
        }

        // Check if the user has an active restriction of the specified type
        $restriction = $this->exile->activeRestrictionFor($user, $restrictionType);

        // If an active restriction is found, throw a RestrictedException to prevent further action
        if ($restriction !== null) {
            throw new RestrictedException($restriction);
        }

        /** @var Response $response */
        $response = $next($request);

        // Return the response from the next middleware or request handler
        return $response;
    }
}
