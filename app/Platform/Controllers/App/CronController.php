<?php

namespace Platform\Controllers\App;

use App\User;
use App\Http\Controllers;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CommerceGuys\Intl\Formatter\NumberFormatter;
use CommerceGuys\Intl\Formatter\CurrencyFormatter;

class CronController extends \App\Http\Controllers\Controller
{
    /*
     |--------------------------------------------------------------------------
     | CRON Controller
     |--------------------------------------------------------------------------
     */

    /**
     * Cron actions for users
     *
     * @return null
     */
    public static function getProcessUsers() {

      /*
       |----------------------------------------------------------------------------------------------------------------------------------------------------
       | Users
       |----------------------------------------------------------------------------------------------------------------------------------------------------
       */

      // Send users who just signed up a welcome email
      $users = User::whereNotNull('expires')
        ->whereNull('plan_id')
        ->where('role', 3)
        ->where('active', 1)
        ->where('emails_sent', 0)
        ->get();

      if (!empty($users)) {
        foreach ($users as $user) {
          // Set user language
          app()->setLocale($user->getLanguage());

          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->name;
          $email->to_email = $user->email;
          $email->subject = trans('app.user_registration_subject', ['account_name' => $user->account->app_name]);
          $email->body_top = trans('app.user_registration_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name, 'trial_days' => config('system.trial_days')]);
          $email->cta_label = trans('app.user_registration_cta');
          $email->cta_url = '//' . $user->account->app_host . '/go#/login';
          $email->body_bottom = trans('app.user_registration_body_bottom', ['support_email' => $user->account->app_contact]);

          Mail::send(new \App\Mail\SendMail($email));

          $user->emails_sent = 1;
          $user->save();
        }
      }

      // Users with trials that expire in 3 days
      $users = User::whereNotNull('expires')
        ->whereNull('plan_id')
        ->where('role', 3)
        ->where('active', 1)
        ->where('emails_sent', 1)
        ->where('expires', '<', Carbon::now()->addDays(2))
        ->get();

      if (!empty($users)) {
        foreach ($users as $user) {
          // Set user language
          app()->setLocale($user->getLanguage());

          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->name;
          $email->to_email = $user->email;
          $email->subject = trans('app.user_trial_ends_in_3_days_subject', ['account_name' => $user->account->app_name]);
          $email->body_top = trans('app.user_trial_ends_in_3_days_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name, 'trial_days' => config('system.trial_days')]);
          $email->cta_label = trans('app.user_trial_ends_in_3_days_cta');
          $email->cta_url = '//' . $user->account->app_host . '/go#/billing';
          $email->body_bottom = trans('app.user_trial_ends_in_3_days_body_bottom', ['account_name' => $user->account->app_name]);

          Mail::send(new \App\Mail\SendMail($email));

          $user->emails_sent = 2;
          $user->save();
        }
      }

      // Users with trials that expire in 1 day
      $users = User::whereNotNull('expires')
        ->whereNull('plan_id')
        ->where('role', 3)
        ->where('active', 1)
        ->where('emails_sent', 2)
        ->where('expires', '<', Carbon::now()->addDays(1))
        ->get();

      if (!empty($users)) {
        foreach ($users as $user) {
          // Set user language
          app()->setLocale($user->getLanguage());

          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->name;
          $email->to_email = $user->email;
          $email->subject = trans('app.user_trial_ends_tomorrow_subject', ['account_name' => $user->account->app_name]);
          $email->body_top = trans('app.user_trial_ends_tomorrow_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name, 'trial_days' => config('system.trial_days')]);
          $email->cta_label = trans('app.user_trial_ends_tomorrow_cta');
          $email->cta_url = '//' . $user->account->app_host . '/go#/billing';
          $email->body_bottom = trans('app.user_trial_ends_tomorrow_body_bottom', ['account_name' => $user->account->app_name]);

          Mail::send(new \App\Mail\SendMail($email));

          $user->emails_sent = 3;
          $user->save();
        }
      }

      // Users with subscriptions that have expired
			/*if (config('general.payment_provider') == '') {
				$users = User::whereNotNull('expires')
					->whereNotNull('plan_id')
					->where('role', 3)
					->where('active', 1)
					->where('expires', '<', Carbon::now()->addDays(-1))
					->get();

				if (!empty($users)) {
					foreach ($users as $user) {
						// Set user language
						app()->setLocale($user->getLanguage());

						$email = new \stdClass;
						$email->app_name = $user->account->app_name;
						$email->app_url = '//' . $user->account->app_host;
						$email->from_name = $user->account->app_mail_name_from;
						$email->from_email = $user->account->app_mail_address_from;
						$email->to_name = $user->name;
						$email->to_email = $user->email;
						$email->subject = trans('app.user_subscription_expired_subject', ['account_name' => $user->account->app_name]);
						$email->body_top = trans('app.user_subscription_expired_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name, 'trial_days' => config('system.trial_days')]);
						$email->cta_label = trans('app.user_subscription_expired_cta');
						$email->cta_url = '//' . $user->account->app_host . '/go#/billing';
						$email->body_bottom = trans('app.user_subscription_expired_body_bottom', ['account_name' => $user->account->app_name]);

						Mail::send(new \App\Mail\SendMail($email));

						$user->emails_sent = 1;
						$user->save();
					}
				}
			}*/

      // Users who have expired
      $users = User::whereNotNull('expires')
        ->where('role', 3)
        ->where('active', 1)
        ->where('emails_sent', 3)
        ->where('expires', '<', Carbon::now()->addDays(-1))
        ->get();

      if (!empty($users)) {
        foreach ($users as $user) {
          // Set user language
          app()->setLocale($user->getLanguage());

					if ($user->plan_id !== null) {
						$user->plan_id = null;
						$user->emails_sent = 1;
						$user->save();
					} else {
						$email = new \stdClass;
						$email->app_name = $user->account->app_name;
						$email->app_url = '//' . $user->account->app_host;
						$email->from_name = $user->account->app_mail_name_from;
						$email->from_email = $user->account->app_mail_address_from;
						$email->to_name = $user->name;
						$email->to_email = $user->email;
						$email->subject = trans('app.user_account_expired_yesterday_subject');
						$email->body_top = trans('app.user_account_expired_yesterday_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name, 'grace_period_days' => config('system.grace_period_days')]);
						$email->cta_label = trans('app.user_account_expired_yesterday_cta');
						$email->cta_url = '//' . $user->account->app_host . '/go#/login';
						$email->body_bottom = trans('app.user_account_expired_yesterday_body_bottom', ['account_name' => $user->account->app_name]);

						Mail::send(new \App\Mail\SendMail($email));

						$user->emails_sent = 4;
						$user->save();
					}
        }
      }

      // Delete users who have expired the grace period
      $users = User::whereNotNull('expires')
        ->where('role', 3)
        ->where('active', 1)
        ->where('emails_sent', 4)
        ->where('expires', '<', Carbon::now()->addDays(-(config('system.grace_period_days') + 1)))
        ->get();

      if (!empty($users)) {
        foreach ($users as $user) {
          // Set user language
          app()->setLocale($user->getLanguage());

          $email = new \stdClass;
          $email->app_name = $user->account->app_name;
          $email->app_url = '//' . $user->account->app_host;
          $email->from_name = $user->account->app_mail_name_from;
          $email->from_email = $user->account->app_mail_address_from;
          $email->to_name = $user->name;
          $email->to_email = $user->email;
          $email->subject = trans('app.user_account_deleted_subject', ['account_name' => $user->account->app_name]);
          $email->body_top = trans('app.user_account_deleted_body_top', ['user_name' => $user->name, 'account_name' => $user->account->app_name]);
          $email->cta_label = trans('app.user_account_deleted_cta');
          $email->cta_url = '//' . $user->account->app_host . '/go#/login';
          $email->body_bottom = trans('app.user_account_deleted_body_bottom', ['account_name' => $user->account->app_name]);

          Mail::send(new \App\Mail\SendMail($email));

          $user->delete();
        }
      }
    }
}