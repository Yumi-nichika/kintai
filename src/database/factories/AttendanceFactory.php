<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\BreakTime;

class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id'   => 1,
            'work_date' => now()->toDateString(),
            'start_time' => '09:00:00',
            'end_time'  => '18:00:00',
            'note'      => null,
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($attendance) {
            BreakTime::factory()->create([
                'attendance_id' => $attendance->id,
            ]);
        });
    }
}
