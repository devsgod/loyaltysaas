<?php
namespace Platform\Exports;

use App\Customer;

use Carbon\Carbon;

use PhpOffice\PhpSpreadsheet\Style;

use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomersExport implements ShouldAutoSize, FromCollection, WithColumnFormatting, WithHeadings, WithMapping
{
    use Exportable;

    private $columns = [
      'name',
      'email',
      'customer_number',
      'campaign_id',
      'active',
      'created_at',
      'logins',
      'last_login'
    ];

    public function headings(): array
    {
      return [
        'Name',
        'E-mail',
        'Customer number',
        'Points',
        'Campaign',
        'Active',
        'Created',
        'Logins',
        'Last login'
      ];
    }

    public function map($row): array
    {
      return [
        $row->name,
        $row->email,
        $row->number,
        $row->points,
        $row->campaign_text,
        //($row->active === 1) ? trans('g.yes') : trans('g.no'),
        $row->active,
        Carbon::parse($row->created_at, config('app.timezone'))->setTimezone(auth()->user()->getTimezone())->format('Y-m-d H:i'),
        ($row->logins === 0) ? '0' : $row->logins,
        ($row->last_login !== null) ? Carbon::parse($row->last_login, config('app.timezone'))->setTimezone(auth()->user()->getTimezone())->format('Y-m-d H:i') : null
      ];
    }

    public function columnFormats(): array
    {
        return [
          'C' => Style\NumberFormat::FORMAT_TEXT,
          'D' => Style\NumberFormat::FORMAT_NUMBER,
          'H' => Style\NumberFormat::FORMAT_NUMBER,
        ];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
/*
        $query = Customer::select($this->columns)->with('history');

        $rows = collect($query->get());

        $rows->map(function ($row) {
          $row['points'] = $row->points;
          return $row;
        });
*/
        return Customer::withoutGlobalScopes()->where('created_by', auth()->user()->id)->get();
    }
}