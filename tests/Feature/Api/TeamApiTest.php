<?php

declare(strict_types=1);

use He4rt\Identity\Teams\Team;
use He4rt\Identity\Teams\TeamStatus;
use He4rt\Identity\Users\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

it('rejects an unauthenticated request', function (): void {
    getJson('/api/teams')->assertUnauthorized();
});

it('lists teams newest first', function (): void {
    actingAs(User::factory()->create());
    $older = Team::factory()->create(['created_at' => now()->subDay()]);
    $newer = Team::factory()->create();

    getJson('/api/teams')
        ->assertOk()
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id);
});

it('filters the listing by status', function (): void {
    actingAs(User::factory()->create());
    Team::factory()->create(['status' => TeamStatus::Active]);
    $archived = Team::factory()->create(['status' => TeamStatus::Archived]);

    getJson('/api/teams?status=archived')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $archived->id);
});

it('searches by name and slug', function (): void {
    actingAs(User::factory()->create());
    $match = Team::factory()->create(['name' => 'He4rt Developers', 'slug' => 'he4rt-developers']);
    Team::factory()->create(['name' => 'Another Team', 'slug' => 'another-team']);

    getJson('/api/teams?search=he4rt')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('shows one team', function (): void {
    actingAs(User::factory()->create());
    $team = Team::factory()->create();

    getJson("/api/teams/{$team->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $team->id)
        ->assertJsonPath('data.slug', $team->slug);
});

it('answers 404 for a team that does not exist', function (): void {
    actingAs(User::factory()->create());

    getJson('/api/teams/9f8c2b1a-4d3e-4f5a-8b6c-7d8e9f0a1b2c')->assertNotFound();
});

it('creates a team as active by default', function (): void {
    actingAs(User::factory()->create());
    $owner = User::factory()->create();

    postJson('/api/teams', [
        'name' => 'He4rt Developers',
        'slug' => 'he4rt-developers',
        'description' => 'The community team behind the open source projects.',
        'contact_email' => 'contact@he4rt.dev',
        'owner_id' => $owner->id,
    ])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'he4rt-developers')
        ->assertJsonPath('data.status', TeamStatus::Active->value);
});

it('rejects a slug that is not url safe', function (): void {
    actingAs(User::factory()->create());
    $owner = User::factory()->create();

    postJson('/api/teams', [
        'name' => 'He4rt Developers',
        'slug' => 'He4rt Developers',
        'description' => 'A description.',
        'contact_email' => 'contact@he4rt.dev',
        'owner_id' => $owner->id,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('slug');
});

it('rejects a duplicated slug', function (): void {
    actingAs(User::factory()->create());
    $owner = User::factory()->create();
    Team::factory()->create(['slug' => 'he4rt-developers']);

    postJson('/api/teams', [
        'name' => 'He4rt Developers',
        'slug' => 'he4rt-developers',
        'description' => 'A description.',
        'contact_email' => 'contact@he4rt.dev',
        'owner_id' => $owner->id,
    ])->assertJsonValidationErrors('slug');
});

it('updates only the fields present in the payload', function (): void {
    actingAs(User::factory()->create());
    $team = Team::factory()->create(['name' => 'Original', 'status' => TeamStatus::Active]);

    patchJson("/api/teams/{$team->id}", ['status' => TeamStatus::Suspended->value])
        ->assertOk()
        ->assertJsonPath('data.name', 'Original')
        ->assertJsonPath('data.status', TeamStatus::Suspended->value);
});

it('soft deletes a team and answers 204', function (): void {
    actingAs(User::factory()->create());
    $team = Team::factory()->create();

    deleteJson("/api/teams/{$team->id}")->assertNoContent();

    expect(Team::query()->find($team->id))->toBeNull()
        ->and(Team::withTrashed()->find($team->id))->not->toBeNull();
});
