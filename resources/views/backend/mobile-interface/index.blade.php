@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('backend.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Mobile App Settings') }}</h4>
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
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning text-dark">
                {{ __('This section is for configuring the mobile app settings. All This options may not apply to the web version.') }}
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-lg-10">
                            <div class="card-title">{{ __('Mobile App Settings') }}</div>
                        </div>

                        <div class="col-lg-2">
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">

                        <div class="col-sm-6 col-md-3 mb-4">
                            <a href="{{ route('admin.mobile_interface_content', ['language' => request()->input('language')]) }}"
                                class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border h-100 transition-hover">
                                    <div class="mx-3">
                                        <span class="d-inline-block bg-light rounded-circle p-3">
                                            <i class="fas fa-home fa-lg text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-muted"> {{ __('Home Page') }}</h5>
                                        <div class="fw-semibold text-muted"> {{ __('Update texts') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                        <!--general settings-->
                        <div class="col-sm-12 col-md-3 mb-4">
                            <a href="{{ route('admin.mobile_interface_gsetting', ['language' => $defaultLang->code]) }}"
                                class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border h-100 transition-hover">
                                    <div class="mx-3">
                                        <span class="d-inline-block bg-light rounded-circle p-3">
                                            <i class="fas fa-cogs fa-lg text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-muted"> {{ __('General Settings') }}</h5>
                                        <div class="fw-semibold text-muted"> {{ __('Update general settings') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!--payment settings-->
                        <div class="col-sm-12 col-md-3 mb-4">
                            <a href="{{ route('admin.mobile_interface.payment_gateways', ['language' => $defaultLang->code]) }}"
                                class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border h-100 transition-hover">
                                    <div class="mx-3">
                                        <span class="d-inline-block bg-light rounded-circle p-3">
                                            <i class="fas fa-credit-card fa-lg text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-muted"> {{ __('Payment Gateways') }}</h5>
                                        <div class="fw-semibold text-muted"> {{ __('Update payment gateways settings') }}
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>

                        <!--plugins-->
                        <div class="col-sm-12 col-md-3 mb-4">
                            <a href="{{ route('admin.mobile_interface.plugins', ['language' => $defaultLang->code]) }}"
                                class="text-decoration-none">
                                <div class="d-flex align-items-center p-3 rounded border h-100 transition-hover">
                                    <div class="mx-3">
                                        <span class="d-inline-block bg-light rounded-circle p-3">
                                            <i class="fas fa-plug fa-lg text-primary"></i>
                                        </span>
                                    </div>
                                    <div>
                                        <h5 class="mb-1 text-muted"> {{ __('Plugins') }}</h5>
                                        <div class="fw-semibold text-muted"> {{ __('Update necessary plugins') }}</div>
                                    </div>
                                </div>
                            </a>
                        </div>

                    </div>
                </div>
                <div class="card-footer"></div>
            </div>
        </div>
    </div>
@endsection
