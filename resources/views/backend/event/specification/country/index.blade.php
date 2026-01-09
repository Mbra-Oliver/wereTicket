@extends('backend.layout')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Countries') }}</h4>
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
                <a href="#">{{ __('Countries') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-4">
                            <div class="card-title d-inline-block">{{ __('Countries') }}</div>
                        </div>

                        <div class="col-lg-3">
                            @includeIf('backend.partials.languages')
                        </div>

                        <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
                            <a href="#" data-toggle="modal" data-target="#createModal"
                                class="btn btn-primary btn-sm float-lg-right float-left"><i class="fas fa-plus"></i>
                                {{ __('Add Country') }}</a>

                            <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                                data-href="{{ route('admin.event_management.bulk-delete_country') }}">
                                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-12">
                            @if (count($countries) == 0)
                                <h3 class="text-center mt-2">{{ __('NO COUNTRIES FOUND') . '!' }}</h3>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-striped mt-3">
                                        <thead>
                                            <tr>
                                                <th scope="col">
                                                    <input type="checkbox" class="bulk-check" data-val="all">
                                                </th>
                                                <th scope="col">{{ __('Name') }}</th>
                                                <th scope="col">{{ __('Status') }}</th>
                                                <th scope="col">{{ __('Serial Number') }}</th>
                                                <th scope="col">{{ __('Actions') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($countries as $country)
                                                <tr>
                                                    <td>
                                                        <input type="checkbox" class="bulk-check"
                                                            data-val="{{ $country->id }}">
                                                    </td>
                                                    <td>
                                                        {{ $country->name }}
                                                    </td>
                                                    <td>
                                                        @if ($country->status == 1)
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-success">{{ __('Active') }}</span>
                                                            </h2>
                                                        @else
                                                            <h2 class="d-inline-block"><span
                                                                    class="badge badge-danger">{{ __('Deactive') }}</span>
                                                            </h2>
                                                        @endif
                                                    </td>
                                                    <td>{{ $country->serial_number }}</td>

                                                    <td>
                                                        <a class="btn btn-secondary btn-xs mr-1 mt-1 editBtn" href="#"
                                                            data-toggle="modal" data-target="#editModal"
                                                            data-id="{{ $country->id }}" data-name="{{ $country->name }}"
                                                            data-status="{{ $country->status }}"
                                                            data-serial_number="{{ $country->serial_number }}">
                                                            <span class="btn-label">
                                                                <i class="fas fa-edit"></i>
                                                            </span>
                                                        </a>

                                                        <form class="deleteForm d-inline-block"
                                                            action="{{ route('admin.event_management.delete_country', ['id' => $country->id]) }}"
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

                <div class="card-footer d-flex justify-content-center">
                    {{ $countries->appends(['language' => $defaultLang->code])->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- create modal --}}
    @include('backend.event.specification.country.create')

    {{-- edit modal --}}
    @include('backend.event.specification.country.edit')
@endsection
