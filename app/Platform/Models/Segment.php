<?php 

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;
use App\Scopes\AccountScope;

class Segment extends Model
{
    use GeneratesUuid;

    protected $table = 'segments';

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
      'businesses_text'
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
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'name', 'text' => __('Name'), 'validate' => 'required|max:32', 'required' => true],
                ['type' => 'relation', 'relation' => ['type' => 'belongsToMany', 'permission' => 'personal', 'with' => 'businesses', 'remote_pk' => 'business_id', 'table' => 'businesses', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc'], 'text' => __('Business'), 'validate' => 'nullable'],
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
      return 'segments';
    }

    /**
     * Columns used for filters
     *
     * @return array
     */
    public static function getTableFilters() {
      $owner = [
        ['column' => 'businesses', 'text' => __('All businesses'), 'icon' => 'filter_list', 'type' => 'relation', 'default' => null, 'relation' => ['type' => 'belongsToMany', 'permission' => 'personal', 'with' => 'businesses', 'table' => 'businesses', 'pk' => 'id', 'val' => 'name', 'orderBy' => 'name', 'order' => 'asc']]
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
        'items' => __('Segments'),
        'edit_item' => __('Edit segment'),
        'create_item' => __('Create segment'),
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
        ['visible' => true, 'value' => 'name', 'text' => __('Segment'), 'align' => 'left', 'sortable' => true],
        ['visible' => true, 'value' => 'businesses_text', 'exclude_from_select' => true, 'relation' => ['type' => 'belongsToMany', 'with' => 'businesses', 'table' => 'businesses', 'val' => 'name'], 'text' => __('Business'), 'align' => 'left', 'sortable' => false]
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

      $reseller = $owner;
      $user = $owner;

      return [
        1 => $owner,
        2 => $reseller,
        3 => $user
      ];
    }

    /**
     * Get comma separated business names
     *
     * @return string
     */
    public function getBusinessesTextAttribute() {
      $businesses = '';
      foreach ($this->businesses as $i => $business) {
        if (isset($business->name)) {
          $businesses .= $business->name;
          if ($i < count($this->businesses) - 1) $businesses .= ', ';
        }
      }

      return $businesses;
    }

    /**
     * Relationships
     * -------------
     */

    public function businesses() {
      return $this->belongsToMany(\Platform\Models\Business::class, 'business_segment', 'segment_id', 'business_id');
    }

    public function history() {
      return $this->belongsTo(\Platform\Models\History::class, 'history_id', 'segment_id');
    }
}