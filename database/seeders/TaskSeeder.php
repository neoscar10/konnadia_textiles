<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tasks = [
            ['name' => 'Cutting', 'code' => 'TSK-001'],
            ['name' => 'Stitching', 'code' => 'TSK-002'],
            ['name' => 'Finishing', 'code' => 'TSK-003'],
            ['name' => 'Quality Check (QC)', 'code' => 'TSK-004'],
            ['name' => 'Ironing', 'code' => 'TSK-005'],
            ['name' => 'Packing', 'code' => 'TSK-006'],
        ];

        foreach ($tasks as $task) {
            \App\Models\Task::firstOrCreate(
                ['code' => $task['code']],
                ['name' => $task['name'], 'status' => true]
            );
        }
    }
}
