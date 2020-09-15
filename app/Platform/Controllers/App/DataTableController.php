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

use Money\Currency;
use Money\Money;

class DataTableController extends \App\Http\Controllers\Controller
{
    /*
    |--------------------------------------------------------------------------
    | DataTable Controller
    |--------------------------------------------------------------------------
    |
    | This controller is a blue print for the custom Vue DataTable component.
    |
    */

    /**
     * This function generates the json response required for building the table (headers)
     * and all records.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function getDataList(Request $request) {
      $model = $request->model;

      if (! class_exists($model)) {
        return response()->json(['status' => 'error', 'msg' => 'Class does not exist'], 422);
      }

      //$account = app()->make('account');
      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $filteredResults = false;
      $search = $request->search;
      $selectFilters = ($request->filters !== null) ? json_decode($request->filters) : null;
      if ($request->filters !== null || $request->search !== null) $filteredResults = true;
      $page = $request->page;
      $itemsPerPage = $request->itemsPerPage;
      if ($itemsPerPage == -1) $itemsPerPage = 1000;
      $descending = $request->descending;
      $sortBy = $request->sortBy;
      $settings = $model::getSettings()[auth()->user()->role];
      $permissions = $model::getPermissions()[auth()->user()->role];
      $actions = $model::getActions()[auth()->user()->role];
      $allHeaders = $model::getHeaders()[auth()->user()->role];
      $extraSelectColumns = method_exists($model, 'getExtraSelectColumns') ? $model::getExtraSelectColumns()[auth()->user()->role] : [];
      $extraQueryColumns = method_exists($model, 'getExtraQueryColumns') ? $model::getExtraQueryColumns()[auth()->user()->role] : [];
      $extraWithQueries = method_exists($model, 'getExtraWithQueries') ? $model::getExtraWithQueries()[auth()->user()->role] : [];
      $tableFilters = method_exists($model, 'getTableFilters') ? $model::getTableFilters()[auth()->user()->role] : [];
      $exportClass = method_exists($model, 'getExportClass') ? $model::getExportClass()[auth()->user()->role] : null;
      $searchColumns = $model::getSearchColumns()[auth()->user()->role];
      $translations = $model::getTranslations();

      // Derivates
      $start = ($page - 1) * $itemsPerPage;

      $select = Arr::flatten(Collect($allHeaders)->filter(function ($item) {
        return (! isset($item['exclude_from_select']) || $item['exclude_from_select'] === false);
      })->pluck('value')); // Get columns for select query

      $headers = Arr::where($allHeaders, function ($value, $key) { // Filter non-visible headers from structure
        return ($value['visible'] == true) ? $key = $value : null;
      });

      // Filter raw queries and extract columns to be parsed
      $new_headers = [];
      $date_times = [];
      $dates = [];
      $times = [];
      $numbers = [];
      $currencies = [];
      $locales = [];
      $jsons = [];

      $defaultOrderByName = 'id';

      foreach ($headers as $header) {
        // Check for default sort
        if (isset($header['default_order']) && $header['default_order']) {
          $defaultOrderByName = $header['value'];
        }

        if (isset($header['json'])) {
          $jsons[] = $header['json'];
        }

        if (isset($header['type']) && $header['type'] == "date_time") {
          $date_times[] = $header['value'];
        }

        if (isset($header['type']) && $header['type'] == "date") {
          $dates[] = $header['value'];
        }

        if (isset($header['type']) && $header['type'] == "time") {
          $times[] = $header['value'];
        }

        if (isset($header['type']) && $header['type'] == "number") {
          $numbers[] = $header['value'];
        }

        if (isset($header['type']) && $header['type'] == "currency") {
          $currencies[] = $header['value'];
        }

        if (strpos($header['value'], '|') !== false) {
          $select_raw = explode('|', $header['value']);
          $header['value'] = $select_raw[0];
        }
        $new_headers[] = $header;
      }
      $headers = $new_headers;

      $orderByName = ($sortBy === null || $sortBy == '') ? [$defaultOrderByName] : $sortBy;
      $direction =  ($descending == 'true' || $descending === null) ? 'desc' : 'asc'; 

      // Defaults
      $showExport = ($exportClass !== null) ? true : false;
      $records = [];
      $total = 0;

      // Wrap select columns in DB::raw
      $selectRaw = [];
      foreach (array_merge($select, $extraSelectColumns) as $select_column) {
        if (strpos($select_column, '|') === false) {
          $selectRaw[] = $select_column;
        } else {
          $select_raw = explode('|', $select_column);
          $selectRaw[] = DB::raw($select_raw[1]);
        }
      }

      // Relations
      $withRelations = [];
      foreach ($headers as $header) {
        if (isset($header['relation'])) {
          $withRelations = [
              $header['relation']['with'] => function ($query) use ($header) {
                $query->select($header['relation']['table'] . '.id', $header['relation']['table'] . '.' . $header['relation']['val']);
              }
          ];
        }
      }

      // Filters
      $filters = [];
      foreach ($tableFilters as $filter) {
        // Relations
        if ($filter['type'] == 'relation') {
          $relation = $filter['relation'];
          $permission = (isset($relation['permission'])) ? $relation['permission'] : 'personal';
          $where = (isset($relation['where'])) ? $relation['where'] : '';
          $items = [];

          if ($relation['type'] == 'hasOne' || $relation['type'] == 'belongsTo') {
            if ($permission == 'all') {
              $items = $model::with($relation['with'])->getRelation($relation['with'])->selectRaw($relation['pk'] . ' as pk, ' . $relation['val'] . ' as val')->where(function($query) use($where) { if ($where != '') return $query->whereRaw($where); })->orderBy($relation['orderBy'], $relation['order'])->get();
            } else {
              $items = $model::with($relation['with'])->getRelation($relation['with'])->selectRaw('' . $relation['pk'] . ' as pk, ' . $relation['val'] . ' as val')->where(function($query) use($where) { if ($where != '') return $query->whereRaw($where); })->where('created_by', auth()->user()->id)->orderBy($relation['orderBy'], $relation['order'])->get();
            }
          }

          if ($relation['type'] == 'belongsToMany') {
            if ($permission == 'all') {
              $items = DB::table($relation['table'])->selectRaw($relation['pk'] . ' as pk, ' . $relation['val'] . ' as val')->where(function($query) use($where) { if ($where != '') return $query->whereRaw($where); })->orderBy($relation['orderBy'], $relation['order'])->get();
            } else {
              $items = DB::table($relation['table'])->selectRaw('' . $relation['pk'] . ' as pk, ' . $relation['val'] . ' as val')->where(function($query) use($where) { if ($where != '') return $query->whereRaw($where); })->where('created_by', auth()->user()->id)->orderBy($relation['orderBy'], $relation['order'])->get();
            }
          }
        }

        $items = $items->map(function ($item) use ($relation) {
          return ['pk' => $item->pk, 'val' => $item->val];
        });

        $items = $items->toArray();

        // Check for selected filter, otherwise default
        $selected = $filter['default'];
        if ($selectFilters !== null) {
          foreach ($selectFilters as $column => $value) {
            if ($column == $filter['column']) $selected = $value;
          }
        }

        $filters[] = [
          'model' => $selected,
          'column' => $filter['column'],
          'placeholder' => $filter['text'],
          'icon' => isset($filter['icon']) ? $filter['icon'] : null,
          'items' => $items
        ];
      }

      // Query model
      // (new Model)->newQuery();
      $query = $model::select(array_merge($selectRaw, $extraQueryColumns))
        ->where(function ($query) use ($selectFilters) {
          if ($selectFilters !== null) {
            foreach ($selectFilters as $column => $values) {
              if (Str::endsWith($column, '_id')) {
                foreach ($values as $value) {
                  $query->orWhere($column, '=', $value);
                }
              }
            }
          }
        })
        ->with($extraWithQueries);

      // Filter belongs to many relations
      if ($selectFilters !== null) {
        foreach ($selectFilters as $column => $values) {
          if (! Str::endsWith($column, '_id')) {
            // Belongs To many
            $query->whereHas($column, function($query) use($column, $values) {
              $query->whereIn($column . '.id', $values);
            });
          }
        }
      };

      // Permission to view all records
      if ($permissions['view'] == 'all') {
        if($search !== null && strlen($search) > 1) {
          // Search query
          $search_query = $query->withoutGlobalScopes()->where(function ($query) use($search, $searchColumns) {
            foreach ($searchColumns as $searchColumn) {
              $query->orWhere($searchColumn, 'like', '%' . $search . '%');
            }
          });

          // Get total
          $total = clone $search_query;
          $total = $total->withoutGlobalScopes()->count();

          // Get paginated result
          $records = $search_query->withoutGlobalScopes()->with($withRelations);
        } else {
          // Get total
          $total = clone $query;
          $total = $total->withoutGlobalScopes()->count();

          // Get paginated result
          $records = $query->withoutGlobalScopes()->with($withRelations);
        }
      }

      // Only records from account can be viewed
      if ($permissions['view'] == 'account') {
        if($search !== null && strlen($search) > 1) {
          // Search query
          $search_query = $query->where(function ($query) use($search, $searchColumns) {
            foreach ($searchColumns as $searchColumn) {
              $query->orWhere($searchColumn, 'like', '%' . $search . '%');
            }
          });

          // Get total
          $total = clone $search_query;
          $total = $total->count();

          // Get paginated result
          $records = $search_query->with($withRelations);
        } else {
          // Get total
          $total = clone $query;
          $total = $total->count();

          // Get paginated result
          $records = $query->with($withRelations);
        }
      }

      // Only records created by user can be viewed
      if ($permissions['view'] == 'personal') {
        if($search !== null && strlen($search) > 1) {
          // Search query
          $search_query = $query->withoutGlobalScopes()->where('created_by', auth()->user()->id)->where(function ($query) use($search, $searchColumns) {
            foreach ($searchColumns as $searchColumn) {
              $query->orWhere($searchColumn, 'like', '%' . $search . '%');
            }
          });

          // Get total
          $total = clone $search_query;
          $total = $total->withoutGlobalScopes()->where('created_by', auth()->user()->id)->count();

          // Get paginated result
          $records = $search_query->withoutGlobalScopes()->where('created_by', auth()->user()->id)->with($withRelations);
        } else {
          // Get total
          $total = clone $query;
          $total = $total->withoutGlobalScopes()->where('created_by', auth()->user()->id)->count();

          // Get paginated result
          $records = $query->withoutGlobalScopes()->where('created_by', auth()->user()->id)->with($withRelations);
        }
      }

      // Only records created by user can be viewed
      if (Str::startsWith($permissions['view'], 'created_by:')) {
        $created_by = explode(':', $permissions['delete']);
        $created_by = explode(',', $created_by[1]);

        if($search !== null && strlen($search) > 1) {
          // Search query
          $search_query = $query->withoutGlobalScopes()->whereIn('created_by', $created_by)->where(function ($query) use ($search, $searchColumns) {
            foreach ($searchColumns as $searchColumn) {
              $query->orWhere($searchColumn, 'like', '%' . $search . '%');
            }
          });

          // Get total
          $total = clone $search_query;
          $total = $total->withoutGlobalScopes()->whereIn('created_by', $created_by)->count();

          // Get paginated result
          $records = $search_query->withoutGlobalScopes()->whereIn('created_by', $created_by);
        } else {
          // Get total
          $total = clone $query;
          $total = $total->withoutGlobalScopes()->whereIn('created_by', $created_by)->count();

          // Get paginated result
          $records = $query->withoutGlobalScopes()->whereIn('created_by', $created_by);
        }
      }

      // Order by
      if (is_array($orderByName)) {
        foreach ($orderByName as $orderBy) {
          $records = $records->orderBy($orderBy, $direction);
        }
      }

      $records = $records->take($itemsPerPage)->skip($start)->get();

      // Add action header
      if ($settings['actions']) {
        $headers[] = [
          'text' => __('Actions'),
          'align' => 'left',
          'sortable' => false,
          'value' => 'actions',
          'width' => '100px'
        ];
      }

      // Add action column
      /*
      $records->map(function ($item) {
        $item['actions'] = '1';
        return $item;
      });
      */

