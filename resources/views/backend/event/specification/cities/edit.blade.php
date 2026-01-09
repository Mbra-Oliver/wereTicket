@extends('backend.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Edit City') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('admin.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Event Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Event Specifications') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Cities') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Edit City') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('Edit City') }}</div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('backend.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('admin.event_management.bulk-delete_city') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body" id="createModal">
                    <div class="col-lg-6 mx-auto">
                        <form id="modalForm" class="modal-form create"
                            action="{{ route('admin.event_management.update_city') }}" method="post">
                            @csrf
                            <input type="hidden" name="id" value="{{ $city->id }}">
                            <input type="hidden" name="language_id" value="{{ $city->language_id }}">
                            @if ($settings->event_country_status == 1)
                                <div class="form-group">
                                    <label for="">{{ __('Country') . '*' }}</label>
                                    <select name="country_id" class="form-control select2 countryDropdown country_select">
                                        @if (!is_null($selectedCountry))
                                            <option selected value="{{ $selectedCountry->id }}">
                                                {{ $selectedCountry->name }}
                                            </option>
                                        @else
                                            <option selected disabled>{{ __('Select Country') }}</option>
                                        @endif
                                    </select>
                                    <p id="err_country_id" class="mt-1 mb-0 text-danger em"></p>
                                </div>
                            @endif

                            @if ($settings->event_state_status == 1)
                                @php
                                    $none = 'none';
                                @endphp
                                <div class="form-group state_div" style="display: {{ $city->state_id ? '' : $none }}">
                                    <label for="">{{ __('State') . '*' }}</label>
                                    <select name="state_id" class="form-control select2 stateDropdown state_select"
                                    >
                                        @if (!is_null($selectedState))
                                            <option selected value="{{ $selectedState->id }}">
                                                {{ $selectedState->name }}
                                            </option>
                                        @else
                                            <option selected disabled>{{ __('Select State') }}</option>
                                        @endif
                                    </select>
                                    <p id="err_state_id" class="mt-1 mb-0 text-danger em"></p>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="">{{ __('Name') . ' *' }}</label>
                                <input type="text" class="form-control" name="name" placeholder="Enter name"
                                    value="{{ $city->name }}">
                                <p id="err_name" class="mt-2 mb-0 text-danger em"></p>
                            </div>


                            <div class="form-group">
                                <label for="">{{ __('Status') . '*' }}</label>
                                <select name="status" class="form-control">
                                    <option selected disabled>{{ __('Select a Status') }}</option>
                                    <option value="1" @selected($city->status == 1)>{{ __('Active') }}</option>
                                    <option value="0" @selected($city->status == 0)>{{ __('Deactive') }}</option>
                                </select>
                                <p id="err_status" class="mt-1 mb-0 text-danger em"></p>
                            </div>
                            <div class="form-group">
                                <label for="">{{ __('Serial Number') . ' *' }}</label>
                                <input type="text" class="form-control" name="serial_number"
                                    placeholder="Enter serial number" value="{{ $city->serial_number }}">
                                <p id="err_serial_number" class="mt-2 mb-0 text-danger em"></p>
                                <span
                                    class="text-warning">{{ __('The higher the serial number is, the later the country will be shown.') }}</span>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card-footer text-center">
                    <button id="modalSubmit" type="button" class="btn btn-primary">
                        {{ __('Update') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        const getCountry = 'get_country';
        const getStateUrl = "{{ route('get.city.state') }}";
        const requestLangId = "{{ $city->language_id }}";
    </script>
    <script src="{{ asset('assets/admin/js/event_specification_csc.js') }}"></script>
@endsection
