<?php

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;
use Platform\Controllers\Core;
use App\Scopes\AccountScope;
use Spatie\MediaLibrary\Models\Media;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Spatie\Image\Manipulations;
use Carbon\Carbon;

class Reward extends Model implements HasMedia
{
    use GeneratesUuid;
    use HasMediaTrait;

    protected $table = 'rewards';

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
      'title_with_points', 'main_image', 'main_image_thumb', 'image1', 'image2', 'image3', 'image4', 'image1_thumb', 'image2_thumb', 'image3_thumb', 'image4_thumb'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Field mutators.
     *
     * @var array
     */
    protected $casts = [
      'uuid' => 'uuid',
      'settings' => 'json',
      'tags' => 'json',
      'attributes' => 'json',
      'meta' => 'json'
    ];

    public function registerMediaCollections() {

      $this
        ->addMediaCollection('main_image')
        ->singleFile();

      $this
        ->addMediaCollection('image1')
        ->singleFile();

      $this
        ->addMediaCollection('image2')
        ->singleFile();

      $this
        ->addMediaCollection('image3')
        ->singleFile();

      $this
        ->addMediaCollection('image4')
        ->singleFile();
    }

    public function registerMediaConversions(Media $media = null) {
        $this
          ->addMediaConversion('thumb')
          ->width(360)
          ->height(240)
          ->performOnCollections('main_image');

        $this
          ->addMediaConversion('square')
          ->crop(Manipulations::CROP_CENTER, 256, 256)
          ->performOnCollections('image1', 'image2', 'image3', 'image4');
    }

    /**
     * Date/time fields that can be used with Carbon.
     *
     * @return array
     */
    public function getDates() {
      return ['active_from', 'expires_at', 'created_at', 'updated_at'];
    }

