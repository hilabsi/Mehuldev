<?php

namespace App\Modules\Invoice\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class InvoicePresenter extends ApisPresenter
{
  /**
   * Base representation of collection.
   *
   * @return array
   */
  public function present (): array
  {
    return $this -> collection -> map(function ($item) {
      return $this -> item($item);
    })-> toArray();
  }

  public function item ($item)
  {
    return [
      'id'        => $item -> id,
      'due_date'  => $item->due_date,
      'invoice_date' => $item->invoice_date,
      'code' => $item->code,
      'formatted_code' => settings('invoice_partner_prefix').str_pad($item->code, 6, '0', STR_PAD_LEFT),
      'partner_id' => $item->partner_id,
      'partner' => $item->company_name ?? $item->first_name.' '.$item->last_name,
      'total' => $item->total,
      'status' => $item->status,
      'apple' => $item->apple,
      'google' => $item->google,
      'cash' => $item->cash,
      'card' => $item->card,
    ];
  }
}