      // Remove unused columns
      //$records = collect($records)->map(function ($record) use ($extraQueryColumns) {
      //  return collect($record)->except($extraQueryColumns);
      //});

      $records = $records->map(function ($record) use ($extraQueryColumns) {
        return collect($record)->except($extraQueryColumns);
      });

      // Parse JSON
      $records = $records->map(function ($item) use ($jsons) {
        foreach ($jsons as $json) {
          $columns = explode('->', $json);
          $column = 'json_unquote(json_extract(`' . $columns[0] . '`, \'$."' . $columns[1] . '"\'))';
          $item[$json] = $item[$column];

          // Remove unparsed column
          unset($item[$column]);
        }

        return $item;
      });

      // Convert date time columns to correct timezone
      $records = $records->map(function ($item) use ($date_times, $dates, $times) {
        foreach ($date_times as $date_time) {
          if (isset($item[$date_time]) && $item[$date_time] != null) {
            $value = Carbon::parse($item[$date_time], config('app.timezone'))->setTimezone(auth()->user()->getTimezone());
            $item[$date_time] = $value->format('Y-m-d H:i:s');
          }
        }

        foreach ($dates as $date) {
          if (isset($item[$date]) && $item[$date] != null) {
            $value = Carbon::parse($item[$date], config('app.timezone'))->setTimezone(auth()->user()->getTimezone());
            $item[$date] = $value->format('Y-m-d');
          }
        }

        foreach ($times as $time) {
          if (isset($item[$time]) && $item[$time] != null) {
            $value = Carbon::parse($item[$time], config('app.timezone'))->setTimezone(auth()->user()->getTimezone());
            $item[$time] = $value->format('H:i:s');
          }
        }

        return $item;
      });

