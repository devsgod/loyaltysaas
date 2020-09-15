<?php

use Illuminate\Database\Seeder;

class IndustriesSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        // Industries
        $industries = [
            'Accounting & Legal',
            'Advertising',
            'Aerospace',
            'Agriculture',
            'Automotive',
            'Banking & Finance',
            'Bars & Nightclubs',
            'Biotechnology',
            'Broadcasting',
            'Business Services',
            'Commodities & Chemicals',
            'Communications',
            'Computers & Hightech',
            'Construction',
            'Defense',
            'Energy',
            'Entertainment',
            'Government',
            'Healthcare & Life Sciences',
            'Insurance',
            'Internet & Online',
            'Manufacturing',
            'Marketing',
            'Media',
            'Nonprofit & Higher Education',
            'Other',
            'Pharmaceuticals',
            'Photography',
            'Professional Services & Consulting',
            'Real Estate',
            'Restaurant & Catering',
            'Retail & Wholesale',
            'Software & Development',
            'Sports',
            'Transportation',
            'Travel & Luxury'
        ];

        foreach ($industries as $industry) {
            $model = new \Platform\Models\Industry;
            $model->name = $industry;
            $model->save();
        }

        Eloquent::reguard();
    }
}
