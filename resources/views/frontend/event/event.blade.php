@extends('frontend.layout')
@section('pageHeading')
    @if (!empty($pageHeading))
        {{ $pageHeading->event_page_title ?? __('Events') }}
    @else
        {{ __('Events') }}
    @endif
@endsection

@php
    $metaKeywords = !empty($seo->meta_keyword_event) ? $seo->meta_keyword_event : '';
    $metaDescription = !empty($seo->meta_description_event) ? $seo->meta_description_event : '';
@endphp
@section('meta-keywords', "{{ $metaKeywords }}")
@section('meta-description', "$metaDescription")

@section('hero-section')
    <!-- Page Banner Start -->
    <section class="page-banner overlay pt-120 pb-125 rpt-90 rpb-95 lazy"
        data-bg="{{ asset('assets/admin/img/' . $basicInfo->breadcrumb) }}">
        <div class="container">
            <div class="banner-inner">
                <h2 class="page-title">
                    @if (!empty($pageHeading))
                        {{ $pageHeading->event_page_title ?? __('Events') }}
                    @else
                        {{ __('Events') }}
                    @endif
                </h2>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="{{ route('index') }}">{{ __('Home') }}</a></li>
                        <li class="breadcrumb-item active">
                            @if (!empty($pageHeading))
                                {{ $pageHeading->event_page_title ?? __('Events') }}
                            @else
                                {{ __('Events') }}
                            @endif
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </section>
    <!-- Page Banner End -->
