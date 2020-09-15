<?php

use Illuminate\Database\Seeder;
use Platform\Controllers\Core;

use Faker\Factory as Faker;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class DemoContentSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

        $campain_names = ['Restaurant', 'Clothing Store', 'Gadget Store', 'Travel Business'];
        $image_dirs = ['restaurant', 'clothing', 'gadgets', 'travel'];
        $background_colors = ['#EEEEEE', '#EEEEEE', '#EEEEEE', '#e1f5fe'];
        $primary_colors = ['#9b0000', '#eeeeee', '#D7D7D7', '#FBC02D'];
        $primary_text_colors = ['#eeeeee', '#222222', '#0026ca', '#333333'];
        $secondary_colors = ['#d50000', '#ff1744', '#304ffe', '#E64A19'];

        $language = 'en';
        $locale = 'en';

        $account_id = 1;
        $user_count = 12;
        $business_count = 2;
        $rewards_count = 40;
        $campaign_count = 4;
        $customers_per_campaign = 15;
        $reward_iterations = 4;
        $segment_ids = [];
        $campaign_ids = [];
        $customer_ids = [];
        $staff_ids = [];

        $user_created_by = 2;

        $faker = Faker::create();

        $men = 1;
        $women = 1;

        // Main demo account (user id = 2)
        $user = new \App\User;

        $user->account_id = $account_id;
        $user->role = 3;
        $user->plan_id = 4;
        $user->name = 'Demo User';
        $user->email = 'user@example.com';
        $user->password = bcrypt('welcome123');
        $user->language = $language;
        $user->locale = $locale;
        $user->timezone = config('general.app_timezone');
        $user->created_by = $account_id;
        $user->save();
      
        $first = 2;

        if ($user_count > 0) {
          foreach (range($first, $user_count - 1) as $index) {
            $gender = (mt_rand(0, 1) == 1) ? 'male' : 'female';
            if ($gender == 'male') {
              $firstName = $faker->firstNameMale;
              $avatar = base_path() . '/database/seeds/avatars/men/' . $men . '.jpg';
              $men++;
            } else {
              $firstName = $faker->firstNameFemale;
              $avatar = base_path() . '/database/seeds/avatars/women/' . $women . '.jpg';
              $women++;
            }
            $lastName = $faker->lastName;
            $email = Str::slug(substr($firstName, 0, 1)) . '.' . Str::slug($lastName, '_') . '@' . $faker->domainName;

            $active = true;

            $created_at = $faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now');
            $updated_at = $faker->dateTimeBetween($startDate = $created_at, $endDate = 'now');

            $user = new \App\User;

            $user->account_id = $account_id;
            $user->role = 3;
            $user->plan_id = 4;
            $user->name = $firstName . ' ' . $lastName;
            $user->email = $email;
            $user->password = bcrypt('welcome123');
            $user->language = $language;
            $user->locale = $locale;
            $user->timezone = 'Europe/Amsterdam';
            $user->created_at = $created_at;
            $user->created_by = $account_id;
            $user->updated_at = $updated_at;
            $user->updated_by = $account_id;

            $user->save();

            $user = \App\User::find($index + 1);

            // Attach avatar
            //$user->addMedia($avatar)->toMediaCollection('avatar');

            //$user->avatar = $avatar;
            //$user->save();
          }
        }

        // Businesses
        if ($business_count > 0) {
          foreach (range(1, $business_count) as $index) {
            $company = $faker->company;
            $companyDomain = ucfirst(Str::camel(Str::slug($company, '_'))) . '.com';
            $email_pre = ['info', 'info', 'info', 'contact', 'hi', 'hello'];
            $email_tld = ['com', 'com', 'com', 'org', 'biz', 'store', 'agency'];
            $email = $email_pre[mt_rand(0, count($email_pre) - 1)] . '@' . Str::slug($company, '-') . '.' . $email_tld[mt_rand(0, count($email_tld) - 1)];

            $created_at = $faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now');
            $updated_at = $faker->dateTimeBetween($startDate = $created_at, $endDate = 'now');

            $business = new \Platform\Models\Business;

            $business->account_id = $account_id;
            $business->name = $company;
            $business->email = $email;
            $business->industry_id = mt_rand(1, 10);
            $business->website = $faker->domainName;
            $business->phone = $faker->e164PhoneNumber;
            $business->street1 = $faker->streetAddress;
            $business->city = $faker->city;
            $business->state = $faker->state;
            $business->postal_code = $faker->postcode;

            $business->content = [
                'href1' => 'https://example.com',
                'text1' => $companyDomain/*,
                'href2' => 'https://example.com',
                'text2' => $companyDomain . '/some-interesting-page'*/
            ];

            $business->social = [
                'text' => 'Connect with us!',
                /*'medium' => 'https://example.com',*/
                'twitter' => 'https://example.com',
                /*'youtube' => 'https://example.com',*/
                'facebook' => 'https://example.com',
                'linkedin' => 'https://example.com',
                /*'telegram' => 'https://example.com',*/
                'whatsapp' => 'https://example.com',
                'instagram' => 'https://example.com'
            ];

            $business->created_at = $created_at;
            $business->created_by = $user_created_by;
            $business->updated_at = $updated_at;
            $business->updated_by = $user_created_by;

            $business->save();
          }
        }

        // Rewards
        $reward_ids[1] = [];
        $reward_ids[2] = [];
        $reward_ids[3] = [];
        $reward_ids[4] = [];
        if ($rewards_count > 0) {
          foreach (range(1, $rewards_count) as $index) {
            $created_at = $faker->dateTimeBetween($startDate = '-1 months', $endDate = 'now');
            $updated_at = $faker->dateTimeBetween($startDate = $created_at, $endDate = 'now');

            $active_from = $faker->dateTimeBetween($startDate = '-10 months', $endDate = 'now');
            $expires_at = $faker->dateTimeBetween($startDate = '+6 months', $endDate = '+24 months');

            $points = [50, 100, 150, 200, 500, 1000, 5000];
            $points_cost = $points[mt_rand(0, count($points) - 1)];
            $reward_value = $points_cost;

            $reward = new \Platform\Models\Reward;

            $reward->account_id = $account_id;
            $reward->title = $faker->catchPhrase;
            $reward->description = '<p>' . implode('</p><p>', $faker->paragraphs($nb = 2, $asText = false)) . '</p>';
            $reward->points_cost = $points_cost;
            $reward->reward_value = $reward_value;
            $reward->active_from = $active_from;
            $reward->expires_at = $expires_at;
            $reward->created_at = $created_at;
            $reward->created_by = $user_created_by;
            $reward->updated_at = $updated_at;
            $reward->updated_by = $user_created_by;

            $reward->save();

            if ($index <= 10) {
              $image_dir = (isset($image_dirs[0])) ? $image_dirs[0] : 'restaurant';
              $reward_ids[1][] = $index;
            } elseif ($index <= 20) {
              $image_dir = (isset($image_dirs[1])) ? $image_dirs[1] : 'restaurant';
              $reward_ids[2][] = $index;
            } elseif ($index <= 30) {
              $image_dir = (isset($image_dirs[2])) ? $image_dirs[2] : 'restaurant';
              $reward_ids[3][] = $index;
            } else {
              $image_dir = (isset($image_dirs[3])) ? $image_dirs[3] : 'restaurant';
              $reward_ids[4][] = $index;
            }

            // Images
            $image = database_path('seeds/visuals-' . $image_dir . '/rewards/' . mt_rand(1, 18) . '.jpg');
            $reward
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('main_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/rewards/' . mt_rand(1, 18) . '.jpg');
            $reward
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('image1', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/rewards/' . mt_rand(1, 18) . '.jpg');
            $reward
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('image2', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/rewards/' . mt_rand(1, 18) . '.jpg');
            $reward
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('image3', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/rewards/' . mt_rand(1, 18) . '.jpg');
            $reward
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('image4', 'media');
          }
        }

        // Campaigns
        $men = 1;
        $women = 1;

        if ($campaign_count > 0) {
          foreach (range(1, $campaign_count) as $index) {
            $created_at = $faker->dateTimeBetween($startDate = '-3 months', $endDate = '-2 months');
            $updated_at = $faker->dateTimeBetween($startDate = $created_at, $endDate = '-1 months');

            $image_dir = (isset($image_dirs[$index-1])) ? $image_dirs[$index-1] : 'restaurant';

            $points = [100, 150, 250];
            $signup_bonus_points = $points[mt_rand(0, count($points) - 1)];

            $campaign = new \Platform\Models\Campaign;

            $campaign->account_id = $account_id;
            $campaign->business_id = mt_rand(1, $business_count);
            $campaign->name = (isset($campain_names[$index-1])) ? $campain_names[$index-1] : $faker->catchPhrase;
            $campaign->signup_bonus_points = $signup_bonus_points;

            $campaign->content = [
              "homeBlock1Text" => "<p>Receive loyalty points for every dollar you spend with us.</p>",
              "homeBlock2Text" => "<p>Receive loyalty points for every dollar you spend with us.</p>",
              "homeBlock3Text" => "<p>Receive loyalty points for every dollar you spend with us.</p>",
              "earnHeaderTitle" => "Earn Points",
              "homeBlock1Title" => "Earn points buying stuff you love",
              "homeBlock2Title" => "Visit our award winning location",
              "homeBlock3Title" => "Every day of the week",
              "homeBlocksTitle" => "Earn loyalty points",
              "homeHeaderTitle" => "Welcome to our new loyalty program, sign up now and receive " . $signup_bonus_points . " bonus points!",
              "campaignHeadline" => null,
              "earnHeaderContent" => "<p>Receive points for every dollar you spend.</p>",
              "homeHeaderContent" => "<p>This new way of saving is our biggest and best savings program ever.</p>",
              "contactHeaderTitle" => "Contact Us",
              "rewardsHeaderTitle" => "Rewards",
              "contactHeaderContent" => "<p>Get in touch.</p>",
              "rewardsHeaderContent" => "<p>Earn points and choose from these rewards.</p>"
            ];

            $campaign->settings = [
              "claimQr" => 1,
              "redeemQr" => 1,
              "claimCode" => 1,
              "redeemCode" => 1,
              "theme_textColor" => "#333333",
              "claimMerchantCode" => 1,
              "redeemMerchantCode" => 1,
              "theme_primaryColor" => (isset($primary_colors[$index-1])) ? $primary_colors[$index-1] : "#111111",
              "claimCustomerNumber" => 1,
              "theme_headerOpacity" => 85,
              "redeemCustomerNumber" => 1,
              "theme_secondaryColor" => (isset($secondary_colors[$index-1])) ? $secondary_colors[$index-1] : "#0D47A1",
              "theme_backgroundColor" => (isset($background_colors[$index-1])) ? $background_colors[$index-1] : "#EEEEEE",
              "theme_drawer_textColor" => "#eeeeee",
              "theme_primaryTextColor" => (isset($primary_text_colors[$index-1])) ? $primary_text_colors[$index-1] : "#ffffff",
              "theme_secondaryTextColor" => "#ffffff",
              "theme_drawer_backgroundColor" => "#333333",
              "theme_drawer_highlightTextColor" => "#ffffff",
              "theme_drawer_highlightBackgroundColor" => "#222222"
            ];

            $campaign->created_at = $created_at;
            $campaign->created_by = $user_created_by;
            $campaign->updated_at = $updated_at;
            $campaign->updated_by = $user_created_by;

            $campaign->save();

            $campaign_ids[] = $campaign->id;

            // Images
            $image = database_path('seeds/visuals-' . $image_dir . '/home/' . $index . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('home_image', 'media');

            $content_images = range(1, 10);
            $site_images = Arr::random($content_images, 5);

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[0] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('home_item1_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[1] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('home_item2_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[2] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('home_item3_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[3] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('earn_header_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[4] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('rewards_header_image', 'media');

            $image = database_path('seeds/visuals-' . $image_dir . '/content/' . $content_images[5] . '.jpg');
            $campaign
              ->addMedia($image)
              ->preservingOriginal()
              ->sanitizingFileName(function($fileName) {
                return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
              })
              ->toMediaCollection('contact_header_image', 'media');

            // Add rewards
            $synced_rewards = Arr::random($reward_ids[$index], 6);
            $campaign->rewards()->sync($synced_rewards);

            // Add customers for campaign
            for ($i = 0; $i < mt_rand($customers_per_campaign - 3, $customers_per_campaign); $i++) {

              $created_at = $faker->dateTimeBetween($created_at, $endDate = '-2 days');
              $updated_at = $faker->dateTimeBetween($created_at, $endDate = 'now');

              $gender = (mt_rand(0, 1) == 1) ? 'male' : 'female';
              if ($gender == 'male') {
                $firstName = $faker->firstNameMale;
                $avatar = base_path() . '/database/seeds/avatars/men/' . $men . '.jpg';
                $men++;
              } else {
                $firstName = $faker->firstNameFemale;
                $avatar = base_path() . '/database/seeds/avatars/women/' . $women . '.jpg';
                $women++;
              }
              $lastName = $faker->lastName;

              if ($i == 0) {
                $email = 'customer@example.com';
              } else {
                $email = Str::slug(substr($firstName, 0, 1)) . '.' . Str::slug($lastName, '_') . '@' . $faker->domainName;
              }

              $customer = new \App\Customer;

              $customer->account_id = $account_id;
              $customer->role = 1;
              $customer->campaign_id = $campaign->id;
              $customer->name = $firstName . ' ' . $lastName;
              $customer->email = $email;
              $customer->password = bcrypt('welcome123');
              $customer->customer_number = Core\Secure::getRandom(9, '1234567890');
              $customer->created_at = $created_at;
              $customer->created_by = $user_created_by;
              $customer->updated_at = $updated_at;
              $customer->updated_by = $user_created_by;
              $customer->save();

              $customer_ids[] = $customer->id;

              $customer
                ->addMedia($avatar)
                ->preservingOriginal()
                ->sanitizingFileName(function($fileName) {
                  return strtolower(str_replace(['#', '/', '\\', ' '], '-', $fileName));
                })
                ->toMediaCollection('avatar', 'media');

              // Add points for signing up
              if ($campaign->signup_bonus_points > 0) {
                $history = new \Platform\Models\History;

                $history->account_id = $account_id;
                $history->customer_id = $customer->id;
                $history->campaign_id = $campaign->id;
                $history->created_by = $customer->created_by;
                $history->created_at = $customer->created_at;
                $history->event = 'Sign up bonus';
                $history->points = $campaign->signup_bonus_points;
                $history->save();
              }
            }
          }
        }

        for ($i = 1; $i < 3; $i++) {
          $staff = new \App\Staff;

          $staff->account_id = $account_id;
          $staff->role = 1;
          $staff->name = 'Employee ' . $i;
          $staff->email = 'staff' . $i . '@example.com';
          $staff->password = bcrypt('welcome123');
          $staff->created_by = $user_created_by;
          $staff->save();

          $staff_ids[] = $staff->id;

          $staff->businesses()->sync(range(1, $business_count));
        }

        // Segments
        $segments = ['Location A', 'Location B', 'Location C', 'Weekend', 'Day', 'Evening', 'Terrace', 'Summer', 'Autumn', 'Winter', 'Spring'];

        foreach ($segments as $segment_name) {
          $segment = new \Platform\Models\Segment;

          $segment->account_id = $account_id;
          $segment->name = $segment_name;
          $segment->created_by = $user_created_by;
          $segment->save();

          $segment_ids[] = $segment->id;

          $segment->businesses()->sync(range(1, $business_count));
        }

        // Add history
        $history_earn_events = ['Code entered by customer', 'Code entered by staff member', 'Credited with QR code'];
        $history_earn_points = [20, 50, 75, 100, 125, 150, 175, 200, 225, 250, 275, 300, 325, 350];
        $history_redeem_events = ['Redeemed by merchant', 'Redeemed with QR code'];

        for ($reward_count = 0; $reward_count < $reward_iterations; $reward_count++) {
          foreach ($customer_ids as $customer_id) {
            // Get customer
            $customer = \App\Customer::find($customer_id);
            $points = 0;

            // Add 3 to 10 earning of points
            for ($i = 0; $i < mt_rand(3, 10); $i++) {
              $created_at = $faker->dateTimeBetween($customer->created_at, $endDate = 'now');
              $customer_points = $history_earn_points[mt_rand(0, count($history_earn_points) - 1)];

              $staff = \App\Staff::find($staff_ids[mt_rand(0, count($staff_ids)-1)]);

              $history = new \Platform\Models\History;
              $history->account_id = $account_id;
              $history->customer_id = $customer_id;
              $history->staff_id = $staff->id;
              $history->staff_name = $staff->name;
              $history->staff_email = $staff->email;
              $history->campaign_id = $customer->campaign_id;
              $history->created_by = $customer->campaign->created_by;
              $history->created_at = $created_at;
              $history->event = $history_earn_events[mt_rand(0, count($history_earn_events) - 1)];
              $history->points = $customer_points;
              $history->save();

              // Add some random segments
              $history->segments()->sync(Arr::random($segment_ids, mt_rand(1, 5)));

              $customer_points += $points;
            }

            // Get last history date
            //$history = \Platform\Models\History::where('customer_id', $customer_id)->orderBy('created_at', 'desc')->first();
            $history = \Platform\Models\History::where('customer_id', $customer_id)->inRandomOrder()->first();

            // Add reward
            $reward = \Platform\Models\Reward::where('points_cost', '<', $customer_points)->inRandomOrder()->first();

            if ($reward !== null) {
              $created_at = $faker->dateTimeBetween($history->created_at, $endDate = 'now');
              //$created_at = $faker->dateTimeBetween($history->created_at, $endDate = '-' . (($reward_iterations - $reward_count) * 2) . ' days');
              $customer_points = $history_earn_points[mt_rand(0, count($history_earn_points) - 1)];

              $staff = \App\Staff::find($staff_ids[mt_rand(0, count($staff_ids)-1)]);

              $history = new \Platform\Models\History;
              $history->account_id = $account_id;
              $history->customer_id = $customer_id;
              $history->staff_id = $staff->id;
              $history->staff_name = $staff->name;
              $history->staff_email = $staff->email;
              $history->campaign_id = $customer->campaign_id;
              $history->created_by = $customer->campaign->created_by;
              $history->created_at = $created_at;
              $history->event = $history_redeem_events[mt_rand(0, count($history_redeem_events) - 1)];
              $history->points = -$reward->points_cost;
              $history->reward_id = $reward->id;
              $history->reward_title = $reward->title;

              $history->save();

              // Add some random segments
              $history->segments()->sync(Arr::random($segment_ids, mt_rand(1, 5)));

              // Increment reward redemptions
              $reward->increment('number_of_times_redeemed');
            }
          }
        }

        Eloquent::reguard();
    }
}
