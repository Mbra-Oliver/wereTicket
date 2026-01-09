@extends('backend.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Cities') }}</h4>
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
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('Cities') }}</div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('backend.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary btn-sm float-lg-right float-left"><i class="fas fa-plus"></i>
                                {{ __('Add New') }}</a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('admin.event_management.bulk-delete_city') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($cities) == 0)
                                <h3 class="text-center mt-2">{{ __('NO CITIES FOUND') . '!' }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3" id="basic-datatables">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                @if ($settings->event_country_status == 1)
                                                    <th scope="col">{{ __('Country') }}</th>
                                                @endif
                                                @if ($settings->event_state_status == 1)
                                                    <th scope="col">{{ __('State') }}</th>
                                                @endif
                                                <th scope="col">{{ __('City') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($cities as $city)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $city->id }}">
                                                    </td>
                                                    @if ($settings->event_country_status == 1)
                                                        <td>
                                                            {{ @$city->country->name }}
                                                        </td>
                                                    @endif
                                                    @if ($settings->event_state_status == 1)
                                                        <td>
                                                            {{ @$city->state->name ?? '-' }}
                                                        </td>
                                                    @endif
                                                    <td>
                                                        {{ $city->name }}
                                                    </td>
                                                    <td>
                                                        @if ($city->status == 1)
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-success">{{ __('Active') }}</span>
                                                            </h2>
                                                        @else
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-danger">{{ __('Deactive') }}</span>
                                                            </h2>
                                                        @endif
                                                    </td>
                                                    <td>{{ $city->serial_number }}</td>

                                                    <td>
                                                        <a class="btn btn-secondary btn-xs mr-1 mt-1"
                                                            href="{{ route('admin.event_management.edit_city', ['id' => $city->id, 'language' => request()->input('language')]) }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('admin.event_management.delete_city', ['id' => $city->id]) }}"
                                                            method="post">

                                                            @csrf
                                                            <button type="submit"
                                                                class="btn btn-danger mt-1 btn-xs deleteBtn">
                                                                <span class="btn-label">
                                                                    <i class="fas fa-trash"></i>
                                                                </span>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="card-footer"></div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    @include('backend.event.specification.cities.create')
@endsection
@section('script')
    <script>
        const getCountry = 'get_country';
        const getState = 'get_state';
        const getStateUrl = "{{ route('get.city.state') }}";
    </script>
    <script src="{{ asset('assets/admin/js/event_specification_csc.js') }}"></script>
@endsection
