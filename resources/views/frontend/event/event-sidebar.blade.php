  <div class="sidebar rmb-75">
      <div class="widget widget-search">
          <form action="{{ route('events') }}">

              <input type="text" name="search-input"
                  value="{{ !empty(request()->input('search-input')) ? request()->input('search-input') : '' }}"
                  placeholder="{{ __('Search') }}.....">
              @if (request()->filled('category'))
                  <input type="hidden" id="category-id" name="category"
                      value="{{ !empty(request()->input('category')) ? request()->input('category') : '' }}">
              @endif
              @if (request()->filled('event'))
                  <input type="hidden" name="event"
                      value="{{ !empty(request()->input('event')) ? request()->input('event') : '' }}">
              @endif
              @if (request()->filled('min'))
                  <input type="hidden" name="min"
                      value="{{ !empty(request()->input('min')) ? request()->input('min') : '' }}">
              @endif

              @if (request()->filled('max'))
                  <input type="hidden" name="max"
                      value="{{ !empty(request()->input('max')) ? request()->input('max') : '' }}">
              @endif

              @if (request()->filled('location'))
                  <input type="hidden" name="location"
                      value="{{ !empty(request()->input('location')) ? request()->input('location') : '' }}">
              @endif

              @if (request()->filled('dated'))
                  <input type="hidden" name="dates"
                      value="{{ !empty(request()->input('dates')) ? request()->input('dates') : '' }}">
              @endif
              <button type="submit" class="fa fa-search event-search-button"></button>
          </form>
      </div>
      {{-- date filter input --}}
      <div class="widget widget-dropdown">
          <div class="form-group">
              <label for="">{{ __('Filter by Date') }}</label>
              <input type="text" placeholder="{{ __('Start/End Date') }}"
                  @if (request()->input('dates') && request()->input('dates')) value="{{ request()->input('dates') }}" @endif name="daterange" />
          </div>
      </div>
      {{-- location input --}}
      <div class="widget widget-search">
          <form action="{{ route('events') }}" class="mb-20" id="locationForm">

              @if (request()->filled('search-input'))
                  <input type="hidden" name="search-input"
                      value="{{ !empty(request()->input('search-input')) ? request()->input('search-input') : '' }}">
              @endif

              @if (request()->filled('category'))
                  <input type="hidden" id="category-id" name="category"
                      value="{{ !empty(request()->input('category')) ? request()->input('category') : '' }}">
              @endif

              @if (request()->filled('event'))
                  <input type="hidden" name="event"
                      value="{{ !empty(request()->input('event')) ? request()->input('event') : '' }}">
              @endif

              @if (request()->filled('dates'))
                  <input type="hidden" name="dates"
                      value="{{ !empty(request()->input('dates')) ? request()->input('dates') : '' }}">
              @endif

              @if (request()->filled('min'))
                  <input type="hidden" name="min"
                      value="{{ !empty(request()->input('min')) ? request()->input('min') : '' }}">
              @endif

              @if (request()->filled('max'))
                  <input type="hidden" name="max"
                      value="{{ !empty(request()->input('max')) ? request()->input('max') : '' }}">
              @endif
              <input type="text" name="location" value="{{ @request()->input('location') }}"
                  placeholder="{{ __('Enter Location') }}" id="location">
              @if ($basicInfo->google_map_status == 1)
                  <button type="button" class="btn btn-sm current-location" onclick="getCurrentLocation()">
                      <i class="fas fa-crosshairs"></i>
                  </button>
              @else
                  <button type="submit" class="fa fa-search  event-search-button"></button>
              @endif
          </form>


          <form action="{{ route('events') }}" id="countryCityForm">
              @if ($basicInfo->event_country_status == 1)
                  <select id="countryDropdown" name="country" class="mb-20">
                      <option disabled selected>{{ __('Select Country') }}</option>
                      @foreach ($information['countries'] as $country)
                          <option {{ request()->input('country') == $country->slug ? 'selected' : '' }}
                              value="{{ $country->slug }}">{{ $country->name }}</option>
                      @endforeach
                  </select>
              @endif
              @if ($basicInfo->event_state_status == 1)
                  <select id="stateDropdown" name="state" class="mb-20">
                      <option disabled selected>{{ __('Select State') }}</option>
                      @foreach ($information['states'] as $state)
                          <option {{ request()->input('state') == $state->slug ? 'selected' : '' }}
                              value="{{ $state->slug }}" data-id="{{ $state->id }}">{{ $state->name }}</option>
                      @endforeach
                  </select>
              @endif

              <select id="cityDropdown" name="city" class="">
                  <option disabled selected>{{ __('Select City') }}</option>
                  @foreach ($information['cities'] as $city)
                      <option {{ request()->input('city') == $city->slug ? 'selected' : '' }}
                          value="{{ $city->slug }}">{{ $city->name }}</option>
                  @endforeach
              </select>

              @if (request()->filled('location'))
                  <select id="event_sort" name="sort"
                      class="{{ request()->filled('location') ? '' : 'd-none' }} mt-20 select2">
                      <option value="close-by" @selected(request()->input('sort') == 'close-by')>
                          {{ __('Distance') . ': ' }} {{ __('Closest first') }}
                      </option>
                      <option value="distance-away" @selected(request()->input('sort') == 'distance-away')>
                          {{ __('Distance') . ': ' }} {{ __('Farthest first') }}
                      </option>
                  </select>
                  <input type="hidden" name="location" value="{{ @request()->input('location') }}">
              @endif
          </form>
      </div>
      <div class="widget widget-cagegory">
          <h5 class="widget-title">{{ __('Category') }}</h5>
          <form action="{{ route('events') }}" id="catForm">
              @if (request()->filled('search-input'))
                  <input type="hidden" name="search-input"
                      value="{{ !empty(request()->input('search-input')) ? request()->input('search-input') : '' }}">
              @endif

              <select id="category" name="category" class="widget-select">
                  <option disabled>{{ __('Select  Category') }}</option>
                  <option value="">{{ __('All') }}</option>
                  @foreach ($information['categories'] as $item)
                      <option {{ request()->input('category') == $item->slug ? 'selected' : '' }}
                          value="{{ $item->slug }}">{{ $item->name }}</option>
                  @endforeach
              </select>
              {{-- form hidden input --}}

              @if (request()->filled('location'))
                  <input type="hidden" name="location"
                      value="{{ !empty(request()->input('location')) ? request()->input('location') : '' }}">
              @endif

              @if (request()->filled('event'))
                  <input type="hidden" name="event"
                      value="{{ !empty(request()->input('event')) ? request()->input('event') : '' }}">
              @endif

              @if (request()->filled('min'))
                  <input type="hidden" name="min"
                      value="{{ !empty(request()->input('min')) ? request()->input('min') : '' }}">
              @endif

              @if (request()->filled('max'))
                  <input type="hidden" name="max"
                      value="{{ !empty(request()->input('max')) ? request()->input('max') : '' }}">
              @endif

              @if (request()->filled('dates'))
                  <input type="hidden" name="dates"
                      value="{{ !empty(request()->input('dates')) ? request()->input('dates') : '' }}">
              @endif
          </form>
      </div>
      <div class="widget widget-location">
          <h5 class="widget-title">{{ __('Events') }}</h5>
          <div class="widget-radio">
              <div class="custom-control custom-radio">
                  <input type="radio" class="custom-control-input"
                      {{ request()->input('event') == 'online' ? 'checked' : '' }} value="online" name="event"
                      id="radio1">
                  <label class="custom-control-label" for="radio1">{{ __('Online Events') }}</label>
              </div>
              <div class="custom-control custom-radio">
                  <input type="radio" class="custom-control-input" value="venue"
                      {{ request()->input('event') == 'venue' ? 'checked' : '' }} name="event" id="radio2">
                  <label class="custom-control-label" for="radio2">{{ __('Venue Events') }}</label>
              </div>
          </div>
      </div>


      <div class="widget price-filter-widget">
          <h5 class="widget-title">{{ __('Price Filter') }}</h5>
          <div class="price-slider-range" id="range-slider"></div>
          <div class="price-btn">
              <input type="text" dir="ltr" id="price" value="{{ request()->input('min') }}" readonly>
              <button class="theme-btn" id="slider_submit">{{ __('Price Filter') }}</button>
          </div>
      </div>
      @if (!empty(showAd(2)))
          <div class="text-center mt-4">
              {!! showAd(2) !!}
          </div>
      @endif
  </div>