    public static function boot() {
      parent::boot();

      static::addGlobalScope(new AccountScope(auth()->user()));

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth()->user()->id;
        }
      });

      // On create
      self::creating(function ($model) {
        if (auth()->check()) {
          $model->account_id = auth()->user()->account_id;
          $model->created_by = auth()->user()->id;
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
          'text' => __('General'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'title', 'text' => __('Title'), 'validate' => 'required|max:64', 'required' => true],
                ['type' => 'number', 'column' => 'points_cost', 'text' => __('Points cost'), 'validate' => 'required|integer|min:1|max:1000000', 'required' => true],
                ['type' => 'currency', 'prefix' => auth()->user()->getCurrencyFormat('symbol'), 'suffix' => auth()->user()->getCurrency(), 'column' => 'reward_value', 'text' => __('Reward value'), 'validate' => 'required|decimal:' . auth()->user()->getCurrencyFormat('fraction_digits') . '|min:0|max:1000000', 'required' => true],
                ['type' => 'boolean', 'default' => true, 'column' => 'active', 'text' => __('Active'), 'validate' => 'nullable']
              ]
            ]
          ]
        ],
        'tab2' => [
          'text' => __('Date range'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'date_time', 'default' => Carbon::now()->format('Y-m-d H:00:00'), 'format' => 'LLLL', 'column' => 'active_from', 'text' => __('Active from'), 'validate' => 'required', 'required' => true, 'icon' => 'calendar_today'],
                ['type' => 'date_time', 'default' => Carbon::now()->addMonths(18)->format('Y-m-d H:00:00'), 'format' => 'LLLL', 'column' => 'expires_at', 'text' => __('Expires'), 'validate' => 'required', 'required' => true, 'icon' => 'calendar_today']
              ]
            ]
          ]
        ],
        'tab3' => [
          'text' => __('Description'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'wysiwyg', 'config' => ['toolbar' => ['bold', 'italic', '|', 'link']], 'column' => 'description', 'text' => __('Description'), 'validate' => 'nullable'],
              ]
            ]
          ]
        ],
        'tab4' => [
          'text' => __('Images'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'column' => 'main_image', 'text' => __('Main image'), 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'column' => 'image1', 'text' => __('Additional image') . ' 1', 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'column' => 'image2', 'text' => __('Additional image') . ' 2', 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'column' => 'image3', 'text' => __('Additional image') . ' 3', 'validate' => 'nullable', 'icon' => 'attach_file'],
                ['type' => 'image', 'image' => ['thumb_max_width' => '180px', 'thumb_max_height' => '120px'], 'column' => 'image4', 'text' => __('Additional image') . ' 4', 'validate' => 'nullable', 'icon' => 'attach_file'],
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
      return 'rewards';
    }

    /**
     * Columns used for filters
     *
     * @return array
     */
    public static function getTableFilters() {
      $owner = [
        ['column' => 'campaigns', 'text' => __('All campaigns'), 'icon' => 'filter_list', 'type' => 'relation', 'default' => null, 'relation' => ['type' => 'belongsToMany', 'permission' => 'personal', 'with' => 'campaigns', 'table' => 'campaigns', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc']]
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
      $owner = ['uuid'];
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
      $owner = ['select_all' => true, 'actions' => true, 'create' => true, 'actions_width' => '90px', 'dialog_height' => 380];
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
        'items' => __('Rewards'),
        'edit_item' => __('Edit reward'),
        'create_item' => __('Create reward'),
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
     * The headers for the data table, per role (value = column name)
     *
     * @return array
     */
    public static function getHeaders() {
      $owner = [
        ['visible' => true, 'value' => 'title', 'style' => ['min-width'=> '120px'], 'text' => __('Title'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'points_cost', 'type' => 'number', 'text' => __('Points cost'), 'align' => 'right', 'sortable' => true, 'default_order' => true],
        ['visible' => true, 'value' => 'active_from', 'type' => 'date_time', 'color_is_future' => '#b71c1c', 'format' => 'll', 'text' => __('Active from'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'expires_at', 'type' => 'date_time', 'color_is_past' => '#b71c1c', 'format' => 'll', 'text' => __('Expires'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'number_of_times_redeemed', 'type' => 'number', 'text' => __('Redemptions'), 'align' => 'right', 'sortable' => true],
        ['visible' => true, 'value' => 'active', 'text' => __('Active'), 'align' => 'center', 'sortable' => true, 'type' => 'boolean'],
        ['visible' => true, 'value' => 'main_image_thumb', 'type' => 'image', 'exclude_from_select' => true, 'max_width' => '140px', 'text' => __('Main image'), 'align' => 'left', 'sortable' => false]
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
      $owner = ['title', 'points_cost'];
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

    public function getTitleWithPointsAttribute() {
      return $this->title . ' (' . $this->points_cost . ')';
    }

    /**
     * Images
     * -------------
     */

    public function getMainImageAttribute() {
      return ($this->getFirstMediaUrl('main_image') !== '') ? $this->getFirstMediaUrl('main_image') : null;
    }

    public function getMainImageThumbAttribute() {
      return ($this->getFirstMediaUrl('main_image') !== '') ? $this->getMedia('main_image')[0]->getUrl('thumb') : null;
    }

    public function getImage1Attribute() {
      return ($this->getFirstMediaUrl('image1') !== '') ? $this->getFirstMediaUrl('image1') : null;
    }

    public function getImage1ThumbAttribute() {
      return ($this->getFirstMediaUrl('image1') !== '') ? $this->getMedia('image1')[0]->getUrl('square') : null;
    }

    public function getImage2Attribute() {
      return ($this->getFirstMediaUrl('image2') !== '') ? $this->getFirstMediaUrl('image2') : null;
    }

    public function getImage2ThumbAttribute() {
      return ($this->getFirstMediaUrl('image2') !== '') ? $this->getMedia('image2')[0]->getUrl('square') : null;
    }

    public function getImage3Attribute() {
      return ($this->getFirstMediaUrl('image3') !== '') ? $this->getFirstMediaUrl('image3') : null;
    }

    public function getImage3ThumbAttribute() {
      return ($this->getFirstMediaUrl('image3') !== '') ? $this->getMedia('image3')[0]->getUrl('square') : null;
    }

    public function getImage4Attribute() {
      return ($this->getFirstMediaUrl('image4') !== '') ? $this->getFirstMediaUrl('image4') : null;
    }

    public function getImage4ThumbAttribute() {
      return ($this->getFirstMediaUrl('image4') !== '') ? $this->getMedia('image4')[0]->getUrl('square') : null;
    }

    /**
     * Relationships
     * -------------
     */

    public function account() {
      return $this->belongsTo(\App\User::class, 'account_id', 'id');
    }

    public function users() {
      return $this->belongsToMany(\App\User::class, 'company_user', 'company_id', 'user_id');
    }

    public function campaigns() {
      return $this->belongsToMany(\Platform\Models\Campaign::class, 'campaign_reward', 'reward_id', 'campaign_id');
    }
}
