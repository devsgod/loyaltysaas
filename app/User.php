<?php

namespace App;

use Illuminate\Database\Eloquent\Model as BaseModel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Dyrynda\Database\Support\GeneratesUuid;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Platform\Controllers\App;

use App\Scopes\AccountScope;

class User extends Authenticatable implements JWTSubject, HasMedia
{
    use GeneratesUuid;
    use Notifiable;
    use HasMediaTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'name', 'email', 'password',
    ];

    /**
     * Append programmatically added columns.
     *
     * @var array
     */
    protected $appends = [
      'account_active', 'avatar', 'plan_name', 'plan_limitations', 'expires_at', 'demo'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
      'uuid' => 'uuid',
      'email_verified_at' => 'datetime',
      'expires' => 'datetime',
      'social' => 'json',
      'settings' => 'json',
      'tags' => 'json',
      'attributes' => 'json',
      'meta' => 'json'
    ];

    public function registerMediaCollections() {
      $this
        ->addMediaCollection('avatar')
        ->singleFile();
    }

    public function registerMediaConversions(Media $media = null) {
        $this
          ->addMediaConversion('avatar')
          ->width(512)
          ->height(512)
          ->performOnCollections('avatar');
    }
    
    public function uuidColumn() {
      return 'uuid';
    }

    public function getJWTIdentifier() {
      return $this->getKey();
    }

    public function getJWTCustomClaims() {
      return [];
    }

    public static function boot() {
      parent::boot();

      if (auth()->check()) {
        static::addGlobalScope(new AccountScope(auth()->user()));
      }

      // On select
      static::retrieved(function ($model) {
      });

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth()->user()->id;

          // Either old host or new host has a value
          if (($model->app_host !== null || $model->getOriginal('app_host') !== null) && ($model->app_host != $model->getOriginal('app_host'))) {

            if ($model->app_host === null) { // Delete old host, new host is empty
              App\ServerPilotController::deleteDomain($model->getOriginal('app_host'), $model->ssl_app_id);
              $model->ssl_app_id = null;

            } elseif ($model->getOriginal('app_host') === null) { // Old host was empty, new host has value
              // Set SP app id
              if ($model->ssl_app_id === null) {
                $model->ssl_app_id = config('system.serverpilot_app_id');
              }
              App\ServerPilotController::addDomain($model->app_host, $model->ssl_app_id);
            } else { // Both old and new host have a value
              // Remove $model->getOriginal('app_host') domain
              App\ServerPilotController::deleteDomain($model->getOriginal('app_host'), $model->ssl_app_id);

              // Update to latest ServerPilot App
              $model->ssl_app_id = config('system.serverpilot_app_id');

              // Add $model->app_host domain to ServerPilot
              App\ServerPilotController::addDomain($model->app_host, $model->ssl_app_id);
            }
          }
        }
      });

      // On create
      self::creating(function ($model) {
        if (auth()->check()) {
          $model->account_id = auth()->user()->account_id;
          $model->created_by = auth()->user()->id;
        }
      });

      // Created
      self::created(function ($model) {
        if (auth()->check()) {
          if (config('system.serverpilot_app_id') !== null) {
            // Check if app id is set for this campaign
            if ($model->ssl_app_id === null) {
              $model->ssl_app_id = config('system.serverpilot_app_id');
            }
            // Add domain to serverpilot
            App\ServerPilotController::addDomain($model->app_host, $model->ssl_app_id);
          }
        }
        $model->save();
      });

      // Deleted
      self::deleted(function ($model) {
        if (auth()->check()) {
          // Delete domain from ServerPilot SSL
          App\ServerPilotController::deleteDomain($model->app_host, $model->ssl_app_id);
        }
      });
    }

    /**
     * Form for creating a new record, per role
     *
     * @return array
     */
    public static function getCreateForm() {
      $owner = [
        'tab1' => [
          'text' => __('Subscription'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'relation', 'relation' => ['type' => 'belongsTo', 'with' => 'plan', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc'], 'column' => 'plan_id', 'text' => __('Plan'), 'required' => false, 'validate' => 'nullable'],
                ['type' => 'date_time', 'default' => '', 'format' => 'LLLL', 'column' => 'expires', 'text' => __('Expiration date'), 'validate' => 'nullable', 'required' => false, 'icon' => 'calendar_today'],
              ]
            ]
          ]
        ],
        'tab2' => [
          'text' => __('Personal'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'name', 'text' => __('Name'), 'validate' => 'required|max:32', 'required' => true],
                ['type' => 'email', 'column' => 'email', 'text' => __('E-mail address'), 'validate' => 'nullable|email|max:64', 'icon' => 'email'],
                ['type' => 'image', 'image' => ['thumb_max_width' => '140px', 'thumb_max_height' => '140px'], 'column' => 'avatar', 'text' => __('Avatar'), 'validate' => 'nullable', 'icon' => 'attach_file'],
                /*['type' => 'boolean', 'default' => true, 'column' => 'active', 'text' => __('Active'), 'validate' => 'nullable']*/
              ]
            ]
          ]
        ]
      ];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Extra columns used in select queries, exposed in json response
     *
     * @return array
     */
    public static function getExtraSelectColumns() {
      $owner = ['uuid', 'plan_id'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Extra columns used in select queries, hidden from json response
     *
     * @return array
     */
    public static function getExtraQueryColumns() {
      $owner = ['id', 'created_by'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Generic settings
     *
     * actions: add actions column (true / false)
     *
     * @return array
     */
    public static function getSettings() {
      $owner = ['select_all' => true, 'actions' => true, 'create' => false, 'actions_width' => '105px'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Language variables
     *
     * @return array
     */
    public static function getTranslations() {
      return [
        'items' => __('Users'),
        'edit_item' => __('Edit user'),
        'create_item' => __('Create user'),
      ];
    }

    /**
     * Define per role if and what they can see
     *
     * all: all records from all accounts
     * account: all records from the current account
     * personal: only records the current user has created
     * created_by: only records created by the user id defined like created_by:1
     * none: this role has no permission
     *
     * @return array
     */
    public static function getPermissions() {
      $owner = ['view' => 'personal', 'delete' => 'personal', 'update' => 'personal', 'create' => true];
      $reseller = ['view' => 'personal', 'delete' => 'none', 'update' => 'personal', 'create' => false];
      $user = ['view' => 'personal', 'delete' => 'none', 'update' => 'personal', 'create' => false];

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * The headers for the data table, per role
     *
     * @return array
     */
    public static function getHeaders() {
      $owner = [
        ['visible' => true, 'value' => 'avatar', 'exclude_from_select' => true, 'width' => '60px', 'text' => __('Avatar'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'name', 'text' => __('Name'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'email', 'text' => __('E-mail'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'logins', 'type' => 'number', 'text' => __('Logins'), 'align' => 'right', 'sortable' => true],
        ['visible' => true, 'value' => 'last_login', 'type' => 'date_time', 'format' => 'ago', 'text' => __('Last login'), 'align' => 'left', 'sortable' => true, 'default_order' => true],
        ['visible' => true, 'value' => 'plan_name', 'exclude_from_select' => true, 'text' => __('Plan'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'expires', 'type' => 'date_time', 'format' => 'lll', 'text' => __('Expires'), 'align' => 'left', 'sortable' => true],
        /*['visible' => true, 'value' => 'active', 'text' => __('Active'), 'align' => 'center', 'sortable' => true, 'type' => 'boolean'],*/
      ];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * The columns used for searching the table
     *
     * @return array
     */
    public static function getSearchColumns() {
      $owner = ['name', 'email'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Available actions for data table row, per role
     *
     * @return array
     */
    public static function getActions() {
      $owner = [
        ['text' => __('Edit'), 'action' => 'edit', 'icon' => 'edit', 'color' => 'secondary', 'dark' => false],
        ['text' => __('Delete'), 'action' => 'delete', 'icon' => 'delete', 'color' => 'secondary', 'dark' => true],
        ['divider'],
        ['text' => __('Log in to account'), 'action' => 'log_in_as', 'icon' => 'vpn_key', 'color' => 'secondary', 'dark' => true],
      ];

      $reseller = [
        ['text' => __('Edit'), 'action' => 'edit', 'icon' => 'edit', 'color' => 'secondary', 'dark' => false]
      ];

      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Get plan.
     *
     * @return string
     */
    public function getPlanNameAttribute() {
      if ($this->plan_id !== null) {
        return $this->plan->name;
      } else {
        return __('Trial');
      }
    }

    /**
     * Get plan limiations.
     *
     * @return string
     */
    public function getPlanLimitationsAttribute() {
      if ($this->plan_id !== null) {
        return $this->plan->limitations;
      } else {
        return [
          'customers' => 3,
          'campaigns' => 1,
          'rewards' => 2,
          'businesses' => 1,
          'staff' => 1,
          'segments' => 2
        ];
      }
    }

    /**
     * Is (in) demo account.
     *
     * @return string
     */
    public function getDemoAttribute() {
      return env('APP_DEMO', false);
    }

    /**
     * Account is active.
     *
     * @return string
     */
    public function getAccountActiveAttribute() {
      if ($this->account !== null && $this->account->expires !== null) {
        return ! $this->account->expires->addDays(config('system.grace_period_days'))->isPast();
      } else {
        return true;
      }
    }

    /**
     * Expiration date in user timezone.
     *
     * @return date
     */
    public function getExpiresAtAttribute() {
      if ($this->expires !== null) {
        return $this->expires->timezone($this->getTimezone())->toDateTimeString();
      } else {
        return null;
      }
    }

    /**
     * Get avatar.
     *
     * @return string for use in <img> src
     */
    public function getAvatarAttribute() {
      if ($this->getFirstMediaUrl('avatar') !== '') {
        return $this->getFirstMediaUrl('avatar', 'avatar');
      } else {
        return (string) \Avatar::create(strtoupper($this->name))->toBase64();
      }
    }

    /**
     * Money formatting
     */
    public function formatMoney($amount, $currency = 'USD', $formatHtml = false) {
      if ($currency == null) $currency = 'USD';
      $value = Money::{$currency}($amount);
      $currencies = new \Money\Currencies\ISOCurrencies();

      $numberFormatter = new \NumberFormatter($this->getLanguage(), \NumberFormatter::CURRENCY);
      $moneyFormatter = new IntlMoneyFormatter($numberFormatter, $currencies);

      $amount = $moneyFormatter->format($value);
      if ($formatHtml) {
        $amount = explode($numberFormatter->getSymbol(0), $amount);
        $amount = $amount[0] . '<span class="cents">' . $numberFormatter->getSymbol(0) . $amount[1] . '</span>';
      }

      return $amount;
    }

    /**
     * Date / time formatting
     */
    public function formatDate($date, $format = 'date_medium') {
      if ($date !== null) {
        switch ($format) {
          case 'date_medium': $date = $date->timezone($this->getTimezone())->format('d-m-y'); break;
          case 'datetime_medium': $date = $date->timezone($this->getTimezone())->format('d-m-y H:i'); break;
          case 'friendly': $date = $date->timezone($this->getTimezone())->diffForHumans(); break;
        }
        return $date;
      } else {
        return null;
      }
    }

    /**
     * Check if user was online recently.
     *
     * @return boolean
     */
    public function getRecentlyOnline($minutes = 10) {
      $lastActivity = strtotime(\Carbon\Carbon::now()->subMinutes($minutes));
      $visit = \DB::table('sessions')
        ->whereRaw('user_id = ?', [$this->id])
        ->whereRaw('last_activity >= ?', [$lastActivity])
        ->first();

      return ($visit === null) ? false : true;
    }

    /**
     * User language
     */
    public function getLanguage() {
      if ($this->language === NULL) {
        return config('system.default_language');
      } else {
        return $this->language;
      }
    }

    /**
     * User locale
     */
    public function getLocale() {
      if ($this->locale === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.locale', config('system.default_locale'));
      } else {
        return $this->locale;
      }
    }

    /**
     * User timezone
     */
    public function getTimezone() {
      if ($this->timezone === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.timezone', config('system.default_timezone'));
      } else {
        return $this->timezone;
      }
    }

    /**
     * User currency
     */
    public function getCurrency() {
      if ($this->currency_code === NULL) {
        $language = $this->getLanguage();
        // If there is no default for user's language, use global default
        return config('system.language_defaults.' . $language . '.currency', config('system.default_currency'));
      } else {
        return $this->currency_code;
      }
    }

    /**
     * Currency decimal points
     */
    public function getCurrencyFormat($get = null) {
      $currencyRepository = new CurrencyRepository;
      $currency = $currencyRepository->get($this->getCurrency());

      $format = [
          'numeric_code' => $currency->getNumericCode(),
          'fraction_digits' => $currency->getFractionDigits(),
          'name' => $currency->getName(),
          'symbol' => $currency->getSymbol(),
          'locale' => $currency->getLocale()
      ];

      return ($get === null) ? $format : $format[$get];
    }

    /**
     * Admin totals
     */
    public function getAdminTotals() {
      // Totals
      $totalUsers = $this->users->count();

      return [
        'users' => $totalUsers
      ];
    }

    /**
     * Admin stats
     */
    public function getAdminStats($statsPeriod = '7days') {
      // Totals
      $totals = $this->getAdminTotals();

      // Period
      if ($statsPeriod == '7days') {
        $from = now()->addDays(-7);
        $to = now();
        $fromPrevious = now()->addDays(-15);
        $toPrevious = now()->addDays(-8);
      }

      // User signups for current period
      $period = new \DatePeriod( new \DateTime($from), new \DateInterval('P1D'), new \DateTime($to));

      $range = [];
      foreach($period as $date){
        $range[$date->format("Y-m-d")] = 0;
      }

      $data = $this->users()
        ->select([
          DB::raw('DATE(`created_at`) as `date`'),
          DB::raw('COUNT(id) as `count`')
        ])
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('date')
        ->get()
        /*
        ->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('d');
        })*/
        ->pluck('count', 'date');

      $dbData = [];
      $total = 0;
      if ($data !== null) {
        foreach($data as $date => $count) {
          $dbData[$date] = (int) $count;
          $total += $count;
        }
      }

      $userSignups = array_replace($range, $dbData);
      $userSignupsTotal = $total;

      // Customer signups for previous period
      $period = new \DatePeriod( new \DateTime($fromPrevious), new \DateInterval('P1D'), new \DateTime($toPrevious));
      $data = $this->users()
        ->select([
          DB::raw('COUNT(id) as `count`')
        ])
        ->whereBetween('created_at', [$fromPrevious, $toPrevious])
        ->get()
        ->pluck('count');

      $userSignupsTotalPrevious = (isset($data[0])) ? (int) $data[0] : 0;

      $stats = [
        'total' => $totals,
        'payment_provider' => config('general.payment_provider'),
        'payment_test_mode' => config('general.payment_test_mode'),
        'domain' => $this->app_host,
        'account_host' => config('general.cname_domain'),
        'app_name' => $this->app_name,
        'app_contact' => $this->app_contact,
        'app_mail_name_from' => $this->app_mail_name_from,
        'app_mail_address_from' => $this->app_mail_address_from,
        'users' => [
          'signupsCurrentPeriod' => $userSignups,
          'signupsCurrentPeriodTotal' => $userSignupsTotal,
          'signupsPreviousPeriodTotal' => $userSignupsTotalPrevious,
          'signupsChange' => $userSignupsTotal - $userSignupsTotalPrevious
        ]
      ];

      return $stats;
    }

    /**
     * User totals
     */
    public function getUserTotals() {
      // Totals
      $totalBusinesses = $this->businesses->count();
      $totalStaff = $this->staff->count();
      $totalRewards = $this->rewards->count();
      $totalCampaigns = $this->campaigns->count();
      $totalCustomers = $this->customers->count();
      $totalSegments = $this->segments->count();

      // Onboarding step
      $onboardingStep = 6;
      if ($totalCustomers == 0) $onboardingStep = 5;
      if ($totalCampaigns == 0) $onboardingStep = 4;
      if ($totalRewards == 0) $onboardingStep = 3;
      if ($totalStaff == 0) $onboardingStep = 2;
      if ($totalBusinesses == 0) $onboardingStep = 1;

      return [
        'onboardingStep' => $onboardingStep,
        'customers' => $totalCustomers,
        'campaigns' => $totalCampaigns,
        'rewards' => $totalRewards,
        'staff' => $totalStaff,
        'businesses' => $totalBusinesses,
        'segments' => $totalSegments,
      ];
    }

    /**
     * User stats
     */
    public function getUserStats($statsPeriod = '7days') {
      // Totals
      $totals = $this->getUserTotals();

      // Period
      if ($statsPeriod == '7days') {
        $from = now()->addDays(-7);
        $to = now();
        $fromPrevious = now()->addDays(-15);
        $toPrevious = now()->addDays(-8);
      }

      // Customer signups for current period
      $period = new \DatePeriod( new \DateTime($from), new \DateInterval('P1D'), new \DateTime($to));

      $range = [];
      foreach($period as $date){
        $range[$date->format("Y-m-d")] = 0;
      }

      $data = $this->customers()
        ->select([
          DB::raw('DATE(`created_at`) as `date`'),
          DB::raw('COUNT(id) as `count`')
        ])
        ->whereBetween('created_at', [$from, $to])
        ->groupBy('date')
        ->get()
        /*
        ->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('d');
        })*/
        ->pluck('count', 'date');

      $dbData = [];
      $total = 0;
      if ($data !== null) {
        foreach($data as $date => $count) {
          $dbData[$date] = (int) $count;
          $total += $count;
        }
      }

      $customerSignups = array_replace($range, $dbData);
      $customerSignupsTotal = $total;

      // Customer signups for previous period
      $period = new \DatePeriod( new \DateTime($fromPrevious), new \DateInterval('P1D'), new \DateTime($toPrevious));
      $data = $this->customers()
        ->select([
          DB::raw('COUNT(id) as `count`')
        ])
        ->whereBetween('created_at', [$fromPrevious, $toPrevious])
        ->get()
        ->pluck('count');

      $customerSignupsTotalPrevious = (isset($data[0])) ? (int) $data[0] : 0;

      // Earnings
      $earnings = $this->getEarningsForPeriod($statsPeriod);

      // Spendings
      $spendings = $this->getSpendingsForPeriod($statsPeriod);

      // Popular rewards
      $popularRewards = $this->getPopularRewards();

      $stats = [
        'total' => $totals,
        'customers' => [
          'signupsCurrentPeriod' => $customerSignups,
          'signupsCurrentPeriodTotal' => $customerSignupsTotal,
          'signupsPreviousPeriodTotal' => $customerSignupsTotalPrevious,
          'signupsChange' => $customerSignupsTotal - $customerSignupsTotalPrevious
        ],
        'popularRewards' => $popularRewards,
        'earnings' => $earnings,
        'spendings' => $spendings
      ];

      return $stats;
    }

    /**
     * Get popular rewards
     *
     * @return string
     */
    public function getPopularRewards($limit = 3) {
      $rewards = $this->rewards()->where('active_from', '<', \Carbon\Carbon::now()->toDateTimeString())
        ->where('expires_at', '>', \Carbon\Carbon::now()->toDateTimeString())
        ->where('active', 1)
        ->where('number_of_times_redeemed', '>', 0)
        ->orderBy('number_of_times_redeemed', 'desc')
        ->orderBy('points_cost', 'desc')
        ->limit($limit)
        ->get();

      $rewards = $rewards->map(function ($record) {
        return collect($record)->only('title', 'points', 'number_of_times_redeemed');
      });

      return $rewards->toArray();
    }

    /**
     * Get earning stats for period
     *
     * @return string
     */
    public function getEarningsForPeriod($statsPeriod = '7days') {
      // Period
      if ($statsPeriod == '7days') {
        $from = now()->addDays(-7);
        $to = now();
        $fromPrevious = now()->addDays(-15);
        $toPrevious = now()->addDays(-8);
      }

      // Customer signups for current period
      $period = new \DatePeriod( new \DateTime($from), new \DateInterval('P1D'), new \DateTime($to));

      $range = [];
      foreach($period as $date){
        $range[$date->format("Y-m-d")] = 0;
      }

      $data = $this->history()
        ->select([
          DB::raw('DATE(`created_at`) as `date`'),
          DB::raw('SUM(points) as `count`')
        ])
        ->whereBetween('created_at', [$from, $to])
        ->where('points', '>', 0)
        ->groupBy('date')
        ->get()
        /*
        ->groupBy(function ($val) {
            return Carbon::parse($val->created_at)->format('d');
        })*/
        ->pluck('count', 'date');

      $dbData = [];
      $total = 0;
      if ($data !== null) {
        foreach($data as $date => $count) {
          $dbData[$date] = (int) $count;
          $total += $count;
        }
      }

      $earnings = array_replace($range, $dbData);
      $earningsTotal = $total;

      // Customer signups for previous period
      $period = new \DatePeriod( new \DateTime($fromPrevious), new \DateInterval('P1D'), new \DateTime($toPrevious));

      $data = $this->history()
        ->select([
          DB::raw('SUM(points) as `count`')
        ])
        ->whereBetween('created_at', [$fromPrevious, $toPrevious])
        ->where('points', '>', 0)
        ->get()
        ->pluck('count');

      $earningsTotalPrevious = (isset($data[0])) ? (int) $data[0] : 0;

      return [
        'earnings' => $earnings,
        'earningsTotal' => $earningsTotal,
        'earningsTotalPrevious' => $earningsTotalPrevious,
        'earningsChange' => $earningsTotal - $earningsTotalPrevious
      ];
    }

    /**
     * Get spending stats for period
     *
     * @return string
     */
    public function getSpendingsForPeriod($statsPeriod = '7days') {
      // Period
      if ($statsPeriod == '7days') {
        $from = now()->addDays(-7);
        $to = now();
        $fromPrevious = now()->addDays(-15);
        $toPrevious = now()->addDays(-8);
      }

      // Customer signups for current period
      $period = new \DatePeriod( new \DateTime($from), new \DateInterval('P1D'), new \DateTime($to));

      $range = [];
      foreach($period as $date){
        $range[$date->format("Y-m-d")] = 0;
      }

      $data = $this->history()
        ->select([
          DB::raw('DATE(`created_at`) as `date`'),
          DB::raw('SUM(points) as `count`')
        ])
        ->whereBetween('created_at', [$from, $to])
        ->where('points', '<', 0)
        ->groupBy('date')
        ->get()
        ->pluck('count', 'date');

      $dbData = [];
      $total = 0;
      if ($data !== null) {
        foreach($data as $date => $count) {
          $dbData[$date] = abs($count);
          $total += abs($count);
        }
      }

      $spendings = array_replace($range, $dbData);
      $spendingsTotal = $total;

      // Customer signups for previous period
      $period = new \DatePeriod( new \DateTime($fromPrevious), new \DateInterval('P1D'), new \DateTime($toPrevious));
      $data = $this->history()
        ->select([
          DB::raw('SUM(ABS(points)) as `count`')
        ])
        ->whereBetween('created_at', [$fromPrevious, $toPrevious])
        ->where('points', '<', 0)
        ->get()
        ->pluck('count');

      $spendingsTotalPrevious = (isset($total[0])) ? (int) $total[0] : 0;

      return [
        'spendings' => $spendings,
        'spendingsTotal' => $spendingsTotal,
        'spendingsTotalPrevious' => $spendingsTotalPrevious,
        'spendingsChange' => $spendingsTotal - $spendingsTotalPrevious
      ];
    }

    /**
     * Relationships
     * -------------
     */

    public function account() {
      return $this->belongsTo(\App\User::class, 'account_id', 'id');
    }

    public function plan() {
      return $this->belongsTo(\Platform\Models\Plan::class, 'plan_id', 'id');
    }

    public function users() {
      return $this->hasMany(\App\User::class, 'created_by', 'id');
    }

    public function businesses() {
      return $this->hasMany(\Platform\Models\Business::class, 'created_by', 'id');
    }

    public function staff() {
      return $this->hasMany(\App\Staff::class, 'created_by', 'id');
    }

    public function rewards() {
      return $this->hasMany(\Platform\Models\Reward::class, 'created_by', 'id');
    }

    public function campaigns() {
      return $this->hasMany(\Platform\Models\Campaign::class, 'created_by', 'id');
    }

    public function customers() {
      return $this->hasMany(\App\Customer::class, 'created_by', 'id');
    }

    public function segments() {
      return $this->hasMany(\Platform\Models\Segment::class, 'created_by', 'id');
    }

    public function history() {
      return $this->hasMany(\Platform\Models\History::class, 'created_by', 'id');
    }
}