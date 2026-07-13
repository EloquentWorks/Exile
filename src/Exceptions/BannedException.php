<?php

namespace EloquentWorks\Exile\Exceptions;

use EloquentWorks\Exile\Models\Ban;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Exception thrown when a user is banned.
 */
final class BannedException extends RuntimeException
{
    /**
     * Create a new BannedException instance.
     *
     * @param  Ban  $ban  The ban instance associated with the exception.
     */
    public function __construct(public readonly Ban $ban)
    {
        // Call the parent constructor with a default message or a custom message from the configuration.
        parent::__construct((string) config('exile.responses.ban_message', 'Your access has been suspended.'));
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return JsonResponse|Response The HTTP response to be sent back to the client.
     */
    public function render(Request $request): JsonResponse|Response
    {
        // Prepare the payload for the response, including the ban message and ban ID.
        $payload = ['message' => $this->getMessage(), 'ban_id' => $this->ban->getKey()];

        // Include the ban reason in the response if configured to do so.
        if (config('exile.responses.include_reason', true)) {
            $payload['reason'] = $this->ban->reason;
        }

        // Include the ban expiration time in the response if configured to do so.
        if (config('exile.responses.include_expiration', true)) {
            $payload['expires_at'] = $this->ban->expires_at?->toISOString();
        }

        // Return a JSON response if the request expects JSON, otherwise return a plain response with the ban message.
        if ($request->expectsJson()) {
            return response()->json($payload, 403);
        }

        // Return a plain response with the ban message and a 403 status code if JSON is not expected.
        return response($this->getMessage(), 403);
    }
}
