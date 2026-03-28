<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Yasumi\Yasumi;
use Carbon\Carbon;
use App\Models\Attendance;

class AttendancesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $holidays = Yasumi::create('Japan', 2026);

        $start = Carbon::create(2026, 2, 1);
        $end   = Carbon::yesterday();

        for ($date = $start; $date->lte($end); $date->addDay()) {
            if ($date->isWeekday() && !$holidays->isHoliday($date)) {
                Attendance::factory()->create([
                    'user_id'   => 2,
                    'work_date' => $date->toDateString(),
                ]);
            }
        }
    }
}