      // Localize numbers
      $numberFormatRepository = new NumberFormatRepository;
      $numberFormatter = new NumberFormatter($numberFormatRepository, ['locale' => auth()->user()->getLocale()]);

      $records = $records->map(function ($item) use ($numbers, $numberFormatter) {
        foreach ($numbers as $number) {
          if (isset($item[$number]) && $item[$number] != null) {
            $value = $numberFormatter->format($item[$number]);
            $item[$number] = $value;
          }
        }
        return $item;
      });

      // Localize currencies
      $currencyRepository = new CurrencyRepository;
      $currencyFormatter = new CurrencyFormatter($numberFormatRepository, $currencyRepository);
      $userCurrency = auth()->user()->getCurrency();

      $records = $records->map(function ($item) use ($currencies, $userCurrency, $currencyFormatter) {
        foreach ($currencies as $currency) {
          if (isset($item[$currency]) && $item[$currency] != null) {
            $value = $item[$currency] / 100;
            $item[$currency] = $currencyFormatter->format($value, $userCurrency);
          }
        }
        return $item;
      });

      // Parse localization to full text
      $records = $records->map(function ($item) use ($locales) {
        foreach ($locales as $locale) {
          if (isset($item[$locale]) && $item[$locale] != null) {

            $value = config('locales.' . $item[$locale]);
            $item[$locale] = $value;
          }
        }
        return $item;
      });

