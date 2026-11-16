<?php

namespace Tests\Feature;

use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProgramScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_program_with_past_start_and_future_end(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Equipment Support Test',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Tractor Test',
            'description' => 'Test benefit',
        ]);

        $start = now()->subDay()->format('Y-m-d H:i:s');
        $end = now()->addDay()->format('Y-m-d H:i:s');

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'Updated description',
                'start_at' => $start,
                'end_at' => $end,
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('admin.programs.index'));

        $program->refresh();

        $this->assertSame($start, $program->start_at->format('Y-m-d H:i:s'));
        $this->assertSame($end, $program->end_at->format('Y-m-d H:i:s'));
    }

    public function test_admin_cannot_save_program_with_past_end_date(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'admin_registration_status' => 'approved',
        ]);

        $program = Program::create([
            'name' => 'Equipment Support Invalid Date Test',
            'description' => 'Original description',
            'is_active' => true,
        ]);

        $benefit = $program->benefits()->create([
            'name' => 'Tractor Test',
            'description' => 'Test benefit',
        ]);

        $response = $this->actingAs($admin)
            ->post(route('admin.programs.store'), [
                'program_id' => $program->id,
                'program_benefit_id' => $benefit->id,
                'description' => 'Updated description',
                'start_at' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'end_at' => now()->subDay()->format('Y-m-d H:i:s'),
            ]);

        $response->assertSessionHasErrors('end_at');
    }
}
