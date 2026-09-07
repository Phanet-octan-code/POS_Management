<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds for the 8 standard expense categories.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Rent',
                'slug' => 'rent',
                'description' => 'Store, retail space, office, or warehouse rental fees.',
            ],
            [
                'name' => 'Electricity',
                'slug' => 'electricity',
                'description' => 'Electric power bills and utility power consumption.',
            ],
            [
                'name' => 'Internet',
                'slug' => 'internet',
                'description' => 'High-speed broadband, phone lines, and telecommunications.',
            ],
            [
                'name' => 'Salary',
                'slug' => 'salary',
                'description' => 'Staff payroll, wages, overtime, and cashier commissions.',
            ],
            [
                'name' => 'Transportation',
                'slug' => 'transportation',
                'description' => 'Delivery expenses, freight, vehicle fuel, and logistics.',
            ],
            [
                'name' => 'Maintenance',
                'slug' => 'maintenance',
                'description' => 'Hardware repairs, cleaning, POS maintenance, and facilities upkeep.',
            ],
            [
                'name' => 'Marketing',
                'slug' => 'marketing',
                'description' => 'Advertising, social media marketing campaigns, and promotional printing.',
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'Sundry operating expenses and miscellaneous overhead.',
            ],
        ];

        foreach ($categories as $cat) {
            ExpenseCategory::updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