      // Transform translations
      $new_translations = [];
      foreach ($translations as $key => $translation) {
        $new_translations[$key] = $translation;
        $new_translations[$key . '_lowercase'] = strtolower($translation);
      }
      $translations = $new_translations;

      return response()->json(['status' => 'success', 'settings' => $settings, 'headers' => $headers, 'total' => $total, 'records' => $records, 'actions' => $actions, 'showExport' => $showExport, 'filters' => $filters, 'translations' => $translations, 'filteredResults' => $filteredResults], 200);
    }

    /**
     * Delete one or more records.
     *
     * @return \Symfony\Component\HttpFoundation\Response 
     */
    public function postDeleteRecords(Request $request) {
      $model = $request->model;

      if (! class_exists($model)) {
        return response()->json(['status' => 'error', 'msg' => 'Class does not exist'], 422);
      }

      if (env('APP_DEMO', false) === true && (auth()->user()->id == 1 || auth()->user()->id == 2)) {
        return response()->json(['status' => 'error', 'msg' => "This is a demo user. You can't update or delete anything in this account. If you want to test all user features, sign up with a new account."], 422);
      }

      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $uuids = $request->uuids;
      $permissions = $model::getPermissions()[auth()->user()->role];
      $all_records_success = true;

      $deleted = 0;

      if ($uuids !== null && is_array($uuids)) {
        foreach ($uuids as $uuid) {
          $query = false;
          // All records can be deleted
          if ($permissions['delete'] == 'all') {
            $query = $model::withoutGlobalScopes()->whereUuid($uuid)->first();
          }

          // Only records from account can be deleted
          if ($permissions['delete'] == 'account') {
            $query = $model::whereUuid($uuid)->first();
          }

          // Only records created by user can be deleted
          if ($permissions['delete'] == 'personal') {
            $query = $model::whereUuid($uuid)->where('created_by', auth()->user()->id)->first();
          }

          // Only records created by user can be deleted
          if (Str::startsWith($permissions['delete'], 'created_by:')) {
            $created_by = explode(':', $permissions['delete']);
            $created_by = explode(',', $created_by[1]);
            $query = $model::whereUuid($uuid)->whereIn('created_by', $created_by)->first();
          }

          if (! $query) {
            $all_records_success = false;
          } else {
            $deleted++;
            $query->delete();
          }
        }
      }

      if (! $all_records_success) {
        return response()->json([
          'status' => 'error',
          'msg' => 'One or more records couldn\'t be deleted.'
        ], 422);
      } else {
        return response()->json(['status' => 'success', 'deleted' => $deleted], 200);
      }
    }

    /**
     * Export records.
     *
     * @return \Illuminate\Contracts\Routing\ResponseFactory 
     */
    public function getExport(Request $request) {

      $model = $request->model;

      if (! class_exists($model)) {
        return response()->json(['status' => 'error', 'msg' => 'Class does not exist'], 422);
      }

      //$account = app()->make('account');
      $locale = request('locale', config('system.default_language'));
      app()->setLocale($locale);

      $exportClass = method_exists($model, 'getExportClass') ? $model::getExportClass()[auth()->user()->role] : null;
      $translations = $model::getTranslations();

      if ($exportClass !== null) {
        // File extension
        $ext = request('ext', 'xlsx');

        // Filename
        /*
        $filename = Str::slug(str_replace([':','/',' '], '-', env('APP_NAME') . '-' . $translations['items'] . '-' . auth()->user()->formatDate(Carbon::now(), 'datetime_medium')));

        switch ($ext) {
          case 'xlsx'; return (new $exportClass)->download($filename . '.' . $ext, \Maatwebsite\Excel\Excel::XLSX); break;
          case 'xls'; return (new $exportClass)->download($filename . '.' . $ext, \Maatwebsite\Excel\Excel::XLS); break;
          case 'csv'; return (new $exportClass)->download($filename . '.' . $ext, \Maatwebsite\Excel\Excel::CSV); break;
          case 'html'; return (new $exportClass)->download($filename . '.' . $ext, \Maatwebsite\Excel\Excel::HTML); break;
        }
        */

        switch ($ext) {
          case 'xlsx'; $blob = \Maatwebsite\Excel\Facades\Excel::raw(new $exportClass, \Maatwebsite\Excel\Excel::XLSX); break;
          case 'xls'; $blob = \Maatwebsite\Excel\Facades\Excel::raw(new $exportClass, \Maatwebsite\Excel\Excel::XLS); break;
          case 'csv'; $blob = \Maatwebsite\Excel\Facades\Excel::raw(new $exportClass, \Maatwebsite\Excel\Excel::CSV); break;
          case 'html'; $blob = \Maatwebsite\Excel\Facades\Excel::raw(new $exportClass, \Maatwebsite\Excel\Excel::HTML); break;
        }

        return $blob;
        //return response()->json(['status' => 'success', 'filename' => $filename . '.' . $ext, 'blob' => mb_convert_encoding($blob, 'UTF-8', 'UTF-8')], 200);
      }
    }
}