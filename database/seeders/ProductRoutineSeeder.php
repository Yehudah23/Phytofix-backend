<?php

namespace Database\Seeders;

use App\Models\ProductRoutine;
use App\Models\RoutineStep;
use Illuminate\Database\Seeder;

class ProductRoutineSeeder extends Seeder
{
    public function run(): void
    {
        $routines = [
            [
                'routine_id' => 'routine-glucofix-classic',
                'product_id' => 1,
                'product_name' => 'Glucofix Herbal Capsule',
                'product_type' => 'Botanical supplement · Capsules',
                'product_image' => '/fixit.jpeg',
                'name' => 'Glucofix 30-Day Wellness Routine',
                'description' => 'A structured 30-day routine for optimal results with Glucofix Herbal Capsule.',
                'total_days' => 30,
                'created_by' => 'Admin',
                'steps' => [
                    ['day' => 1, 'title' => 'Getting Started', 'description' => 'Take 1 capsule with breakfast. Begin tracking your daily wellness.', 'instructions' => 'Take with food for better absorption.', 'tips' => 'Set a daily reminder for consistency.'],
                    ['day' => 7, 'title' => 'Week 1 Check-in', 'description' => 'Review how you feel after the first week. Note any changes.', 'instructions' => 'Continue daily dose.', 'tips' => 'Stay hydrated throughout the day.'],
                    ['day' => 14, 'title' => 'Week 2 Milestone', 'description' => 'Halfway point! Evaluate your progress and adjust if needed.', 'instructions' => 'Maintain consistent timing.', 'tips' => 'Pair with a balanced diet.'],
                    ['day' => 21, 'title' => 'Week 3 Review', 'description' => 'Three weeks in. Reflect on the routine benefits.', 'instructions' => 'Continue as directed.', 'tips' => 'Consider gentle exercise.'],
                    ['day' => 30, 'title' => 'Routine Complete', 'description' => '30 days complete! Assess overall results and plan next steps.', 'instructions' => 'Consult about continuing or adjusting.', 'tips' => 'Celebrate your consistency!'],
                ],
            ],
            [
                'routine_id' => 'routine-phytogold-tea',
                'product_id' => 3,
                'product_name' => 'PhytoGold Tea',
                'product_type' => 'Botanical infusion · 60 g',
                'product_image' => '/product%203.PNG',
                'name' => 'PhytoGold 14-Day Infusion Ritual',
                'description' => 'A gentle 14-day tea ritual for daily vitality and calm.',
                'total_days' => 14,
                'created_by' => 'Admin',
                'steps' => [
                    ['day' => 1, 'title' => 'First Brew', 'description' => 'Steep 1 tsp in hot water for 5 minutes. Enjoy mindfully.', 'instructions' => 'Water temperature: 95°C.', 'tips' => 'Create a calm morning moment.'],
                    ['day' => 3, 'title' => 'Finding Rhythm', 'description' => 'Establish your preferred brewing time and strength.', 'instructions' => 'Adjust steeping to taste.', 'tips' => 'Try morning or evening.'],
                    ['day' => 7, 'title' => 'Week 1 Reflection', 'description' => 'Notice how the ritual fits into your day.', 'instructions' => 'Continue daily cup.', 'tips' => 'Journal your experience.'],
                    ['day' => 14, 'title' => 'Ritual Established', 'description' => 'Two weeks complete. Your tea ritual is now a habit.', 'instructions' => 'Continue or adjust frequency.', 'tips' => 'Share with a friend.'],
                ],
            ],
        ];

        foreach ($routines as $routine) {
            $steps = $routine['steps'];
            unset($routine['steps']);

            $model = ProductRoutine::query()->updateOrCreate(
                ['routine_id' => $routine['routine_id']],
                $routine,
            );

            $model->steps()->delete();
            foreach ($steps as $step) {
                $model->steps()->create($step);
            }
        }
    }
}