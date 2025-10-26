<?php

declare(strict_types=1);

use App\Livewire\Pos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('pos mounts without null pointer error', function (): void {
    // This test specifically checks that mount method
    // doesn't fail with "Call to a member function pluck() on null"
    // which was the original error
    Livewire::test(Pos::class)->assertStatus(200)->assertViewIs('livewire.pos');

    // The key assertion is that the component mounts successfully
    // without throwing a null pointer exception
    expect(true)->toBeTrue();
});
