@component('mail::layout')

  @slot('header')
    @component('mail::header', ['url' => $app_url])
      {{ $app_name }}
    @endcomponent
  @endslot

{{ $body_top }}

<?php if ($cta_url !== null) { ?>

@component('mail::button', ['url' => $cta_url, 'color' => $cta_color])
  {{ $cta_label }}
@endcomponent

<?php } ?>

{{ $body_bottom }}

  @slot('footer')
    @component('mail::footer')

<?php if ($cta_url !== null) { ?>

      {{ trans('campaign.email_trouble_clicking_button', ['cta_label' => $cta_label]) }}
      {{ $cta_url }}

<?php } ?>

      {!! trans('campaign.email_ip_address', ['ip_address' => request()->ip()]) !!}

    @endcomponent
  @endslot
@endcomponent