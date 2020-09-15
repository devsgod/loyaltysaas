<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $language = 'en';
        $locale = 'en';

        $plans = [];
        $plans[2000] = ['customers' => 25, 'campaigns' => 1, 'rewards' => 5, 'businesses' => 1, 'staff' => 1, 'segments' => 5];
        $plans[3000] = ['customers' => 75, 'campaigns' => 2, 'rewards' => 15, 'businesses' => 2, 'staff' => 2, 'segments' => 15];
        $plans[4000] = ['customers' => 150, 'campaigns' => 3, 'rewards' => 25, 'businesses' => 3, 'staff' => 4, 'segments' => 25];
        $plans[5000] = ['customers' => 250, 'campaigns' => 4, 'rewards' => 40, 'businesses' => 4, 'staff' => 6, 'segments' => 40];
        $plans[6000] = ['customers' => 400, 'campaigns' => 6, 'rewards' => 60, 'businesses' => 6, 'staff' => 8, 'segments' => 60];
        $plans[7000] = ['customers' => 600, 'campaigns' => 8, 'rewards' => 80, 'businesses' => 8, 'staff' => 10, 'segments' => 80];
        $plans[8000] = ['customers' => 1000, 'campaigns' => 10, 'rewards' => 100, 'businesses' => 10, 'staff' => 12, 'segments' => 100];

        foreach ($plans as $price => $resellerPlan) {
            $plan = new \Platform\Models\Plan;
            $plan->name = '$' . ($price / 100) . '/mo';
            $plan->role = 3;
            $plan->price = $price;
            $plan->billing_interval = 'month';
            $plan->limitations = [
                'customers' => $resellerPlan['customers'],
                'campaigns' => $resellerPlan['campaigns'],
                'rewards' => $resellerPlan['rewards'],
                'businesses' => $resellerPlan['businesses'],
                'staff' => $resellerPlan['staff'],
                'segments' => $resellerPlan['segments']
            ];
            $plan->created_by = 1;
            $plan->save();
        }

        $user = new \App\User;

        $user->role = 1;
        $user->name = 'Admin';
        $user->email = 'admin@example.com';
        $user->password = bcrypt('welcome123');
        $user->language = $language;
        $user->locale = $locale;
        $user->timezone = config('general.app_timezone');
        $user->app_name = config('app.name');
        $user->app_contact = config('general.mail_contact');
        $user->app_headline = config('general.app_headline');
        $user->app_mail_name_from =  config('general.mail_name_from');
        $user->app_mail_address_from =  config('general.mail_address_from');
        $user->app_color = '#304FFE';
        $user->app_host = str_replace(['http://', 'https://'], '', config('general.app_url'));
        $user->save();

        Eloquent::unguard();

        $this->call('IndustriesSeeder');
 
        if (config('app.demo')) {
          $this->call('DemoContentSeeder');
        }

        Eloquent::reguard();
    }
}
