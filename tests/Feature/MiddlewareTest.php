<?php

namespace Tests\Feature;

use EloquentWorks\Exile\Enums\RestrictionType;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MiddlewareTest extends TestCase
{
    /**
     * Set up the test environment and define routes for middleware testing.
     */
    protected function setUp(): void
    {
        // Set up the test environment and define routes for middleware testing
        parent::setUp();

        // Define test routes for middleware testing
        Route::get('/protected', fn () => response()->json(['ok' => true]))->middleware('exile');
        Route::post('/posts', fn () => response()->json(['ok' => true]))->middleware('exile.allowed:posting');
        Route::get('/shadow', fn () => response()->json(['shadowed' => request()->attributes->get('exile.shadowed')]))->middleware('exile.shadow');
    }

    #[Test]
    public function ban_middleware_returns_403_for_banned_users(): void
    {
        // Test that the ban middleware returns a 403 response for banned users
        $user = $this->user();
        $user->ban('Testing ban');

        // Act as the banned user and make a GET request to the protected route, asserting
        // that the response is forbidden and contains the expected ban reason
        $this->actingAs($user)->getJson('/protected')->assertForbidden()->assertJsonPath('reason', 'Testing ban');
    }

    #[Test]
    public function restriction_middleware_blocks_posting(): void
    {
        // Test that the restriction middleware blocks posting for users with a posting restriction
        $user = $this->user();
        $user->restrict(RestrictionType::Posting, 'Posting cooldown');

        // Act as the restricted user and make a POST request to the posts route, asserting
        $this->actingAs($user)->postJson('/posts')->assertForbidden()->assertJsonPath('restriction', 'posting');
    }

    #[Test]
    public function shadow_middleware_marks_the_request_without_blocking_it(): void
    {
        // Test that the shadow middleware marks the request without blocking it for users with a shadow restriction
        $user = $this->user();
        $user->restrict(RestrictionType::Shadow);

        // Act as the shadowed user and make a GET request to the shadow route, asserting
        $this->actingAs($user)->getJson('/shadow')->assertOk()->assertJson(['shadowed' => true]);
    }
}
