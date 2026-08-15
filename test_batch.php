<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$comp = new \App\Livewire\Admin\Production\JobDetailPage();
// Mock a task that does NOT consume raw material (like Finishing)
$comp->selectedTask = App\Models\Task::where('consumes_raw_material', 0)->first();
$comp->wizardStep = 1;
echo "Initial Wizard Step: " . $comp->wizardStep . "\n";
echo "Initial Active Step: " . $comp->activeStep . "\n";
$comp->setWizardStep(2);
echo "After setWizardStep(2), Wizard Step: " . $comp->wizardStep . "\n";
echo "After setWizardStep(2), Active Step: " . $comp->activeStep . "\n";
