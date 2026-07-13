<?php

namespace EloquentWorks\Exile\Exceptions;

use EloquentWorks\Exile\Models\Restriction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Exception thrown when a user is restricted from performing an action.
 */
final class RestrictedException extends RuntimeException
{
    /**
     * Create a new RestrictedException instance.
     *
     * @param  Restriction  $restriction  The restriction instance associated with the exception.
     */
    public function __construct(public readonly Restriction $restriction)
    {
        parent::__construct((string) config('exile.responses.restriction_message', 'This action is currently restricted.'));
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return JsonResponse|Response The HTTP response to be sent back to the client.
     */
    public function render(Request $request): JsonResponse|Response
    {
        // Prepare the payload for the response, including the restriction message, type, reason, and expiration time.
        $payload = [
            'message' => $this->getMessage(),
            'restriction' => $this->restriction->type->value,
            'reason' => config('exile.responses.include_reason', true) ? $this->restriction->reason : null,
            'expires_at' => config('exile.responses.include_expiration', true)
                ? $this->restriction->expires_at?->toISOString()
                : null,
        ];

        // Return a JSON response if the request expects JSON, otherwise return a plain response with the restriction message.
        if ($request->expectsJson()) {
            return response()->json($payload, 403);
        }

        // Return a plain response with the restriction message and a 403 status code if JSON is not expected.
        return response($this->getMessage(), 403);
    }
}
