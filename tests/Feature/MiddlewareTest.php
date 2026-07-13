<?php

namespace Tests\Feature;

use EloquentWorks\Exile\Enums\RestrictionType;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/protected', fn () => response()->json(['ok' => true]))->middleware('exile');
        Route::post('/posts', fn () => response()->json(['ok' => true]))->middleware('exile.allowed:posting');
        Route::get('/shadow', fn () => response()->json(['shadowed' => request()->attributes->get('exile.shadowed')]))->middleware('exile.shadow');
    }

    #[Test]
    public function ban_middleware_returns_403_for_banned_users(): void
    {
        $user = $this->user();
        $user->ban('Testing ban');

        $this->actingAs($user)->getJson('/protected')->assertForbidden()->assertJsonPath('reason', 'Testing ban');
    }

    #[Test]
    public function restriction_middleware_blocks_posting(): void
    {
        $user = $this->user();
        $user->restrict(RestrictionType::Posting, 'Posting cooldown');

        $this->actingAs($user)->postJson('/posts')->assertForbidden()->assertJsonPath('restriction', 'posting');
    }

    #[Test]
    public function shadow_middleware_marks_the_request_without_blocking_it(): void
    {
        $user = $this->user();
        $user->restrict(RestrictionType::Shadow);

        $this->actingAs($user)->getJson('/shadow')->assertOk()->assertJson(['shadowed' => true]);
    }
}
