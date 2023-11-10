<?php

namespace App\Modules\Trip\Enums;

class TripResponses
{
  public const NO_FIELDS_SENT = 411;

  public const TRIP_ALREADY_STARTED = 1300;
  public const CALL_NOT_AVAILABLE = 1302;
  public const TRIP_NOT_AVAILABLE = 1303;
  public const ALREADY_RATED = 1304;
  public const NO_TRIP_ASSIGNED = 1305;
  public const TRIP_NOT_STARTED = 1306;
  public const INVALID_STOP_ORDER = 1307;
  public const STOP_ALREADY_REACHED = 1308;
  public const TRIP_NOT_ENDED = 1309;
  public const ALREADY_SENT = 1310;
  public const ALREADY_ENDED = 1311;
  public const USE_TAXI_API = 1312;
}
