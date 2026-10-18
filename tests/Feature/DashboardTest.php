<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_a_user_without_role_waits_for_approval(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/dashboard')->assertRedirect(route('cuenta.pendiente'));
    }
}
