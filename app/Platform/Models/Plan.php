<?php 

namespace Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Dyrynda\Database\Support\GeneratesUuid;

class Plan extends Model
{
    use GeneratesUuid;

    protected $table = 'plans';

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
      'user_count'
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
      'limitations' => 'json',
      'meta' => 'json'
    ];

    /**
     * Date/time fields that can be used with Carbon.
     *
     * @return array
     */
    public function getDates() {
      return [];
    }

    public static function boot() {
      parent::boot();

      // On update
      static::updating(function ($model) {
        if (auth()->check()) {
          $model->updated_by = auth()->user()->id;
        }
      });

      // On create
      self::creating(function ($model) {
        if (auth()->check()) {
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
      $product_id = null;

      if (config('general.payment_provider') == 'stripe') {
        $product_id = ['type' => 'text', 'column' => 'product_id_stripe', 'text' => __('Stripe subscription ID'), 'validate' => 'nullable|max:128', 'required' => false];
      }

      if (config('general.payment_provider') == 'paddle') {
        $product_id = ['type' => 'text', 'column' => 'product_id_paddle', 'text' => __('Paddle subscription ID'), 'validate' => 'nullable|max:128', 'required' => false];
      }

      if (config('general.payment_provider') == '2checkout') {
        $product_id = ['type' => 'text', 'column' => 'product_id_2checkout', 'text' => __('2Checkout subscription ID'), 'validate' => 'nullable|max:128', 'required' => false];
      }

      $owner = [
        'tab1' => [
          'text' => __('General'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'text', 'column' => 'name', 'text' => __('Name'), 'validate' => 'required|max:32', 'required' => true],
                ['type' => 'currency', 'prefix' => auth()->user()->getCurrencyFormat('symbol'), 'suffix' => auth()->user()->getCurrency(), 'column' => 'price', 'text' => __('Price'), 'validate' => 'required|decimal:' . auth()->user()->getCurrencyFormat('fraction_digits') . '|min:0|max:1000000', 'required' => true]
              ]
            ]
          ]
        ],
        'tab2' => [
          'text' => __('Limitations'),
          'subs' => [
            'sub1' => [
              'items' => [
                ['type' => 'number', 'column' => 'limitations->customers', 'text' => __('Customers'), 'validate' => 'required|integer|min:0|max:1000000', 'required' => true],
                ['type' => 'number', 'column' => 'limitations->campaigns', 'text' => __('Campaigns'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true],
                ['type' => 'number', 'column' => 'limitations->rewards', 'text' => __('Rewards'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true],
                ['type' => 'number', 'column' => 'limitations->businesses', 'text' => __('Businesses'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true],
                ['type' => 'number', 'column' => 'limitations->staff', 'text' => __('Staff'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true],
                ['type' => 'number', 'column' => 'limitations->segments', 'text' => __('Segments'), 'validate' => 'required|integer|min:0|max:10000', 'required' => true],
              ]
            ]
          ]
        ]
      ];

      if ($product_id !== null) array_push($owner['tab1']['subs']['sub1']['items'], $product_id);

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
        'items' => __('Plans'),
        'edit_item' => __('Edit plan'),
        'create_item' => __('Create plan'),
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
        ['visible' => true, 'value' => 'name', 'text' => __('Name'), 'align' => 'left', 'sortable' => false],
        ['visible' => true, 'value' => 'price', 'type' => 'currency', 'text' => __('Price'), 'align' => 'right', 'sortable' => true, 'default_order' => true],
        ['visible' => true, 'value' => 'limitations->customers', 'json' => 'limitations->customers', 'type' => 'number', 'text' => __('Customers'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'limitations->campaigns', 'json' => 'limitations->campaigns', 'type' => 'number', 'text' => __('Campaigns'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'limitations->rewards', 'json' => 'limitations->rewards', 'type' => 'number', 'text' => __('Rewards'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'limitations->businesses', 'json' => 'limitations->businesses', 'type' => 'number', 'text' => __('Businesses'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'limitations->staff', 'json' => 'limitations->staff', 'type' => 'number', 'text' => __('Staff'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'limitations->segments', 'json' => 'limitations->segments', 'type' => 'number', 'text' => __('Segments'), 'align' => 'right', 'sortable' => false],
        ['visible' => true, 'value' => 'user_count', 'type' => 'number', 'exclude_from_select' => true, 'text' => __('Users'), 'align' => 'right', 'sortable' => false]
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
     * Get user count for plan.
     *
     * @return string
     */
    public function getUserCountAttribute() {
      $users = $this->users()->get();
      return $users->count();
    }

    /**
     * Get plans for billing
     */
    public static function getPlansForBilling($role = 3) {
      $account = app()->make('account');
      $active_plan_id = (auth()->check()) ? auth()->user()->plan_id : null;

      $plans = Plan::where('role', $role)->where('active', 1)->orderBy('price', 'asc')->get();

      $plans = $plans->map(function ($record) use ($account, $active_plan_id) {
        $limitations = $record->limitations;
        $limitations['id'] = $record->id;
        $limitations['amount'] = $record->price;
        $limitations['price'] = $record->name;
        $limitations['currency'] = $account->getCurrency();
        $limitations['active'] = ($active_plan_id == $record->id) ? true : false;
        if (config('general.payment_provider') == 'paddle') {
          $limitations['remote_id'] = $record->product_id_paddle;
        } elseif (config('general.payment_provider') == '2checkout') {
          $limitations['remote_id'] = $record->product_id_2checkout;
        } elseif (config('general.payment_provider') == 'stripe') {
          $limitations['remote_id'] = $record->product_id_stripe;
        } else {
          $limitations['remote_id'] = $record->remote_product_id;
        }

        return $limitations;
      });

      return $plans->toArray();
    }

    /**
     * Get plans for site display
     *
     * @return array
     */
    public static function getPlansForSite($role = 3) {
      $plans = Plan::where('role', $role)->where('active', 1)->orderBy('price', 'asc')->get();

      $plans = $plans->map(function ($record) {
        $limitations = $record->limitations;
        $limitations['price'] = $record->name;

        return $limitations;
      });

      return $plans->toArray();
    }

    /**
     * Relationships
     * -------------
     */

    public function users() {
      return $this->hasMany(\App\User::class, 'plan_id', 'id');
    }
}