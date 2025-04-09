<button class="btn btn-round btn-outline-primary btn-block d-flex justify-content-between mt-2 mb-2 px-5 to-tooltip {{((Auth::check() && !GenericHelper::isEmailEnforcedAndValidated()) || (Auth::check() && !GenericHelper::creatorCanEarnMoney($user)) ) ? 'disabled' : ''}}"
        @if(Auth::check())
            @if(!Auth::user()->email_verified_at && getSetting('site.enforce_email_validation'))
                data-placement="top"
                title="{{__('Please verify your account')}}"
            @elseif(!GenericHelper::creatorCanEarnMoney($user))
                data-placement="top"
                title="{{__('This creator cannot earn money yet')}}"
            @else
                data-toggle="modal"
                data-target="#checkout-center"
                data-type="yearly-subscription"
                data-recipient-id="{{$user->id}}"
                data-amount="{{$user->profile_access_price_12_months ? $user->profile_access_price_12_months * 12 : 0}}"
                data-first-name="{{Auth::user()->first_name}}"
                data-last-name="{{Auth::user()->last_name}}"
                data-billing-address="{{Auth::user()->billing_address}}"
                data-country="{{Auth::user()->country}}"
                data-city="{{Auth::user()->city}}"
                data-state="{{Auth::user()->state}}"
                data-postcode="{{Auth::user()->postcode}}"
                data-available-credit="{{Auth::user()->wallet->total}}"
                data-username="{{$user->username}}"
                data-name="{{$user->name}}"
                data-avatar="{{$user->avatar}}"
            @endif
        @else
            data-toggle="modal"
            data-target="#login-dialog"
    @endif
>
    <span>{{__('Subscribe')}}</span>
    <span class="d-flex">
        {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($user->profile_access_price_12_months * 12)}}
        {{__('for')}}
        {{trans_choice('months', 12,['number'=>12])}}
        <span class="d-none d-md-flex ml-1">
            @if(isset($offer['discountAmount']['365']) && $offer['discountAmount']['365'] > 0)
                ({{round($offer['discountAmount']['365'])}}% {{__('off')}})
            @endif
        </span>

    </span>
</button>
