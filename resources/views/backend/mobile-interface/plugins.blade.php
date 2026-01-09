@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('backend.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Plugins') }}</h4>
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
            <li class="nav-item">
                <a href="#">{{ __('Plugins') }}</a>
            </li>
        </ul>
    </div>
    <div class="row">
        <!-- store firebase service file to send notifications -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.updateFirebase') }}" method="post"
                    enctype="multipart/form-data">
                    @csrf

                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Firebase') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Upload Firebase Admin JSON') . '*' }}</label>
                                    <input type="file" class="form-control" name="firebase_admin_json"
                                        value="{{ !empty($data) ? $data->firebase_admin_json : '' }}">
                                    <small
                                        class="text-warning">{{ __('Upload the Firebase Admin SDK JSON file from your Firebase project.') }}</small>

                                    @if ($errors->has('firebase_admin_json'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('firebase_admin_json') }}</p>
                                    @endif
                                    @if ($data && $data->firebase_admin_json)
                                        <br>
                                        <span
                                            class="text-warning">{{ __('You have a file, and you can change it by re-uploading it.') }}</span>
                                    @endif
                                    <br>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.basic_settings.mobile_interface.geo') }}" method="post">
                    @csrf
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Google Map') }}</div>
                                <div class="alert alert-warning">
                                    {{ __('When plugins have Google Map credentials, they can be used.') }}
                                    <a href="{{ route('admin.basic_settings.plugins') }}"
                                        style="text-poss">{{ __('Go Here') }}</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Google Map Status') . '*' }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="app_google_map_status" value="1"
                                                class="selectgroup-input"
                                                {{ $data->app_google_map_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>

                                        <label class="selectgroup-item">
                                            <input type="radio" name="app_google_map_status" value="0"
                                                class="selectgroup-input"
                                                {{ $data->app_google_map_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>

                                    @if ($errors->has('app_google_map_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('app_google_map_status') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>


    </div>
@endsection
