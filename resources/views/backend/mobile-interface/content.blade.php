@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('backend.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Home Page') }}</h4>
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
                <a href="{{ route('admin.mobile_interface') }}">{{ __('Mobile App Settings') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="separator">
                <a href="#">{{ __('Home Page') }}</a>
            </li>
        </ul>
    </div>



    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Home Page') }}</div>
                        </div>
                        <div class="col-lg-2">
                            @includeIf('backend.partials.languages')
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form id="mobileGeneralForm" action="{{ route('admin.mobile_interface_update') }}" method="post"
                        enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" value="{{ request()->input('language') }}" name="language">
                        <div class="row px-5">
                            <div class="col-lg-8 mx-auto">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="form-group">
                                            <label for="">{{ __('Category Section Title') }}</label>
                                            <input type="text" class="form-control" name="category_title"
                                                value="{{ empty($data->category_title) ? '' : $data->category_title }}"
                                                placeholder="{{ __('Enter category title') }}">
                                            @error('category_title')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="">{{ __('Upcoming Event Section Title') }}</label>
                                            <input type="text" class="form-control" name="upcoming_event_title"
                                                value="{{ empty($data->upcoming_event_title) ? '' : $data->upcoming_event_title }}"
                                                placeholder="{{ __('Enter Upcoming section title') }}">
                                            @error('upcoming_event_title')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="form-group">
                                            <label for="">{{ __('Features  Section Title') }}</label>
                                            <input type="text" class="form-control" name="features_title"
                                                value="{{ empty($data->features_title) ? '' : $data->features_title }}"
                                                placeholder="{{ __('Enter features section title') }}">
                                            @error('features_title')
                                                <div class="text-danger">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="card-footer">
                    <div class="row">
                        <div class="col-12 text-center">
                            <button type="submit" form="mobileGeneralForm" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
