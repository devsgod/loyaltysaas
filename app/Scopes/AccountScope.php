<?php
namespace App\Scopes;

use Illuminate\Database\Eloquent\{Scope, Model, Builder};
use App\User;

class AccountScope implements Scope {

    /**
     * Create a new instance.
     *
     * @return void
     */
    public function __construct($user) {
        $this->user = $user;
    }

    public function apply(Builder $builder, Model $model) {
        if (! app()->runningInConsole()) {
          if (isset($this->user) && auth()->check()) {
            $builder->where($model->getTable() . '.account_id', '=', $this->user->account_id);
          } else {
            $account = app()->make('account');
            $builder->where($model->getTable() . '.account_id', '=', $account->id);
          }
        }
    }
}