@endsection
@section('content')
    <!-- Event Page Start -->
    <section class="event-page-section py-120 rpy-100">
        <div class="container container-custom">
            <div class="row">
                <div class="col-lg-3">
                    @includeIf('frontend.event.event-sidebar')
                </div>
                <div class="col-lg-9">
                    <div class="event-page-content">
                        <div class="row">
                            @if (count($information['events']) > 0)
                                @foreach ($information['events'] as $event)
                                    <div class="col-sm-6 col-xl-4">
                                        <div class="event-item">
                                            <div class="event-image">
                                                <a href="{{ route('event.details', [$event->slug, $event->id]) }}">
                                                    <img class="lazy"
                                                        data-src="{{ asset('assets/admin/img/event/thumbnail/' . $event->thumbnail) }}"
                                                        alt="Event">
                                                </a>
                                            </div>
                                            <div class="event-content">
                                                <ul class="time-info" dir="ltr">
                                                    @php
                                                        if ($event->date_type == 'multiple') {
                                                            $event_date = eventLatestDates($event->id);
                                                            $date = strtotime(@$event_date->start_date);
                                                        } else {
                                                            $date = strtotime($event->start_date);
                                                        }
                                                    @endphp
                                                    <li>
                                                        <i class="far fa-calendar-alt"></i>
                                                        <span>
                                                            {{ \Carbon\Carbon::parse($date)->timezone($websiteInfo->timezone)->translatedFormat('d M') }}
                                                        </span>
                                                    </li>

                                                    <li>
                                                        <i class="far fa-hourglass"></i>
                                                        <span title="Event Duration">
                                                            {{ $event->date_type == 'multiple' ? @$event_date->duration : $event->duration }}
                                                        </span>
                                                    </li>
                                                    <li>
                                                        <i class="far fa-clock"></i>
                                                        <span>
                                                            @php
                                                                $start_time = strtotime($event->start_time);
                                                            @endphp
                                                            {{ \Carbon\Carbon::parse($start_time)->timezone($websiteInfo->timezone)->translatedFormat('h:s A') }}
                                                        </span>
                                                    </li>
                                                </ul>
                                                @if ($event->organizer_id != null)
                                                    @php
                                                        $organizer = App\Models\Organizer::where(
                                                            'id',
                                                            $event->organizer_id,
                                                        )->first();
                                                    @endphp
                                                    @if ($organizer)
                                                        <a href="{{ route('frontend.organizer.details', [$organizer->id, str_replace(' ', '-', $organizer->username)]) }}"
                                                            class="organizer">{{ __('By') }}&nbsp;&nbsp;{{ @$organizer->organizer_info->name }}</a>
                                                    @endif
                                                @else
                                                    @php
                                                        $admin = App\Models\Admin::first();
                                                    @endphp
                                                    <a href="{{ route('frontend.organizer.details', [$admin->id, str_replace(' ', '-', $admin->username), 'admin' => 'true']) }}"
                                                        class="organizer">{{ __('By') }} {{ $admin->username }}</a>
                                                @endif
                                                <h5>
                                                    <a href="{{ route('event.details', [$event->slug, $event->id]) }}">
                                                        @if (strlen($event->title) > 70)
                                                            {{ mb_substr($event->title, 0, 70) . '...' }}
                                                        @else
                                                            {{ $event->title }}
                                                        @endif
                                                    </a>
                                                </h5>
                                                @php
                                                    $desc = strip_tags($event->description);
                                                @endphp

                                                @if (strlen($desc) > 100)
                                                    <p class="event-description">{{ mb_substr($desc, 0, 100) . '....' }}
                                                    </p>
                                                @else
                                                    <p class="event-description">{{ $desc }}</p>
                                                @endif
                                                @php
                                                    if ($event->event_type == 'online') {
                                                        $ticket = App\Models\Event\Ticket::where('event_id', $event->id)
                                                            ->orderBy('price', 'asc')
                                                            ->first();
                                                    } else {
                                                        $ticket = App\Models\Event\Ticket::where([
                                                            ['event_id', $event->id],
                                                            ['price', '!=', null],
                                                        ])
                                                            ->orderBy('price', 'asc')
                                                            ->first();
                                                        if (empty($ticket)) {
                                                            $ticket = App\Models\Event\Ticket::where([
                                                                ['event_id', $event->id],
                                                                ['f_price', '!=', null],
                                                            ])
                                                                ->orderBy('price', 'asc')
                                                                ->first();
                                                        }
                                                    }
                                                    $event_count = DB::table('tickets')
                                                        ->where('event_id', $event->id)
                                                        ->get()
                                                        ->count();
                                                @endphp
                                                @if ($basicInfo->google_map_status == 1 && request()->input('location'))
                                                    <span class="font-sm icon-start d-block">
                                                        <i class="fas fa-map-signs"></i>
                                                        {{ number_format($event->distance / 1000, 2) }}
                                                        {{ __('km') }}
                                                    </span>
                                                @endif
                                                <div class="price-remain">
                                                    <div class="location">
                                                        @if ($event->event_type == 'venue')
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span>
                                                                {{ $event->address }}
                                                            </span>
                                                        @else
                                                            <i class="fas fa-map-marker-alt"></i>
                                                            <span>{{ __('Online') }}</span>
                                                        @endif
                                                    </div>
                                                    <span>

                                                        @if ($ticket)
                                                            @if ($ticket->event_type == 'online')
                                                                @if ($ticket->pricing_type != 'free')
                                                                    <span class="price" dir="ltr">
                                                                        @if ($ticket->early_bird_discount == 'enable')
                                                                            @php
                                                                                $discount_date = Carbon\Carbon::parse(
                                                                                    $ticket->early_bird_discount_date .
                                                                                        $ticket->early_bird_discount_time,
                                                                                );
                                                                            @endphp

                                                                            @if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast())
                                                                                @php
                                                                                    $calculate_price =
                                                                                        $ticket->price -
                                                                                        $ticket->early_bird_discount_amount;
                                                                                @endphp
                                                                                {{ symbolPrice($calculate_price) }}
                                                                                <span>
                                                                                    <del>
                                                                                        {{ symbolPrice($ticket->price) }}
                                                                                    </del>
                                                                                </span>
                                                                            @elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast())
                                                                                @php
                                                                                    $p_price =
                                                                                        ($ticket->price *
                                                                                            $ticket->early_bird_discount_amount) /
                                                                                        100;
                                                                                    $calculate_price =
                                                                                        $ticket->price - $p_price;
                                                                                @endphp

                                                                                {{ symbolPrice($calculate_price) }}
                                                                                <span>
                                                                                    <del>
                                                                                        {{ symbolPrice($ticket->price) }}
                                                                                    </del>
                                                                                </span>
                                                                            @else
                                                                                @php
                                                                                    $calculate_price = $ticket->price;
                                                                                @endphp
                                                                                {{ symbolPrice($calculate_price) }}
                                                                            @endif
                                                                        @else
                                                                            @php
                                                                                $calculate_price = $ticket->price;
                                                                            @endphp
                                                                            {{ symbolPrice($calculate_price) }}
                                                                        @endif

                                                                    </span>
                                                                @else
                                                                    <span class="price">{{ __('Free') }}</span>
                                                                @endif
                                                            @endif
                                                            @if ($ticket->event_type == 'venue')
                                                                @if ($ticket->pricing_type == 'variation')
                                                                    <span class="price" dir="ltr">
                                                                        @php
                                                                            $variation = json_decode(
                                                                                $ticket->variations,
                                                                                true,
                                                                            );

                                                                            $v_min_price = array_reduce(
                                                                                $variation,
                                                                                function ($a, $b) {
                                                                                    return $a['price'] < $b['price']
                                                                                        ? $a
                                                                                        : $b;
                                                                                },
                                                                                array_shift($variation),
                                                                            );

                                                                            if ($v_min_price['slot_enable'] == 1) {
                                                                                $slot_variations = json_decode(
                                                                                    $ticket->variations,
                                                                                    true,
                                                                                );

                                                                                $v_slot_min_price = array_reduce(
                                                                                    $slot_variations,
                                                                                    function ($a, $b) {
                                                                                        return $a['slot_seat_min_price'] < $b['slot_seat_min_price']
                                                                                            ? $a
                                                                                            : $b;
                                                                                    },
                                                                                    array_shift($slot_variations),
                                                                                );
                                                                                $price = $v_slot_min_price['slot_seat_min_price'] ?? 0.0;
                                                                            } else {
                                                                                $price = $v_min_price['price'];
                                                                            }
                                                                        @endphp
                                                                        <span class="price">
                                                                            @if ($currentLanguageInfo->direction == 1)
                                                                                <strong>{{ $event_count > 1 ? '*' : '' }}</strong>
                                                                            @endif
                                                                            @if ($ticket->early_bird_discount == 'enable')
                                                                                @php
                                                                                    $discount_date = Carbon\Carbon::parse(
                                                                                        $ticket->early_bird_discount_date .
                                                                                            $ticket->early_bird_discount_time,
                                                                                    );
                                                                                @endphp
                                                                                @if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast())
                                                                                    @php
                                                                                        $calculate_price =
                                                                                            $price -
                                                                                            $ticket->early_bird_discount_amount;
                                                                                    @endphp
                                                                                    @if ($calculate_price > 0)
                                                                                        {{ symbolPrice($calculate_price) }}
                                                                                        <span>
                                                                                            <del>
                                                                                                {{ symbolPrice($price) }}
                                                                                            </del>
                                                                                        </span>
                                                                                    @endif
                                                                                @elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast())
                                                                                    @php
                                                                                        $p_price =
                                                                                            ($price *
                                                                                                $ticket->early_bird_discount_amount) /
                                                                                            100;
                                                                                        $calculate_price =
                                                                                            $price - $p_price;
                                                                                    @endphp

                                                                                    @if ($calculate_price > 0)
                                                                                        {{ symbolPrice($calculate_price) }}
                                                                                        <span>
                                                                                            <del>
                                                                                                {{ symbolPrice($price) }}
                                                                                            </del>
                                                                                        </span>
                                                                                    @endif
                                                                                @else
                                                                                    @php
                                                                                        $calculate_price = $price;
                                                                                    @endphp
                                                                                    @if ($calculate_price > 0)
                                                                                        {{ symbolPrice($calculate_price) }}
                                                                                    @endif
                                                                                @endif
                                                                            @else
                                                                                @php
                                                                                    $calculate_price = $price;
                                                                                @endphp
                                                                                @if ($calculate_price > 0)
                                                                                    {{ symbolPrice($calculate_price) }}
                                                                                @endif
                                                                            @endif
                                                                            @if ($currentLanguageInfo->direction != 1)
                                                                                <strong>{{ $event_count > 1 ? '*' : '' }}</strong>
                                                                            @endif
                                                                        </span>
                                                                    </span>
                                                                @elseif($ticket->pricing_type == 'normal')
                                                                    <span class="price" dir="ltr">
                                                                        @if ($currentLanguageInfo->direction == 1)
                                                                            <strong>{{ $event_count > 1 ? '*' : '' }}</strong>
                                                                        @endif
                                                                        @php
                                                                            if (
                                                                                $ticket->normal_ticket_slot_enable == 1
                                                                            ) {
                                                                                $ticketPrice =
                                                                                    $ticket->slot_seat_min_price;
                                                                            } else {
                                                                                $ticketPrice = $ticket->price;
                                                                            }
                                                                        @endphp

                                                                        @if ($ticket->early_bird_discount == 'enable')
                                                                            {{-- check discount date over or not --}}
                                                                            @php
                                                                                $discount_date = Carbon\Carbon::parse(
                                                                                    $ticket->early_bird_discount_date .
                                                                                        $ticket->early_bird_discount_time,
                                                                                );
                                                                            @endphp

                                                                            @if ($ticket->early_bird_discount_type == 'fixed' && !$discount_date->isPast())
                                                                                @php
                                                                                    $calculate_price =
                                                                                        $ticketPrice -
                                                                                        $ticket->early_bird_discount_amount;
                                                                                @endphp
                                                                                @if ($calculate_price > 0)
                                                                                    {{ symbolPrice($calculate_price) }}
                                                                                    <span>
                                                                                        <del>
                                                                                            {{ symbolPrice($ticketPrice) }}
                                                                                        </del>
                                                                                    </span>
                                                                                @endif
                                                                            @elseif ($ticket->early_bird_discount_type == 'percentage' && !$discount_date->isPast())
                                                                                @php
                                                                                    $p_price =
                                                                                        ($ticketPrice *
                                                                                            $ticket->early_bird_discount_amount) /
                                                                                        100;
                                                                                    $calculate_price =
                                                                                        $ticketPrice - $p_price;
                                                                                @endphp
                                                                                @if ($calculate_price > 0)
                                                                                    {{ symbolPrice($calculate_price) }}
                                                                                    <span>
                                                                                        <del>
                                                                                            {{ symbolPrice($ticketPrice) }}
                                                                                        </del>
                                                                                    </span>
                                                                                @endif
                                                                            @else
                                                                                @php
                                                                                    $calculate_price = $ticketPrice;
                                                                                @endphp
                                                                                @if ($calculate_price > 0)
                                                                                    {{ symbolPrice($calculate_price) }}
                                                                                @endif
                                                                            @endif
                                                                        @else
                                                                            @php
                                                                                $calculate_price = $ticketPrice;
                                                                            @endphp
                                                                            @if ($calculate_price > 0)
                                                                                {{ symbolPrice($calculate_price) }}
                                                                            @endif
                                                                        @endif
                                                                        @if ($currentLanguageInfo->direction != 1)
                                                                            <strong>{{ $event_count > 1 ? '*' : '' }}</strong>
                                                                        @endif
                                                                    </span>
                                                                @else
                                                                    <span class="price">
                                                                        {{ __('Free') }}
                                                                        <strong>{{ $event_count > 1 ? '*' : '' }}</strong>
                                                                    </span>
                                                                @endif
                                                            @endif
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                            @if (Auth::guard('customer')->check())
                                                @php
                                                    $customer_id = Auth::guard('customer')->user()->id;
                                                    $event_id = $event->id;
                                                    $checkWishList = checkWishList($event_id, $customer_id);
                                                @endphp
                                            @else
                                                @php
                                                    $checkWishList = false;
                                                @endphp
                                            @endif
                                            <a href="{{ $checkWishList == false ? route('addto.wishlist', $event->id) : route('remove.wishlist', $event->id) }}"
                                                class="wishlist-btn {{ $checkWishList == true ? 'bg-success' : '' }}">
                                                <i class="far fa-bookmark"></i>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="col-lg-12">
                                    <h3 class="text-center">{{ __('No Event Found') }}</h3>
                                </div>
                            @endif
                        </div>
                        <ul class="pagination flex-wrap pt-10">
                            {{ $information['events']->links() }}
                        </ul>
                        @if (!empty(showAd(3)))
                            <div class="text-center mt-4">
                                {!! showAd(3) !!}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Event Page End -->

    <form id="filtersForm" class="d-none" action="{{ route('events') }}" method="GET">
        <input type="hidden" id="category-id" name="category"
            value="{{ !empty(request()->input('category')) ? request()->input('category') : '' }}">

        <input type="hidden" id="event" name="event"
            value="{{ !empty(request()->input('event')) ? request()->input('event') : '' }}">

        <input type="hidden" id="min-id" name="min"
            value="{{ !empty(request()->input('min')) ? request()->input('min') : '' }}">

        <input type="hidden" id="max-id" name="max"
            value="{{ !empty(request()->input('max')) ? request()->input('max') : '' }}">

        <input type="hidden" name="search-input"
            value="{{ !empty(request()->input('search-input')) ? request()->input('search-input') : '' }}">
        <input type="hidden" name="location"
            value="{{ !empty(request()->input('location')) ? request()->input('location') : '' }}">

        <input type="hidden" id="dates-id" name="dates"
            value="{{ !empty(request()->input('dates')) ? request()->input('dates') : '' }}">

        <button type="submit" id="submitBtn"></button>
    </form>
@endsection

@section('custom-script')
    <script type="text/javascript" src="{{ asset('assets/front/js/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('assets/front/js/daterangepicker.min.js') }}"></script>

    <script>
        let min_price = {!! htmlspecialchars($information['min']) !!};
        let max_price = {!! htmlspecialchars($information['max']) !!};
        let symbol = "{!! htmlspecialchars($basicInfo->base_currency_symbol) !!}";
        let position = "{!! htmlspecialchars($basicInfo->base_currency_symbol_position) !!}";
        let curr_min = {!! !empty(request()->input('min')) ? htmlspecialchars(request()->input('min')) : 5 !!};
        let curr_max = {!! !empty(request()->input('max')) ? htmlspecialchars(request()->input('max')) : 800 !!};
        const countryUrl = "{{ route('frontend.get_country') }}";
        const stateUrl = "{{ route('frontend.get_state') }}";
        const cityUrl = "{{ route('frontend.get_city') }}";
    </script>

    <script src="{{ asset('assets/front/js/custom_script.js') }}"></script>
    @if ($basicInfo->google_map_status == 1)
        <script src="{{ asset('assets/front/js/geo-search.js') }}"></script>
    @endif
@endsection
