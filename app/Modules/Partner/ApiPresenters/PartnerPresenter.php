<?php

namespace App\Modules\Partner\ApiPresenters;

use App\Support\Contracts\ApisPresenter;

class PartnerPresenter extends ApisPresenter
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
      'id'            => $item -> id,
      'percent'       => $item->percent,
      'email'         => $item -> email,
      'name'          => $item -> name,
      'company_name'  => $item->company_name,
      'first_name'    => $item->first_name,
      'last_name'     => $item->last_name,
      'country'       => [
        'id'    => $item->country->id,
        'name'  => $item->country->name,
      ],
      'city'          => [
        'id'    => $item->city->id,
        'name'  => $item->city->name,
      ],
      'phone'           => $item->phone,
      'language'        => $item->language,
      'billing_type'    => $item->billing_type,
      'address'         => $item->address,
      'fna'             => $item->fna,
      'uid'             => $item->uid,
      'account_owner'   => $item->account_owner,
      'iban'            => $item->iban,
      'bic'             => $item->bic,
      'status'          => $item->status,
      'documents'       => $item->documents->map(function ($document) {
        return [
          'document_id' => $document->document_id,
          'file'  => s3($document->file),
        ];
      }),
      'gisa'            => $item->getDocument('gisa'),
      'profile'         => $item->getDocument('profile'),
      'holder_id'       => $item->getDocument('holder_id'),
      'company_register'=> $item->getDocument('company_register'),
      'atm_front'       => $item->getDocument('atm_front'),
      'atm_back'        => $item->getDocument('atm_back'),
      'is_deleted'      => $item-> is_deleted,
      'created_at'      => $item -> created_at,
      'deleted_at'      => $item -> deleted_at,
    ];
  }

  public function profile ($item)
  {
    return [
      'id'      => $item -> id,
      'name'    => $item -> name,
      'email'   => $item -> email,
      'company_name' => $item->company_name,
    ];
  }
}
