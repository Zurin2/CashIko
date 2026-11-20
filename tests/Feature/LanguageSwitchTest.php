<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Language preference: English by default, Filipino switchable from Settings. */
class LanguageSwitchTest extends TestCase
{
    use RefreshDatabase;

    private function onboard(): User
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/onboarding', [
            'name' => 'Tindahan ni Aling Nena',
            'type' => 'Sari-sari store',
            'location' => 'Cagayan de Oro',
        ])->assertRedirect('/home');

        return $user->fresh();
    }

    private function settingsPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tindahan ni Aling Nena',
            'type' => 'Sari-sari store',
            'location' => 'Cagayan de Oro',
            'default_due_days' => 7,
            'default_reminder_tone' => 'gentle',
            'low_stock_threshold' => 5,
            'language' => 'english',
        ], $overrides);
    }

    public function test_new_business_defaults_to_english(): void
    {
        $user = $this->onboard();

        $this->assertSame('english', $user->business()->first()->language);
    }

    public function test_language_setting_switches_server_side_messages(): void
    {
        $user = $this->onboard();

        $this->actingAs($user)->post('/settings', $this->settingsPayload(['language' => 'filipino']))
            ->assertRedirect('/settings');

        $this->assertSame('filipino', $user->business()->first()->language);

        // Same request lifecycle now resolves Filipino copy on the server.
        $this->post('/settings/payment-methods', ['label' => 'ShopeePay'])
            ->assertSessionHas('success', 'Nadagdag ang ShopeePay.');
    }

    public function test_retired_language_value_is_rejected(): void
    {
        $user = $this->onboard();

        $this->actingAs($user)->post('/settings', $this->settingsPayload(['language' => 'taglish']))
            ->assertSessionHasErrors('language');
    }
}
