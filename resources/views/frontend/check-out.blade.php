@extends('frontend.layout')
@section('pageHeading')
  {{ __('Check Out') }}
@endsection
@section('custom-style')
  <link rel="stylesheet" href="{{ asset('assets/admin/css/summernote-content.css') }}">
  <style>
    .country-select-wrapper {
      position: relative;
    }
    
    .country-search-container {
      position: relative;
    }
    
    .country-search-container i {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: #6c757d;
      font-size: 14px;
      pointer-events: none;
      z-index: 1;
    }
    
    #country-search {
      padding-left: 40px;
      padding-right: 40px;
      height: 48px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 15px;
      transition: all 0.3s ease;
      background: #fff;
      width: 100%;
    }
    
    #country-search:focus {
      border-color: var(--primary-color, #007bff);
      outline: none;
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
      background: #fff;
    }
    
    #country-search::placeholder {
      color: #9ca3af;
      font-weight: 400;
    }
    
    .country-clear-btn {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6c757d;
      cursor: pointer;
      padding: 5px;
      display: none;
      align-items: center;
      justify-content: center;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      transition: all 0.2s ease;
      z-index: 2;
    }
    
    .country-clear-btn:hover {
      background: #f3f4f6;
      color: #374151;
    }
    
    .country-clear-btn.show {
      display: flex;
    }
    
    .country-dropdown {
      position: absolute;
      top: calc(100% + 5px);
      left: 0;
      right: 0;
      max-height: 350px;
      overflow-y: auto;
      overflow-x: hidden;
      background: #ffffff;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1), 0 4px 6px rgba(0, 0, 0, 0.05);
      z-index: 1050;
      margin-top: 4px;
      opacity: 0;
      visibility: hidden;
      transform: translateY(-10px);
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .country-dropdown.show {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
    }
    
    .country-dropdown::-webkit-scrollbar {
      width: 8px;
    }
    
    .country-dropdown::-webkit-scrollbar-track {
      background: #f1f1f1;
      border-radius: 10px;
    }
    
    .country-dropdown::-webkit-scrollbar-thumb {
      background: #cbd5e0;
      border-radius: 10px;
    }
    
    .country-dropdown::-webkit-scrollbar-thumb:hover {
      background: #a0aec0;
    }
    
    .country-dropdown-header {
      padding: 12px 16px;
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border-bottom: 1px solid #e5e7eb;
      font-size: 12px;
      font-weight: 600;
      color: #6b7280;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      position: sticky;
      top: 0;
      z-index: 10;
    }
    
    .country-dropdown-item {
      padding: 14px 18px;
      cursor: pointer;
      border-bottom: 1px solid #f3f4f6;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: relative;
    }
    
    .country-dropdown-item:last-child {
      border-bottom: none;
    }
    
    .country-dropdown-item:hover {
      background: linear-gradient(90deg, #f8f9fa 0%, #ffffff 100%);
      padding-left: 22px;
    }
    
    .country-dropdown-item.selected {
      background: linear-gradient(90deg, rgba(0, 123, 255, 0.1) 0%, rgba(0, 123, 255, 0.05) 100%);
      border-left: 4px solid var(--primary-color, #007bff);
      padding-left: 18px;
    }
    
    .country-dropdown-item.selected:hover {
      background: linear-gradient(90deg, rgba(0, 123, 255, 0.15) 0%, rgba(0, 123, 255, 0.08) 100%);
    }
    
    .country-name {
      font-size: 15px;
      color: #1f2937;
      font-weight: 500;
      flex: 1;
    }
    
    .country-code {
      font-size: 12px;
      color: #6b7280;
      background: #f3f4f6;
      padding: 4px 10px;
      border-radius: 6px;
      font-weight: 600;
      letter-spacing: 0.5px;
      margin-left: 12px;
    }
    
    .country-dropdown-item.selected .country-code {
      background: var(--primary-color, #007bff);
      color: #fff;
    }
    
    .country-dropdown-item.selected::after {
      content: '✓';
      position: absolute;
      right: 18px;
      color: var(--primary-color, #007bff);
      font-weight: bold;
      font-size: 16px;
    }
    
    .country-no-results {
      padding: 30px 20px;
      text-align: center;
      color: #9ca3af;
      font-size: 14px;
    }
    
    .country-no-results i {
      font-size: 32px;
      margin-bottom: 10px;
      display: block;
      opacity: 0.5;
    }
    
    .country-selected-display {
      display: flex;
      align-items: center;
      padding: 12px 16px;
      background: #f8f9fa;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      cursor: pointer;
      transition: all 0.3s ease;
      min-height: 48px;
    }
    
    .country-selected-display:hover {
      border-color: var(--primary-color, #007bff);
      background: #fff;
    }
    
    .country-selected-display.active {
      border-color: var(--primary-color, #007bff);
      box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
    }
    
    .country-selected-text {
      flex: 1;
      font-size: 15px;
      color: #1f2937;
      font-weight: 500;
    }
    
    .country-selected-arrow {
      color: #6c757d;
      transition: transform 0.3s ease;
    }
    
    .country-selected-display.active .country-selected-arrow {
      transform: rotate(180deg);
    }
    
    @media (max-width: 576px) {
      .country-dropdown {
        max-height: 280px;
      }
      
      .country-dropdown-item {
        padding: 12px 14px;
      }
      
      .country-name {
        font-size: 14px;
      }
    }
  </style>
@endsection
<meta name="csrf-token" content="{{ csrf_token() }}">
@section('hero-section')
  <!-- Page Banner Start -->
  <section class="page-banner overlay pt-120 pb-125 rpt-90 rpb-95 lazy"
    data-bg="{{ asset('assets/admin/img/' . $basicInfo->breadcrumb) }}">
    <div class="container">
      <div class="banner-inner">
        <h2 class="page-title">{{ __('Checkout') }}</h2>
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('index') }}">{{ __('Home') }}</a></li>
            <li class="breadcrumb-item active">{{ __('Checkout') }}</li>
          </ol>
        </nav>
      </div>
    </div>
  </section>
  <!-- Page Banner End -->
  @php
    $authUser = Auth::guard('customer')->user();
  @endphp
@endsection
@section('content')
  <!-- CheckOut Area Start -->
  <section class="checkout-area pt-120 rpt-95 pb-90 rpb-70">
    <div class="container">
      <form class="form" action="{{ route('ticket.booking', [$event->id, 'type' => 'guest']) }}" method="POST"
        enctype="multipart/form-data" id="payment-form">
        @csrf
        <div class="row">
          <div class="col-lg-8">
            <h3 class="from-title mb-25">{{ __('Billing Details') }}</h3>
            <hr>
            <div class="row mt-35">
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="fname">{{ __('First Name') }} *</label>
                  <input type="text" name="fname"
                    value="{{ old('fname', $authUser != null ? $authUser->fname : '') }}" id="fname"
                    class="form-control" placeholder="{{ __('Enter Your First Name') }}">

                  @error('fname')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="lname">{{ __('Last Name') }} *</label>
                  <input type="text" name="lname"
                    value="{{ old('lname', $authUser != null ? $authUser->lname : '') }}" id="lname"
                    class="form-control" placeholder="{{ __('Enter Your Last Name') }}">
                  @error('lname')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="email">{{ __('Email') }} *</label>
                  <input type="text" name="email" id="email"
                    value="{{ old('email', $authUser != null ? $authUser->email : '') }}" class="form-control"
                    placeholder="{{ __('Enter Your Email') }}">
                  @error('email')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="address">{{ __('Phone') }} *</label>
                  <input type="text" name="phone" id="phone" class="form-control"
                    value="{{ old('phone', $authUser != null ? $authUser->phone : '') }}"
                    placeholder="{{ __('Phone Number') }}">
                  @error('phone')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="country">{{ __('Country') }} *</label>
                  <div class="country-select-wrapper">
                    <div class="country-search-container">
                      <i class="fas fa-search"></i>
                      <input type="text" id="country-search" class="form-control" 
                        placeholder="{{ __('Search country...') }}" 
                        autocomplete="off">
                      <button type="button" class="country-clear-btn" id="country-clear" title="{{ __('Clear') }}">
                        <i class="fas fa-times"></i>
                      </button>
                    </div>
                    <select name="country" id="country" class="form-control" required style="display: none;">
                      <option value="">{{ __('Select a country') }}</option>
                      @php
                        $countries = \App\Http\Helpers\CountryList::getAllCountries();
                        $selectedCountry = old('country', $authUser != null ? $authUser->country : '');
                        // Si c'est un code ISO-2, trouver le nom correspondant
                        $selectedCountryName = '';
                        if (strlen($selectedCountry) == 2) {
                          $selectedCountryName = $countries[strtoupper($selectedCountry)] ?? '';
                        } else {
                          $selectedCountryName = $selectedCountry;
                        }
                      @endphp
                      @foreach ($countries as $code => $name)
                        <option value="{{ $code }}" 
                          data-name="{{ $name }}"
                          {{ ($selectedCountry == $code || $selectedCountryName == $name) ? 'selected' : '' }}>
                          {{ $name }} ({{ $code }})
                        </option>
                      @endforeach
                    </select>
                    <div id="country-dropdown" class="country-dropdown">
                      <!-- Options will be populated by JavaScript -->
                    </div>
                  </div>
                  @error('country')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="state">{{ __('State') }}</label>
                  <input type="text" name="state"
                    value="{{ old('state', $authUser != null ? $authUser->state : '') }}" class="form-control"
                    placeholder="{{ __('State') }}">
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="city">{{ __('City') }} *</label>
                  <input type="text" name="city"
                    value="{{ old('city', $authUser != null ? $authUser->city : '') }}" class="form-control"
                    placeholder="{{ __('City') }}">
                  @error('city')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-6">
                <div class="form-group">
                  <label for="company">{{ __('Zip/Post Code') }}</label>
                  <input type="text" name="zip_code"
                    value="{{ old('zip_code', $authUser != null ? $authUser->zip_code : '') }}" class="form-control"
                    placeholder="{{ __('Zip/Post Code') }}">
                    @error('zip_code')
                    <p class="text-danger">{{ $message }}</p>
                  @enderror
                </div>
              </div>
              <div class="col-sm-12">
                <label for="">{{ __('Address') }} * </label>
                <textarea name="address" class="form_control" cols="2" rows="3" placeholder="{{ __('Address') }}">{{ old('address', $authUser != null ? $authUser->address : '') }}</textarea>
                @error('address')
                  <p class="text-danger">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <input type="hidden" name="event" value="{{ $event }}">
            <input type="hidden" name="total" value="{{ $total }}">
            <input type="hidden" name="quantity" value="{{ $quantity }}">
            @if ($selTickets != '')
              @php
                Session::put('selTickets', $selTickets);
              @endphp
            @endif
            @csrf
            <div class="checkout-product mb-25">
              <a href="{{ route('event.details', [$event->slug, $event->id]) }}" class="checkout-product-img">
                <img src="{{ asset('assets/admin/img/event/thumbnail/' . $event->thumbnail) }}" alt="Checkout">
              </a>
              <div class="content">
                <h6><a href="{{ route('event.details', [$event->slug, $event->id]) }}">{{ @$event->title }}</a></h6>
                <span> <i class="fas fa-calendar-alt"></i> {{ date('D, d M Y', strtotime($event->start_date)) }} &nbsp;
                  <i class="fas fa-clock"></i> {{ $event->start_time }}</span>
                @if ($event->event_type == 'venue')
                  <span>
                    <i class="fas fa-map-marker-alt"></i>
                    @if ($event->city != null)
                      {{ $event->city }}
                    @endif
                    @if ($event->country)
                      , {{ $event->country }}
                    @endif
                  </span>
                @else
                  <a href="#">{{ __('Online') }}</a>
                @endif
              </div>
            </div>
            <h3 class="from-title mb-25">{{ __('Order Summary') }}</h3>
            <div>
              <div id="couponReload">
                @php
                  $selTickets = Session::get('selTickets');
                @endphp
                <ul class="package-summary mb-25">
                  @if ($selTickets != null)
                    <li>
                      <span class="text"><strong>{{ __('Tickets Info') }}</strong></span>
                    </li>
                    @foreach ($selTickets as $selTicket)
                      @php
                        $ticket = App\Models\Event\Ticket::where('id', $selTicket['ticket_id'])->first();

                        if ($ticket->pricing_type == 'variation') {
                            $varition_key = App\Models\Event\VariationContent::where([['ticket_id', $selTicket['ticket_id']], ['name', $selTicket['name']]])
                                ->select('key')
                                ->first();

                            $varition_name = App\Models\Event\VariationContent::where([['ticket_id', $ticket->id], ['language_id', $currentLanguageInfo->id], ['key', $varition_key->key]])->first();

                            if ($varition_name) {
                                $name = $varition_name->name;
                            } else {
                                $name = '';
                            }
                        } else {
                            $ticket_content = App\Models\Event\TicketContent::where([['ticket_id', $ticket->id], ['language_id', $currentLanguageInfo->id]])->first();
                            if (empty($ticket_content)) {
                                $ticket_content = App\Models\Event\TicketContent::where([['ticket_id', $ticket->id]])->first();
                            }
                            $name = $ticket_content->title;
                        }
                      @endphp
                      <li>
                        <span class="text">{{ $name }}</span>
                        <span class="number">{{ $selTicket['qty'] }}x</span>
                      </li>
                    @endforeach

                    <hr>
                  @endif
                  <li><span class="text">{{ __('Total Tickets') }}</span> <span
                      class="number">{{ $quantity }}</span></li>
                  <li><span class="text">{{ __('Ticket Price') }}</span>
                    <span dir="ltr" class="number">
                      @if (Session::get('total_early_bird_dicount') != '')
                        {{ symbolPrice(Session::get('sub_total') - Session::get('total_early_bird_dicount')) }}
                      @else
                        {{ symbolPrice(Session::get('sub_total')) }}
                      @endif
                      @if (Session::get('total_early_bird_dicount') != 0)
                        <del class="number">
                          {{ symbolPrice(Session::get('sub_total')) }}
                        </del>
                      @endif
                    </span>
                  </li>

                  @if (Session::get('discount') != '')
                    <li><span class="text">{{ __('Coupon Discount') }}</span> <span class="number" dir="ltr">
                        <span class="text-success"><strong>-</strong>
                          {{ symbolPrice(Session::get('discount')) }}
                        </span>
                      </span>
                    </li>
                  @endif


                  @if (Session::get('total_early_bird_dicount') != '')
                    <li><span class="text">{{ __('Subtotal') }}</span> <span class="number" dir="ltr">

                        @if (Session::get('total_early_bird_dicount') != '')
                          @php
                            $symbol_subtotal = Session::get('sub_total') - (Session::get('total_early_bird_dicount') + Session::get('discount'));
                          @endphp
                          {{ symbolPrice($symbol_subtotal) }}
                        @else
                          {{ symbolPrice(Session::get('sub_total') - Session::get('discount')) }}
                        @endif
                    </li>
                  @endif

                  @php
                    if (Session::get('total_early_bird_dicount') != '') {
                        $subtotal = Session::get('sub_total') - (Session::get('total_early_bird_dicount') + Session::get('discount'));
                    } else {
                        $subtotal = Session::get('sub_total') - Session::get('discount');
                    }
                    $tax = ($subtotal * $basicData->tax) / 100;
                    $tax = round($tax, 2);
                  @endphp
                  <li><span class="text">{{ __('Tax') }} (<span
                        dir="ltr">{{ $basicData->tax }}%</span>)</span> <span class="number" dir="ltr">
                      <span class="text-danger">
                        <strong>+</strong>
                        {{ symbolPrice($tax) }}
                      </span>
                    </span>
                  </li>
                  <li><span class="text">{{ __('Total') }}</span> <span class="number" dir="ltr">
                      @php
                        $symbol_total = Session::get('sub_total') - (Session::get('discount') + Session::get('total_early_bird_dicount')) + $tax;
                      @endphp
                      {{ symbolPrice($symbol_total) }}
                    </span>
                  </li>
                  @php
                    $sub_total = Session::get('sub_total');
                    $discount = Session::get('discount');
                    $total_early_bird_dicount = Session::get('total_early_bird_dicount');

                    $grand_total = $sub_total + $tax - ($discount + $total_early_bird_dicount);
                    Session::put('tax', $tax);
                    Session::put('grand_total', $sub_total - ($discount + $total_early_bird_dicount));
                  @endphp
                </ul>
              </div>
            </div>

            @if ($total != 0 || Session::get('sub_total') != 0)
              <div class="coupon">
                <h4 class="mb-3">{{ __('Coupon') }}</h4>
                <div class="input-group d-flex">
                  <input type="text" onsubmit="event.preventDefault();" class="form-control" name="coupon"
                    id="coupon-code" value="">
                  <div class="input-group-append">
                    <button class="btn theme-btn base-btn" type="button">{{ __('Apply') }}</button>
                  </div>
                </div>
              </div>
              <h5 class="from-title mt-20 mb-15">{{ __('Payment Method') }}</h5>
              @if (Session::has('paypal_error'))
                <p class="text-danger">{{ Session::get('paypal_error') }}</p>
                @php
                  Session::forget('paypal_error');
                @endphp
              @endif
              @if (Session::has('error'))
                <p class="text-danger">{{ Session::get('error') }}</p>
              @endif
              <div class="form-group">
                <select name="gateway" id="payment">
                  <option value="">{{ __('Select a payment method') }}</option>
                  @foreach ($online_gateways as $online_gateway)
                    <option value="{{ $online_gateway->keyword }}"
                      {{ $online_gateway->keyword == old('gateway') ? 'selected' : '' }}>
                      {{ __("$online_gateway->name") }}</option>
                  @endforeach
                  @foreach ($offline_gateways as $offline_gateway)
                    <option value="{{ $offline_gateway->id }}"
                      {{ $offline_gateway->id == old('gateway') ? 'selected' : '' }}>
                      {{ __("$offline_gateway->name") }}</option>
                  @endforeach
                </select>
                @error('gateway')
                  <p class="text-danger">{{ $message }}</p>
                @enderror()
                @if (Session::has('currency_error'))
                  <p class="text-danger">{{ Session::get('currency_error') }}</p>
                @endif
              </div>

              <div class="iyzico-element {{ old('gateway') == 'iyzico' ? '': 'd-none'}}" >
                <input type="text" name="identity_number" class="form_control" placeholder="Identity Number">
                @error('identity_number')
                  <p class="text-danger">{{ $message }}</p>
                @enderror
              </div>

              <div id="stripe-element" class="mb-2">
                <!-- A Stripe Element will be inserted here. -->
              </div>
              <!-- Used to display form errors -->
              <div id="stripe-errors" role="alert" class="mb-2"></div>
              @foreach ($offline_gateways as $offlineGateway)
                <div class="@if (
                    $errors->has('attachment') &&
                        request()->session()->get('gatewayId') == $offlineGateway->id) d-block @else d-none @endif offline-gateway-info"
                  id="{{ 'offline-gateway-' . $offlineGateway->id }}">
                  @if (!is_null($offlineGateway->short_description))
                    <div class="form-group mb-4">
                      <label>{{ __('Description') }}</label>
                      <p>{{ $offlineGateway->short_description }}</p>
                    </div>
                  @endif

                  @if (!is_null($offlineGateway->instructions))
                    <div class="form-group mb-4">
                      <label>{{ __('Instructions') }}</label>
                      <div class="summernote-content">
                        {!! $offlineGateway->instructions !!}
                      </div>
                    </div>
                  @endif

                  @if ($offlineGateway->has_attachment == 1)
                    <div class="form-group mb-4">
                      <label>{{ __('Attachment') . '*' }}</label>
                      <br>
                      <input type="file" name="attachment">
                      @error('attachment')
                        <p class="text-danger mt-1">{{ $message }}</p>
                      @enderror
                      <p></p>
                    </div>
                  @endif
                </div>
              @endforeach

              <button type="submit" class="theme-btn w-100">{{ __('Proceed to Pay') }}</button>
            @else
              <button type="submit" class="theme-btn w-100">{{ __('Submit') }}</button>
            @endif


          </div>
        </div>
      </form>
    </div>
  </section>
  <!-- CheckOut Area End -->
@endsection

@section('custom-script')
  <script src="https://js.stripe.com/v3/"></script>
  <script type="text/javascript">
    let url = "{{ route('apply-coupon') }}";
    let stripe_key = "{{ $stripe_key }}";
  </script>
  <script src="{{ asset('assets/front/js/event_checkout.js') }}"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const countrySearch = document.getElementById('country-search');
      const countrySelect = document.getElementById('country');
      const countryDropdown = document.getElementById('country-dropdown');
      const countryClear = document.getElementById('country-clear');
      const wrapper = document.querySelector('.country-select-wrapper');
      
      if (!countrySearch || !countrySelect || !countryDropdown) return;
      
      // Récupérer toutes les options
      const options = Array.from(countrySelect.options).slice(1);
      
      // Initialiser l'affichage
      const selectedOption = countrySelect.options[countrySelect.selectedIndex];
      if (selectedOption && selectedOption.value) {
        const countryName = selectedOption.dataset.name || selectedOption.textContent.replace(/\s*\([^)]*\)$/, '');
        countrySearch.value = countryName;
        updateClearButton();
      }
      
      // Fonction pour afficher/masquer le bouton clear
      function updateClearButton() {
        if (countryClear) {
          if (countrySearch.value.trim() !== '') {
            countryClear.classList.add('show');
          } else {
            countryClear.classList.remove('show');
          }
        }
      }
      
      // Fonction pour afficher le dropdown avec animation
      function showDropdown() {
        countryDropdown.classList.add('show');
      }
      
      // Fonction pour masquer le dropdown avec animation
      function hideDropdown() {
        countryDropdown.classList.remove('show');
      }
      
      // Fonction pour créer un élément de pays
      function createCountryItem(option, isSelected = false) {
        const item = document.createElement('div');
        item.className = 'country-dropdown-item' + (isSelected ? ' selected' : '');
        item.dataset.value = option.value;
        
        const name = document.createElement('span');
        name.className = 'country-name';
        name.textContent = option.dataset.name || option.textContent.replace(/\s*\([^)]*\)$/, '');
        
        const code = document.createElement('span');
        code.className = 'country-code';
        code.textContent = option.value;
        
        item.appendChild(name);
        item.appendChild(code);
        
        item.addEventListener('click', function() {
          selectCountry(option.value, name.textContent);
        });
        
        return item;
      }
      
      // Fonction pour sélectionner un pays
      function selectCountry(code, name) {
        countrySelect.value = code;
        countrySearch.value = name;
        hideDropdown();
        updateClearButton();
        countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
      }
      
      // Fonction pour filtrer et afficher les options
      function filterCountries(searchTerm) {
        const term = searchTerm.toLowerCase().trim();
        countryDropdown.innerHTML = '';
        
        if (term === '') {
          hideDropdown();
          return;
        }
        
        const filtered = options.filter(option => {
          const name = (option.dataset.name || option.textContent).toLowerCase();
          const code = option.value.toLowerCase();
          return name.includes(term) || code.includes(term);
        });
        
        if (filtered.length === 0) {
          countryDropdown.innerHTML = `
            <div class="country-no-results">
              <i class="fas fa-search"></i>
              <div>{{ __('No country found') }}</div>
            </div>
          `;
          showDropdown();
          return;
        }
        
        // Afficher jusqu'à 15 résultats
        filtered.slice(0, 15).forEach(option => {
          const isSelected = option.selected || countrySelect.value === option.value;
          const item = createCountryItem(option, isSelected);
          countryDropdown.appendChild(item);
        });
        
        showDropdown();
      }
      
      // Événement de recherche
      countrySearch.addEventListener('input', function() {
        filterCountries(this.value);
        updateClearButton();
        selectedIndex = -1;
      });
      
      // Afficher le dropdown au focus
      countrySearch.addEventListener('focus', function() {
        if (this.value.trim() !== '') {
          filterCountries(this.value);
        } else {
          // Afficher les 15 premiers pays par défaut
          countryDropdown.innerHTML = '';
          options.slice(0, 15).forEach(option => {
            const isSelected = option.selected || countrySelect.value === option.value;
            const item = createCountryItem(option, isSelected);
            countryDropdown.appendChild(item);
          });
          showDropdown();
        }
      });
      
      // Bouton clear
      if (countryClear) {
        countryClear.addEventListener('click', function(e) {
          e.stopPropagation();
          countrySearch.value = '';
          countrySelect.value = '';
          hideDropdown();
          updateClearButton();
          countrySearch.focus();
          countrySelect.dispatchEvent(new Event('change', { bubbles: true }));
        });
      }
      
      // Fermer le dropdown en cliquant ailleurs
      document.addEventListener('click', function(e) {
        if (!wrapper.contains(e.target)) {
          hideDropdown();
        }
      });
      
      // Navigation au clavier
      let selectedIndex = -1;
      
      countrySearch.addEventListener('keydown', function(e) {
        if (!countryDropdown.classList.contains('show')) {
          if (e.key === 'ArrowDown' || e.key === 'Enter') {
            e.preventDefault();
            if (this.value.trim() === '') {
              this.dispatchEvent(new Event('focus'));
            } else {
              filterCountries(this.value);
            }
          }
          return;
        }
        
        const visibleItems = Array.from(countryDropdown.querySelectorAll('.country-dropdown-item'));
        
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          selectedIndex = Math.min(selectedIndex + 1, visibleItems.length - 1);
          updateKeyboardSelection(visibleItems);
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          selectedIndex = Math.max(selectedIndex - 1, -1);
          updateKeyboardSelection(visibleItems);
        } else if (e.key === 'Enter') {
          e.preventDefault();
          if (selectedIndex >= 0 && visibleItems[selectedIndex]) {
            visibleItems[selectedIndex].click();
          }
        } else if (e.key === 'Escape') {
          hideDropdown();
          selectedIndex = -1;
        }
      });
      
      function updateKeyboardSelection(items) {
        items.forEach((item, index) => {
          if (index === selectedIndex) {
            item.style.backgroundColor = 'var(--primary-color, #007bff)';
            item.style.color = '#fff';
            item.querySelector('.country-code').style.background = 'rgba(255, 255, 255, 0.2)';
            item.querySelector('.country-code').style.color = '#fff';
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
          } else {
            item.style.backgroundColor = '';
            item.style.color = '';
            const codeEl = item.querySelector('.country-code');
            if (codeEl) {
              codeEl.style.background = '';
              codeEl.style.color = '';
            }
          }
        });
      }
    });
  </script>
@endsection
