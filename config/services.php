<?php

return [
  'facebook' => [
    'client_id' => env('FACEBOOK_CLIENT_ID'),
    'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
    'redirect' => env('FACEBOOK_REDIRECT_URI')
  ],
  'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT_URI')
  ],
  'apple' => [
    'client_id' => env('APPLE_CLIENT_ID'),
    'client_secret' => env('APPLE_CLIENT_SECRET'),
    'redirect' => env('APPLE_REDIRECT_URI')
  ],
  "sign_in_with_apple" => [
    "login" => env("SIGN_IN_WITH_APPLE_LOGIN"),
    "redirect" => env("SIGN_IN_WITH_APPLE_REDIRECT"),
    "client_id" => env("SIGN_IN_WITH_APPLE_CLIENT_ID"),
    "client_secret" => env("SIGN_IN_WITH_APPLE_CLIENT_SECRET"),
  ],
];
