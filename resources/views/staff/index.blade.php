<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, minimal-ui">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="theme-color" content="{{ $website['theme']['primaryColor'] }}">
  <link href="{{ url('css/staff.css?' . config('versions.cache_version_staff')) }}" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900|Material+Icons" rel="stylesheet">
  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" integrity="sha384-50oBUHEmvpQ+1lW4y57PTFmhCaXp0ML5d60M1M7uH2+nqUivzIebhndOJK28anvf" crossorigin="anonymous">
  <link rel="icon" type="image/png" href="{{ url('favicon-32x32.png') }}" sizes="32x32" />
  <link rel="icon" type="image/png" href="{{ url('favicon-16x16.png') }}" sizes="16x16" />
  <title>Staff Area - {{ $website['name'] }}<?php if ($website->content['campaignHeadline'] ?? null !== null) echo ' - ' . $website->content['campaignHeadline']; ?></title>
  <script>
  window.initCampaign = {!! json_encode($website) !!};
  </script>
  <style type="text/css">
    #nprogress .bar {background: {{ $website['theme']['textColor'] }} !important;}
    #app-loader{display:flex;align-items:center;justify-content:center;position:fixed;width:100%;height:100%;background-color:#fff;z-index:999999}.lds-ring{display:inline-block;position:relative;width:64px;height:64px}.lds-ring div{box-sizing:border-box;display:block;position:absolute;width:51px;height:51px;margin:6px;border:4px solid {{ $website['theme']['textColor'] }};border-radius:50%;animation:lds-ring 1.2s cubic-bezier(.5,0,.5,1) infinite;border-color:{{ $website['theme']['textColor'] }} transparent transparent transparent}.lds-ring div:nth-child(1){animation-delay:-.45s}.lds-ring div:nth-child(2){animation-delay:-.3s}.lds-ring div:nth-child(3){animation-delay:-.15s}@keyframes lds-ring{0%{transform:rotate(0)}100%{transform:rotate(360deg)}}
  </style>
</head>
<body>
  <div id="app-loader">
    <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
  </div>
  <div id="app">
    <app></app>
  </div>
  <script src="{{ url('js/manifest.js?' . config('versions.cache_version_vendor')) }}"></script>
  <script src="{{ url('js/vendor.js?' . config('versions.cache_version_vendor')) }}"></script>
  <script src="{{ url('js/staff.js?' . config('versions.cache_version_staff')) }}"></script>
</body>
</html>