<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;
use Platform\Controllers\Core;
use Platform\Controllers\App;
use App\Scopes\AccountScope;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Image\Manipulations;
use Carbon\Carbon;
use Illuminate\Support\Str;

class Campaign extends Model implements HasMedia
{
    use GeneratesUuid;
    use HasMediaTrait;

    protected $table = 'campaigns';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
    ];

    /**
     * Appended columns.
     *
     * @var array
     */
    protected $appends = [
      'home_image',
      'home_item1_image',
      'home_item2_image',
      'home_item3_image',
      'earn_header_image',
      'rewards_header_image',
      'contact_header_image',
      'business_text',
      'url',
      'test_url',
      'customer_count'
    ];

    public function registerMediaCollections() {

      $this
        ->addMediaCollection('home_image')
        ->singleFile();

      $this
        ->addMediaCollection('home_item1_image')
        ->singleFile();

      $this
        ->addMediaCollection('home_item2_image')
        ->singleFile();

      $this
        ->addMediaCollection('home_item3_image')
        ->singleFile();

      $this
        ->addMediaCollection('earn_header_image')
        ->singleFile();

      $this
        ->addMediaCollection('rewards_header_image')
        ->singleFile();

      $this
        ->addMediaCollection('contact_header_image')
        ->singleFile();
    }

    public function registerMediaConversions(Media $media = null) {
        $this
          ->addMediaConversion('full_header')
          ->width(1280)
          ->height(1024)
          ->performOnCollections('home_image');

        $this
          ->addMediaConversion('item')
          ->width(640)
          ->height(480)
          ->performOnCollections('home_item1_image', 'home_item2_image', 'home_item3_image');

        $this
          ->addMediaConversion('header')
          ->width(1920)
          ->height(1280)
          ->performOnCollections('earn_header_image', 'rewards_header_image', 'contact_header_image');
    }

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = ['account'];

    /**
     * Field mutators.
     *
     * @var array
     */
    protected $casts = [
      'uuid' => 'uuid',
      'content' => 'json',
      'settings' => 'json',
      'tags' => 'json',
      'attributes' => 'json',
      'meta' => 'json'
    ];

    /**
     * Date/time fields that can be used with Carbon.
     *
     * @return array
     */
    public function getDates() {
      return ['created_at', 'updated_at'];
    }

    public static function boot() {
      parent::boot();

      static::addGlobalScope(new AccountScope(auth()->user()));

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth()->user()->id;

          // Slug
          $model->slug = Str::slug($model->name, '-') . '-' . Core\Secure::staticHash($model->id);

          // Either old host or new host has a value
          if (($model->host !== null || $model->getOriginal('host') !== null) && ($model->host != $model->getOriginal('host'))) {

            if ($model->host === null) { // Delete old host, new host is empty
              App\ServerPilotController::deleteDomain($model->getOriginal('host'), $model->ssl_app_id);
              $model->ssl_app_id = null;

            } elseif ($model->getOriginal('host') === null) { // Old host was empty, new host has value
              // Set SP app id
              if ($model->ssl_app_id === null) {
                $model->ssl_app_id = config('general.serverpilot_app_id');
              }
              App\ServerPilotController::addDomain($model->host, $model->ssl_app_id);
            } else { // Both old and new host have a value
              // Remove $model->getOriginal('host') domain
              App\ServerPilotController::deleteDomain($model->getOriginal('host'), $model->ssl_app_id);

              // Update to latest ServerPilot App
              $model->ssl_app_id = config('general.serverpilot_app_id');

              // Add $model->host domain to ServerPilot
              App\ServerPilotController::addDomain($model->host, $model->ssl_app_id);
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
        // Slug
        $model->slug = Str::slug($model->name, '-') . '-' . Core\Secure::staticHash($model->id);

        if (auth()->check()) {
          if (config('general.serverpilot_app_id') !== null) {
            // Check if app id is set for this campaign
            if ($model->ssl_app_id === null) {
              $model->ssl_app_id = config('general.serverpilot_app_id');
            }
            // Add domain to serverpilot
            App\ServerPilotController::addDomain($model->host, $model->ssl_app_id);
          }
        }
        $model->save();
      });

      // Deleted
      self::deleted(function ($model) {
        if (auth()->check()) {
          // Delete domain from ServerPilot SSL
          App\ServerPilotController::deleteDomain($model->host, $model->ssl_app_id);
        }
      });
    }

    /**
     * Form for creating a new record, per role
     *
     * @return array
     */
    public static function getCreateForm() {
      $account = app()->make('account');

      $owner = [
        'tab1' => [
          'text' => __('General'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'relation', 'relation' => ['type' => 'hasOne', 'with' => 'business', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc'], 'column' => 'business_id', 'text' => __('Business'), 'validate' => 'required', 'required' => true],
                ['type' => 'text', 'column' => 'name', 'text' => __('Campaign name'), 'validate' => 'required|max:64', 'required' => true],
                ['type' => 'number', 'column' => 'signup_bonus_points', 'text' => __('Points customers receive for signing up'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true, '_hint' => 'Points customers receive for signing up'],
                ['type' => 'text', 'column' => 'host', 'text' => __('Custom domain'), 'validate' => 'nullable|url: {require_protocol: false }|max:128|not_in:domainconfigservice.com,www.domainconfigservice.com|unique:campaigns,host', 'hint' => __('Leave empty to use a test url, or enter a (sub) domain without "http://". For example "loyalty.mydomain.com". Add a CNAME record to this domain pointing to ') . '"' . config('general.cname_domain') . '".'],
                /*['type' => 'boolean', 'default' => true, 'column' => 'active', 'text' => __('Active'), 'validate' => 'nullable']*/
              ]
            ]
          ]
        ],
        'tab2' => [
          'text' => __('Rewards'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'relation', 'relation' => ['type' => 'belongsToMany', 'permission' => 'personal', 'with' => 'rewards', 'remote_pk' => 'reward_id', 'table' => 'rewards', 'pk' => 'id', 'val' => "CONCAT(title, ' (', points_cost ,')')", 'orderBy' => 'points_cost', 'order' => 'asc', 'where' => 'active = 1'], 'text' => __('Rewards'), 'validate' => 'required', 'required' => true]
              ]
            ]
          ]
        ],
        'tab3' => [
          'text' => __('Settings'),
          'subs' => [
            'sub1' => [
              'text' => __('Earn points'),
              'items' => [
                ['type' => 'description', 'text' => __('How can customers claim points? A QR code is convenient if staff members have a mobile device to scan the code. It is also possible to have a staff member generate a code that the customer can enter himself, or to have the customer hand over the phone so a staff member can enter a code. Finally, it is possible for the customer to give their customer number to which the points can be credited.')],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->claimQr', 'text' => __('Staff member scans QR code'), 'validate' => 'nullable'],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->claimCode', 'text' => __('Customer enters code generated by staff member'), 'validate' => 'nullable'],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->claimMerchantCode', 'text' => __('Staff member enters personal code on customer\'s device'), 'validate' => 'nullable'],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->claimCustomerNumber', 'text' => __('Customer gives customer number to staff member'), 'validate' => 'nullable']
              ]
            ],
            'sub2' => [
              'text' => __('Redeem rewards'),
              'description' => __('How can customers redeem rewards?'),
              'items' => [
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->redeemQr', 'text' => __('Staff member scans QR code'), 'validate' => 'nullable'],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->redeemMerchantCode', 'text' => __('Staff member enters personal code on customer\'s device'), 'validate' => 'nullable'],
                ['type' => 'boolean', 'default' => true, 'column' => 'settings->redeemCustomerNumber', 'text' => __('Customer gives customer number to staff member'), 'validate' => 'nullable']
              ]
            ]
          ]
        ],
        'tab4' => [
          'text' => __('Content'),
          'subs' => [
            'sub1' => [
              'text' => __('Top bar'),
              'items' => [
                ['type' => 'text', 'column' => 'content->campaignTitle', 'text' => __('Top title'), 'hint' => __('This title will show next to the logo (if there is a logo uploaded for the business) and will replace the campaign name.'), 'validate' => 'nullable|max:64'],
                ['type' => 'text', 'column' => 'content->campaignHeadline', 'text' => __('Headline'), 'validate' => 'nullable|max:64'],
              ]
            ],
            'sub2' => [
              'text' => __('Home'),
              'items' => [
                ['type' => 'text', 'column' => 'content->homeHeaderTitle', 'default' => 'Welcome to our new loyalty program', 'text' => __('Title'), 'validate' => 'required|max:128', 'required' => true],
                ['type' => 'wysiwyg', 'column' => 'content->homeHeaderContent', 'default' => '<p>This new way of saving is our biggest and best savings program ever.</p>', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Header content'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'home_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Header image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'description', 'text' => __('The campaign homepage may contain three (optional) columns with content. Each column has a title, text and image.')],
                ['type' => 'text', 'column' => 'content->homeBlocksTitle', 'text' => __('Columns title'), 'validate' => 'nullable|max:128'],
                ['type' => 'text', 'column' => 'content->homeBlock1Title', 'text' => __('Column one title'), 'validate' => 'nullable|max:128'],
                ['type' => 'wysiwyg', 'column' => 'content->homeBlock1Text', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Column one text'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'home_item1_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Column one image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'text', 'column' => 'content->homeBlock2Title', 'text' => __('Column two title'), 'validate' => 'nullable|max:128'],
                ['type' => 'wysiwyg', 'column' => 'content->homeBlock2Text', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Column two text'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'home_item2_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Column two image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'text', 'column' => 'content->homeBlock3Title', 'text' => __('Column three title'), 'validate' => 'nullable|max:128'],
                ['type' => 'wysiwyg', 'column' => 'content->homeBlock3Text', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Column three text'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'home_item3_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Column three image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
              ]
            ],
            'sub3' => [
              'text' => __('Earn'),
              'items' => [
                ['type' => 'text', 'column' => 'content->earnHeaderTitle', 'default' => 'Earn Points', 'text' => __('Title'), 'validate' => 'required|max:128', 'required' => true],
                ['type' => 'wysiwyg', 'column' => 'content->earnHeaderContent', 'default' => '<p>Get points for every dollar you spend.</p>', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Header content'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'earn_header_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Header image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
              ]
            ],
            'sub4' => [
              'text' => __('Redeem'),
              'items' => [
                ['type' => 'text', 'column' => 'content->rewardsHeaderTitle', 'default' => 'Rewards', 'text' => __('Title'), 'text' => __('Title'), 'validate' => 'required|max:128', 'required' => true],
                ['type' => 'wysiwyg', 'column' => 'content->rewardsHeaderContent', 'default' => '<p>Earn points and choose from these rewards.</p>', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Header content'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'rewards_header_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Header image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
              ]
            ],
            'sub5' => [
              'text' => __('Contact'),
              'items' => [
                ['type' => 'text', 'column' => 'content->contactHeaderTitle', 'default' => 'Contact Us', 'text' => __('Title'), 'validate' => 'required|max:128', 'required' => true],
                ['type' => 'wysiwyg', 'column' => 'content->contactHeaderContent', 'default' => '<p>Get in touch.</p>', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'text' => __('Header content'), 'validate' => 'nullable'],
                ['type' => 'image', 'column' => 'contact_header_image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'text' => __('Header image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
              ]
            ]
          ]
        ],
        'tab5' => [
          'text' => __('Colors'),
          'subs' => [
            'sub1' => [
              'text' => __('Background'),
              'description' => __('Website background and text.'),
              'items' => [
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_backgroundColor', 'default' => '#EEEEEE', 'text' => __('Background'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_textColor', 'default' => '#333333', 'text' => __('Text'), 'validate' => 'required', 'required' => true]

              ]
            ],
            'sub2' => [
              'text' => __('Primary'),
              'description' => __('Top navigation and footer.'),
              'items' => [
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_primaryColor', 'default' => '#111111', 'text' => __('Primary background'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_primaryTextColor', 'default' => '#ffffff', 'text' => __('Primary text'), 'validate' => 'required', 'required' => true]

              ]
            ],
            'sub3' => [
              'text' => __('Secondary'),
              'description' => __('Header background and footer social links. Opacity for image overlay.'),
              'items' => [
                ['type' => 'slider', 'min' => 0, 'max' => 100, 'step' => 1, 'column' => 'settings->theme_headerOpacity', 'default' => 85, 'text' => __('Header opacity'), 'validate' => 'required'],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_secondaryColor', 'default' => '#0D47A1', 'text' => __('Secondary background'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_secondaryTextColor', 'default' => '#ffffff', 'text' => __('Secondary text'), 'validate' => 'required', 'required' => true]

              ]
            ],
            'sub4' => [
              'text' => __('Mobile menu'),
              'description' => __('Side menu on mobile devices.'),
              'items' => [
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_drawer_backgroundColor', 'default' => '#333333', 'text' => __('Navigation background'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_drawer_textColor', 'default' => '#eeeeee', 'text' => __('Navigation text'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_drawer_highlightBackgroundColor', 'default' => '#222222', 'text' => __('Navigation active background'), 'validate' => 'required', 'required' => true],
                ['type' => 'color', 'mode' => 'hexa', 'column' => 'settings->theme_drawer_highlightTextColor', 'default' => '#ffffff', 'text' => __('Navigation active text'), 'validate' => 'required', 'required' => true],

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
     * Name used in plan limitations (optional)
     *
     * @return string
     */
    public static function getLimitationName() {
      return 'campaigns';
    }

    /**
     * Columns used for filters
     *
     * @return array
     */
    public static function getTableFilters() {
      $owner = [
        ['column' => 'business_id', 'text' => __('All businesses'), 'icon' => 'filter_list', 'type' => 'relation', 'default' => null, 'relation' => ['type' => 'hasOne', 'permission' => 'personal', 'with' => 'business', 'table' => 'businesses', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc']]
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
      $owner = ['uuid', 'business_id', 'host'];
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
      $owner = ['id', 'account_id', 'created_by', 'slug'];
      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Extra with-queries used in select queries
     *
     * @return array
     */
    public static function getExtraWithQueries() {
      $owner = [];
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
      $owner = ['select_all' => true, 'actions' => true, 'create' => true, 'actions_width' => '90px', 'dialog_width' => 640, 'dialog_height' => 340];
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
        'items' => __('Campaigns'),
        'edit_item' => __('Edit campaign'),
        'create_item' => __('Create campaign'),
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
      $owner = ['view' => 'all', 'delete' => 'all', 'update' => 'all', 'create' => true];
      $reseller = ['view' => 'account', 'delete' => 'account', 'update' => 'account', 'create' => false];
      $user = ['view' => 'personal', 'delete' => 'personal', 'update' => 'personal', 'create' => true];

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
        ['visible' => true, 'value' => 'business_text', 'exclude_from_select' => true, 'relation' => ['type' => 'hasOne', 'with' => 'business', 'table' => 'businesses', 'val' => 'name'], 'text' => __('Business'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'name', 'text' => __('Campaign'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'signup_bonus_points', 'type' => 'number', 'text' => __('Sign up bonus'), 'align' => 'right', 'sortable' => true],
        ['visible' => true, 'value' => 'customer_count', 'exclude_from_select' => true, 'type' => 'number', 'text' => __('Customers'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'url', 'exclude_from_select' => true, 'type' => 'campaign_link', 'text' => __('Visit campaign'), 'align' => 'left', 'sortable' => false]
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
      $owner = ['name'];
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
        /*['divider'],*/
        ['text' => __('Delete'), 'action' => 'delete', 'icon' => 'delete', 'color' => 'secondary', 'dark' => true]
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
     * Get business name
     *
     * @return string
     */
    public function getBusinessTextAttribute() {
      return ($this->business != null) ? $this->business->name : null;
    }

    /**
     * Get popular rewards for campaign
     *
     * @return array
     */
    public function getPopularRewards($limit = 4) {
      $rewards = $this->rewards()
        ->where('active_from', '<', \Carbon\Carbon::now()->toDateTimeString())
        ->where('expires_at', '>', \Carbon\Carbon::now()->toDateTimeString())
        ->where('active', 1)
        ->orderBy('number_of_times_redeemed', 'desc')
        ->orderBy('points_cost', 'desc')
        ->limit($limit)
        ->get();

      $rewards = $rewards->map(function ($record) {
        $record->img = $record->main_image_thumb ?? null;
        $record->points = $record->points_cost;
        $record->description = substr(strip_tags($record->description), 0, 60);
        return collect($record)->only('title', 'description', 'points', 'img');
      });

      return $rewards->toArray();
    }

    /**
     * Get all rewards for campaign
     *
     * @return array
     */
    public function getAllRewards($sortBy = 'points_cost', $sortDirection = 'desc') {
      $rewards = $this->rewards()
        ->where('active_from', '<', \Carbon\Carbon::now()->toDateTimeString())
        ->where('expires_at', '>', \Carbon\Carbon::now()->toDateTimeString())
        ->where('active', 1)
        ->orderBy($sortBy, $sortDirection)
        ->get();

      $rewards = $rewards->map(function ($record) {

        $images = [];
        if ($record->main_image !== null) $images[] = ['href' => $record->main_image, 'thumb' => $record->main_image_thumb];
        if ($record->image1 !== null) $images[] = ['href' => $record->image1, 'thumb' => $record->image1_thumb];
        if ($record->image2 !== null) $images[] = ['href' => $record->image2, 'thumb' => $record->image2_thumb];
        if ($record->image3 !== null) $images[] = ['href' => $record->image3, 'thumb' => $record->image3_thumb];
        if ($record->image4 !== null) $images[] = ['href' => $record->image4, 'thumb' => $record->image4_thumb];

        $record->images = $images;

        // In how many months does the reward expire?
        $expiresInMonths = $record->expires_at->diffInMonths(Carbon::now());
        $record->expiresInMonths = $expiresInMonths;

        $record->points = $record->points_cost;

        return collect($record)->only('uuid', 'title', 'description', 'points', 'expires_at', 'expiresInMonths', 'images');
      });

      return $rewards->toArray();
    }

    /**
     * Get active rewards for campaign
     *
     * @return string
     */
    public function getAciveRewards($sortBy = 'points_cost', $sortDirection = 'asc') {
      $rewards = $this->rewards()
        ->where('active_from', '<', \Carbon\Carbon::now()->toDateTimeString())
        ->where('expires_at', '>', \Carbon\Carbon::now()->toDateTimeString())
        ->where('active', 1)
        ->orderBy($sortBy, $sortDirection)
        ->get();

      return $rewards;
    }

    /**
     * Get campaign internal test url
     *
     * @return string
     */
    public function getTestUrlAttribute() {
      return ($this->account != null) ? '//' . $this->account->app_host . '/campaign/' . $this->slug : null;
    }

    /**
     * Get campaign url, returns test url if domain is not configured
     *
     * @return string
     */
    public function getUrlAttribute() {
      return ($this->host === null) ? $this->getTestUrlAttribute() : '//' . $this->host;
    }

    /**
     * Get the number of customers
     *
     * @return integer
     */
    public function getCustomerCountAttribute() {
      return $this->customers->count();
    }

    /**
     * Images
     * -------------
     */

    public function getHomeImageAttribute() {
      return ($this->getFirstMediaUrl('home_image') !== '') ? $this->getMedia('home_image')[0]->getUrl('full_header') : null;
      //return ($this->getFirstMediaUrl('home_image') !== '') ? $this->getFirstMediaUrl('home_image') : null;
    }

    public function getHomeImageThumbAttribute() {
      return ($this->getFirstMediaUrl('home_image') !== '') ? $this->getMedia('home_image')[0]->getUrl('thumb') : null;
    }

    public function getHomeItem1ImageAttribute() {
      return ($this->getFirstMediaUrl('home_item1_image') !== '') ? $this->getMedia('home_item1_image')[0]->getUrl('item') : null;
      //return ($this->getFirstMediaUrl('home_item1_image') !== '') ? $this->getFirstMediaUrl('home_item1_image') : null;
    }

    public function getHomeItem2ImageAttribute() {
      return ($this->getFirstMediaUrl('home_item2_image') !== '') ? $this->getMedia('home_item2_image')[0]->getUrl('item') : null;
      //return ($this->getFirstMediaUrl('home_item2_image') !== '') ? $this->getFirstMediaUrl('home_item2_image') : null;
    }

    public function getHomeItem3ImageAttribute() {
      return ($this->getFirstMediaUrl('home_item3_image') !== '') ? $this->getMedia('home_item3_image')[0]->getUrl('item') : null;
      //return ($this->getFirstMediaUrl('home_item3_image') !== '') ? $this->getFirstMediaUrl('home_item3_image') : null;
    }

    public function getEarnHeaderImageAttribute() {
      return ($this->getFirstMediaUrl('earn_header_image') !== '') ? $this->getMedia('earn_header_image')[0]->getUrl('header') : null;
      //return ($this->getFirstMediaUrl('earn_header_image') !== '') ? $this->getFirstMediaUrl('earn_header_image') : null;
    }

    public function getRewardsHeaderImageAttribute() {
      return ($this->getFirstMediaUrl('rewards_header_image') !== '') ? $this->getMedia('rewards_header_image')[0]->getUrl('header') : null;
      //return ($this->getFirstMediaUrl('rewards_header_image') !== '') ? $this->getFirstMediaUrl('rewards_header_image') : null;
    }

    public function getContactHeaderImageAttribute() {
      return ($this->getFirstMediaUrl('contact_header_image') !== '') ? $this->getMedia('contact_header_image')[0]->getUrl('header') : null;
      //return ($this->getFirstMediaUrl('contact_header_image') !== '') ? $this->getFirstMediaUrl('contact_header_image') : null;
    }

    /**
     * Get selected claim options
     *
     * @return array
     */
    public function getClaimOptions() {
      $options = [];

      if ((boolean) $this->settings['claimQr'] ?? false) $options[] = 'qr';
      if ((boolean) $this->settings['claimCode'] ?? false) $options[] = 'code';
      if ((boolean) $this->settings['claimMerchantCode'] ?? false) $options[] = 'merchant';
      if ((boolean) $this->settings['claimCustomerNumber'] ?? false) $options[] = 'customerNumber';

      return $options;
    }

    /**
     * Get selected redeem options
     *
     * @return array
     */
    public function getRedeemOptions() {
      $options = [];

      if ((boolean) $this->settings['redeemQr'] ?? false) $options[] = 'qr';
      if ((boolean) $this->settings['redeemMerchantCode'] ?? false) $options[] = 'merchant';
      if ((boolean) $this->settings['redeemCustomerNumber'] ?? false) $options[] = 'customerNumber';

      return $options;
    }

    /**
     * Get campaign homepage blocks
     *
     * @return array
     */
    public function getHomeBlocks() {
      $blocks = [];

      if (isset($this->content['homeBlock1Title']) || isset($this->content['homeBlock1Text']) || $this->home_item1_image !== null) {
        $blocks[] = [
          'img' => $this->home_item1_image,
          'title' => $this->content['homeBlock1Title'] ?? null,
          'text' => $this->content['homeBlock1Text'] ?? null,
        ];
      }

      if (isset($this->content['homeBlock2Title']) || isset($this->content['homeBlock2Text']) || $this->home_item2_image !== null) {
        $blocks[] = [
          'img' => $this->home_item2_image,
          'title' => $this->content['homeBlock2Title'] ?? null,
          'text' => $this->content['homeBlock2Text'] ?? null,
        ];
      }

      if (isset($this->content['homeBlock3Title']) || isset($this->content['homeBlock3Text']) || $this->home_item3_image !== null) {
        $blocks[] = [
          'img' => $this->home_item3_image,
          'title' => $this->content['homeBlock3Title'] ?? null,
          'text' => $this->content['homeBlock3Text'] ?? null,
        ];
      }

      return $blocks;
    }

    /**
     * Get array to generate campaign website
     *
     * @return array
     */
    public function getCampaignWebsite() {
      $root = $this->host;
      if ($root === null) $root = $this->account->app_host . '/campaign/' . $this->slug; 

      $headerOpacity = $this->settings['theme_headerOpacity'] ?? 85;

      $topTitle = null;
      if ($this->business->logo === null) $topTitle = $this->name;
      if ($this->content['campaignTitle'] ?? null !== null) $topTitle = $this->content['campaignTitle'];

      $website = [
        'uuid' => $this->uuid,
        'name' => $this->name,
        'title' => $topTitle,
        'headline' => $this->content['campaignHeadline'] ?? null,
        'slug' => $this->slug,
        'scheme' => request()->getScheme(),
        'host' => $this->host,
        'root' => $root,
        'theme' => [
          'logo' => $this->business->logo,
          'backgroundColor' => $this->settings['theme_backgroundColor'] ?? '#EEEEEE',
          'textColor' => $this->settings['theme_textColor'] ?? '#333333',
          'primaryColor' => $this->settings['theme_primaryColor'] ?? '#111111',
          'primaryTextColor' => $this->settings['theme_primaryTextColor'] ?? '#FFFFFF',
          'secondaryColor' => $this->settings['theme_secondaryColor'] ?? '#0D47A1',
          'secondaryTextColor' => $this->settings['theme_secondaryTextColor'] ?? '#FFFFFF',
          'drawer' => [
            'backgroundColor' => $this->settings['theme_drawer_backgroundColor'] ?? '#333333',
            'textColor' => $this->settings['theme_drawer_textColor'] ?? '#EEEEEE',
            'highlightBackgroundColor' => $this->settings['theme_drawer_highlightBackgroundColor'] ?? '#222222',
            'highlightTextColor' => $this->settings['theme_drawer_highlightTextColor'] ?? '#FFFFFF'
          ]
        ],
        'business' => [
          'name' => $this->business->name
        ],
        'externalUrls' => $this->business->getExternalLinks(),
        'footer' => [
          'text' => $this->business->social['text'] ?? null,
          'links' => $this->business->getSocialLinks()
        ],
        'home' => [
          'headerHeight' => 360,
          'headerImg' => $this->home_image,
          'headerTitle' => $this->content['homeHeaderTitle'] ?? null,
          'headerContent' => $this->content['homeHeaderContent'] ?? null,
          'rewardsTitle' => __('Popular rewards'),
          'rewardsImgRatio' => 1.75,
          'rewards' => $this->getPopularRewards(),
          'blocksTitle' => $this->content['homeBlocksTitle'] ?? null,
          'blocksImgRatio' => 1.77,
          'blocks' => $this->getHomeBlocks()
        ],
        'claimOptions' => $this->getClaimOptions(),
        'earn' => [
          'headerHeight' => 200,
          'headerOpacity' => $headerOpacity / 100,
          'headerImg' => $this->earn_header_image,
          'headerTitle' => $this->content['earnHeaderTitle'] ?? null,
          'headerContent' => $this->content['earnHeaderContent'] ?? null,
          'pageTitle' => __('Get loyalty points')
        ],
        'redeemOptions' => $this->getRedeemOptions(),
        'rewards' => [
          'headerHeight' => 200,
          'headerOpacity' => $headerOpacity / 100,
          'headerImg' => $this->rewards_header_image,
          'headerTitle' => $this->content['rewardsHeaderTitle'] ?? null,
          'headerContent' => $this->content['rewardsHeaderContent'] ?? null,
          'imageRatio' => 1.75,
          'list' => $this->getAllRewards()
        ],
        'contact' => [
          'headerHeight' => 200,
          'headerOpacity' => $headerOpacity / 100,
          'headerImg' => $this->contact_header_image,
          'headerTitle' => $this->content['contactHeaderTitle'] ?? null,
          'headerContent' => $this->content['contactHeaderContent'] ?? null,
          'methods' => $this->business->getContactMethods()/*,
          'features' => [
            [
              'icon' => 'local_activity',
              'title' => 'Takes Reservations',
              'value' => 'Yes'
            ],
          ]*/
        ]
      ];

      return $website;
    }

    /**
     * Relationships
     * -------------
     */

    public function account() {
      return $this->belongsTo(\App\User::class, 'account_id', 'id');
    }

    public function user() {
      return $this->belongsTo(\App\User::class, 'created_by', 'id');
    }

    public function customers() {
      return $this->hasMany(\App\Customer::class, 'campaign_id', 'id');
    }

    public function business() {
      return $this->hasOne(\Platform\Models\Business::class, 'id', 'business_id');
    }

    public function rewards() {
      return $this->belongsToMany(\Platform\Models\Reward::class, 'campaign_reward', 'campaign_id', 'reward_id');
    }
}