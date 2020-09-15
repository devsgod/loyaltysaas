<?php 

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;
use App\Scopes\AccountScope;

class History extends Model
{
    use GeneratesUuid;

    protected $table = 'history';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
      'name'
    ];

    /**
     * Append programmatically added columns.
     *
     * @var array
     */
    protected $appends = [
      'icon',
      'icon_size',
      'color',
      'description'
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
      'uuid' => 'uuid'
    ];

    public static function boot() {
      parent::boot();

      static::addGlobalScope(new AccountScope(auth('api')->user()));

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth('api')->user()->id;
        }
      });

      // On create
      self::creating(function ($model) {
        if (auth()->check()) {
          $model->account_id = auth()->user()->account_id;
          //$model->created_by = auth()->user()->id;
        } elseif ($model->account_id === null) {
          $account = app()->make('account');
          $model->account_id = $account->id;
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
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'event', 'text' => __('Event'), 'validate' => 'required|max:250', 'required' => true],
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
      $owner = ['select_all' => true, 'actions' => true, 'create' => true, 'actions_width' => '90px'];
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
        'items' => __('History'),
        'edit_item' => __('Edit item'),
        'create_item' => __('Create item'),
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
      $reseller = $owner;
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
        ['visible' => true, 'value' => 'event', 'text' => __('Event'), 'align' => 'left', 'sortable' => true]
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
      $owner = ['event'];
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

      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Translate event
     *
     * @return string
     */
    public function getDescriptionAttribute() {
      return __($this->event);
    }

    /**
     * Translate event to icon
     *
     * @return string
     */
    public function getIconAttribute() {
      $icon = 'add';

      // Negative points (reward redemption)
      //if ($this->points < 0) $icon = 'remove';
      if ($this->points < 0) $icon = 'fas fa-gift';

      switch ($this->event) {
        case 'Sign up bonus': $icon = 'sentiment_satisfied_alt'; break;
      }
      return $icon;
    }

    /**
     * Translate event to icon size
     *
     * @return string
     */
    public function getIconSizeAttribute() {
      $size = 'small';

      // Negative points (reward redemption)
      if ($this->points < 0) $size = 'large';

      switch ($this->event) {
        case 'Sign up bonus': $size = 'large'; break;
      }
      return $size;
    }

    /**
     * Translate event to color
     *
     * @return string
     */
    public function getColorAttribute() {
      $color = 'green darken-2';

      // Negative points (reward redemption)
      //if ($this->points < 0) $color = 'red darken-2';
      if ($this->points < 0) $color = 'blue darken-2';

      switch ($this->event) {
        case 'Sign up bonus': $color = 'orange darken-2'; break;
      }
      return $color;
    }

    /**
     * Relationships
     * -------------
     */

    public function customer() {
      return $this->hasOne(\App\Customer::class, 'id', 'customer_id');
    }

    public function campaign() {
      return $this->hasOne(\Platform\Models\Campaign::class, 'id', 'campaign_id');
    }

    public function staff() {
      return $this->hasOne(\App\Staff::class, 'id', 'staff_id');
    }

    public function segments() {
      return $this->belongsToMany(\Platform\Models\Segment::class, 'history_segment', 'history_id', 'segment_id');
    }
}