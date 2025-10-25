<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Livewire\Pos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class PosMountTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_mounts_without_null_pointer_error(): void
    {
        // This test specifically checks that the mount method
        // doesn't fail with "Call to a member function pluck() on null"
        // which was the original error
        Livewire::test(Pos::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.pos');
            
        // The key assertion is that the component mounts successfully
        // without throwing a null pointer exception
        $this->assertTrue(true);
    }
}
