@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('backend.partials.rtl-style')

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Payment Gateways') }}</h4>
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
                <a href="#">{{ __('Payment Gateways') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">

        <!--paypal-->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_paypal_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Paypal') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Paypal Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paypal_status" value="1"
                                                class="selectgroup-input"
                                                {{ $paypal->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paypal_status" value="0"
                                                class="selectgroup-input"
                                                {{ $paypal->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('paypal_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paypal_status') }}</p>
                                    @endif
                                </div>

                                @php $paypalInfo = json_decode($paypal->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Paypal Test Mode') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paypal_sandbox_status" value="1"
                                                class="selectgroup-input"
                                                {{ @$paypalInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paypal_sandbox_status" value="0"
                                                class="selectgroup-input"
                                                {{ @$paypalInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('paypal_sandbox_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paypal_sandbox_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Paypal Client ID') }}</label>
                                    <input type="text" class="form-control" name="paypal_client_id"
                                        value="{{ @$paypalInfo['client_id'] }}">
                                    @if ($errors->has('paypal_client_id'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paypal_client_id') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Paypal Client Secret') }}</label>
                                    <input type="text" class="form-control" name="paypal_client_secret"
                                        value="{{ @$paypalInfo['client_secret'] }}">
                                    @if ($errors->has('paypal_client_secret'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paypal_client_secret') }}</p>
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

        <!-- toyyibpay -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_toyyibpay_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Toyyibpay') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1" class="selectgroup-input"
                                                {{ $toyyibpay->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0" class="selectgroup-input"
                                                {{ $toyyibpay->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $toyyibpayInfo = json_decode($toyyibpay->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Test Mode') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="1"
                                                class="selectgroup-input"
                                                {{ @$toyyibpayInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="0"
                                                class="selectgroup-input"
                                                {{ @$toyyibpayInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('sandbox_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('sandbox_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Secret Key') }}</label>
                                    <input type="text" class="form-control" name="secret_key"
                                        value="{{ @$toyyibpayInfo['secret_key'] }}">
                                    @if ($errors->has('secret_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('secret_key') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Category Code') }}</label>
                                    <input type="text" class="form-control" name="category_code"
                                        value="{{ @$toyyibpayInfo['category_code'] }}">
                                    @if ($errors->has('category_code'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('category_code') }}</p>
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

        <!-- stripe -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_stripe_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Stripe') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Stripe Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="stripe_status" value="1"
                                                class="selectgroup-input"
                                                {{ $stripe->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="stripe_status" value="0"
                                                class="selectgroup-input"
                                                {{ $stripe->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('stripe_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('stripe_status') }}</p>
                                    @endif
                                </div>

                                @php $stripeInfo = json_decode($stripe->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Stripe Key') }}</label>
                                    <input type="text" class="form-control" name="stripe_key"
                                        value="{{ @$stripeInfo['key'] }}">
                                    @if ($errors->has('stripe_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('stripe_key') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Stripe Secret') }}</label>
                                    <input type="text" class="form-control" name="stripe_secret"
                                        value="{{ @$stripeInfo['secret'] }}">
                                    @if ($errors->has('stripe_secret'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('stripe_secret') }}</p>
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

        <!-- flutterwave -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_flutterwave_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Flutterwave') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Flutterwave Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="flutterwave_status" value="1"
                                                class="selectgroup-input"
                                                {{ $flutterwave->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="flutterwave_status" value="0"
                                                class="selectgroup-input"
                                                {{ $flutterwave->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('flutterwave_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('flutterwave_status') }}</p>
                                    @endif
                                </div>

                                @php $flutterwaveInfo = json_decode($flutterwave->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Flutterwave Public Key') }}</label>
                                    <input type="text" class="form-control" name="flutterwave_public_key"
                                        value="{{ @$flutterwaveInfo['public_key'] }}">
                                    @if ($errors->has('flutterwave_public_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('flutterwave_public_key') }}
                                        </p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Flutterwave Secret Key') }}</label>
                                    <input type="text" class="form-control" name="flutterwave_secret_key"
                                        value="{{ @$flutterwaveInfo['secret_key'] }}">
                                    @if ($errors->has('flutterwave_secret_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('flutterwave_secret_key') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- midtrans -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_midtrans_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Midtrans') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $midtrans->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $midtrans->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $midtransInfo = json_decode($midtrans->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Test Mode') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="is_production" value="1"
                                                class="selectgroup-input"
                                                {{ @$midtransInfo['is_production'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="is_production" value="0"
                                                class="selectgroup-input"
                                                {{ @$midtransInfo['is_production'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('is_production'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('is_production') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Server Key') }}</label>
                                    <input type="text" class="form-control" name="server_key"
                                        value="{{ @$midtransInfo['server_key'] }}">
                                    @if ($errors->has('server_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('server_key') }}</p>
                                    @endif
                                    <span>
                                        <span
                                            class="text-warning">{{ __('Set these URLs in Midtrans Dashboard like this') . ':' }}</span>
                                        <a href="https://prnt.sc/OiucUCeYJIXo"
                                            target="_blank">{{ __('See Example') }}</a>
                                    </span>
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

        <!-- myfatoorah -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_myfatoorah_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('My-Fatoorah') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('MyFatoorah Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $myfatoorah->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $myfatoorah->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $myfatoorahInfo = json_decode($myfatoorah->mobile_information, true); @endphp
                                <div class="form-group">
                                    <label>{{ __('Sandbox Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="1"
                                                class="selectgroup-input"
                                                {{ @$myfatoorahInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="0"
                                                class="selectgroup-input"
                                                {{ @$myfatoorahInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('sandbox_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('sandbox_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Token') }}</label>
                                    <input type="text" class="form-control" name="token"
                                        value="{{ @$myfatoorahInfo['token'] }}">
                                    @if ($errors->has('token'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('token') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 py-2 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>



        <!-- phonepe -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_phonepe_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Phonepe') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $phonepe->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $phonepe->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $phonepeInfo = json_decode($phonepe->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Sandbox Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="1"
                                                class="selectgroup-input"
                                                {{ @$phonepeInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="0"
                                                class="selectgroup-input"
                                                {{ @$phonepeInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('sandbox_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('sandbox_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Client Id') }}</label>
                                    <input type="text" class="form-control" name="merchant_id"
                                        value="{{ @$phonepeInfo['merchant_id'] }}">
                                    @if ($errors->has('merchant_id'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('merchant_id') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Client Secret Key') }}</label>
                                    <input type="text" class="form-control" name="salt_key"
                                        value="{{ @$phonepeInfo['salt_key'] }}">
                                    @if ($errors->has('salt_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('salt_key') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Salt Index') }}</label>
                                    <input type="number" class="form-control" name="salt_index"
                                        value="{{ @$phonepeInfo['salt_index'] }}">
                                    @if ($errors->has('salt_index'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('salt_index') }}</p>
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

        <!--Monnify-->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_monify') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Monnify') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $monnify->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $monnify->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $monifyInfo = json_decode($monnify->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Sandbox Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="1"
                                                class="selectgroup-input"
                                                {{ @$monifyInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="sandbox_status" value="0"
                                                class="selectgroup-input"
                                                {{ @$monifyInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('sandbox_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('sandbox_status') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Api Key') }}</label>
                                    <input type="text" class="form-control" name="api_key"
                                        value="{{ @$monifyInfo['api_key'] }}">
                                    @if ($errors->has('api_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('api_key') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Secret Key') }}</label>
                                    <input type="text" class="form-control" name="secret_key"
                                        value="{{ @$monifyInfo['secret_key'] }}">
                                    @if ($errors->has('secret_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('secret_key') }}</p>
                                    @endif
                                </div>
                                <div class="form-group">
                                    <label>{{ __('Wallet Account Number') }}</label>
                                    <input type="number" class="form-control" name="wallet_account_number"
                                        value="{{ @$monifyInfo['wallet_account_number'] }}">
                                    @if ($errors->has('wallet_account_number'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('wallet_account_number') }}
                                        </p>
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

        <!-- paystack -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_paystack_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Paystack') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Paystack Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paystack_status" value="1"
                                                class="selectgroup-input"
                                                {{ $paystack->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="paystack_status" value="0"
                                                class="selectgroup-input"
                                                {{ $paystack->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('paystack_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paystack_status') }}</p>
                                    @endif
                                </div>

                                @php $paystackInfo = json_decode($paystack->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Paystack Secret Key') }}</label>
                                    <input type="text" class="form-control" name="paystack_key"
                                        value="{{ @$paystackInfo['key'] }}">
                                    @if ($errors->has('paystack_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('paystack_key') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- nowpayments -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_nowpayments') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('NowPayments') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $now_payments->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $now_payments->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $now_paymentsInfo = json_decode($now_payments->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Api Key') }}</label>
                                    <input type="text" class="form-control" name="api_key"
                                        value="{{ @$now_paymentsInfo['api_key'] }}">
                                    @if ($errors->has('api_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('api_key') }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="col-12 text-center">
                            <button type="submit" class="btn btn-success">
                                {{ __('Update') }}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- mollie -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_mollie_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Mollie') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="mollie_status" value="1"
                                                class="selectgroup-input"
                                                {{ $mollie->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="mollie_status" value="0"
                                                class="selectgroup-input"
                                                {{ $mollie->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('mollie_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('mollie_status') }}</p>
                                    @endif
                                </div>

                                @php $mollieInfo = json_decode($mollie->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('API Key') }}</label>
                                    <input type="text" class="form-control" name="mollie_key"
                                        value="{{ @$mollieInfo['key'] }}">
                                    @if ($errors->has('mollie_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('mollie_key') }}</p>
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

        <!-- xendit -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_xendit_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Xendit') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="1"
                                                class="selectgroup-input"
                                                {{ $xendit->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="status" value="0"
                                                class="selectgroup-input"
                                                {{ $xendit->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('status') }}</p>
                                    @endif
                                </div>

                                @php $xenditInfo = json_decode($xendit->mobile_information, true); @endphp


                                <div class="form-group">
                                    <label>{{ __('Secret Key') }}</label>
                                    <input type="text" class="form-control" name="secret_key"
                                        value="{{ @$xenditInfo['secret_key'] }}">
                                    @if ($errors->has('secret_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('secret_key') }}</p>
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

        <!-- mercadopago -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_mercadopago_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('MercadoPago') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="form-group">
                            <label>{{ __('MercadoPago Status') }}</label>
                            <div class="selectgroup w-100">
                                <label class="selectgroup-item">
                                    <input type="radio" name="mercadopago_status" value="1"
                                        class="selectgroup-input"
                                        {{ $mercadopago->mobile_status == 1 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Active') }}</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="mercadopago_status" value="0"
                                        class="selectgroup-input"
                                        {{ $mercadopago->mobile_status == 0 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                </label>
                            </div>
                            @if ($errors->has('mercadopago_status'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('mercadopago_status') }}</p>
                            @endif
                        </div>

                        @php $mercadopagoInfo = json_decode($mercadopago->mobile_information, true); @endphp

                        <div class="form-group">
                            <label>{{ __('MercadoPago Test Mode') }}</label>
                            <div class="selectgroup w-100">
                                <label class="selectgroup-item">
                                    <input type="radio" name="mercadopago_sandbox_status" value="1"
                                        class="selectgroup-input"
                                        {{ @$mercadopagoInfo['sandbox_status'] == 1 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Active') }}</span>
                                </label>
                                <label class="selectgroup-item">
                                    <input type="radio" name="mercadopago_sandbox_status" value="0"
                                        class="selectgroup-input"
                                        {{ @$mercadopagoInfo['sandbox_status'] == 0 ? 'checked' : '' }}>
                                    <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                </label>
                            </div>
                            @if ($errors->has('mercadopago_sandbox_status'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('mercadopago_sandbox_status') }}</p>
                            @endif
                        </div>

                        <div class="form-group">
                            <label>{{ __('MercadoPago Token') }}</label>
                            <input type="text" class="form-control" name="mercadopago_token"
                                value="{{ @$mercadopagoInfo['token'] }}">
                            @if ($errors->has('mercadopago_token'))
                                <p class="mt-1 mb-0 text-danger">{{ $errors->first('mercadopago_token') }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="card-footer">
                        <div class="row">
                            <div class="col-12 py-2 text-center">
                                <button type="submit" class="btn btn-success">
                                    {{ __('Update') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>


        <!-- razorpay -->
        <div class="col-lg-4">
            <div class="card">
                <form action="{{ route('admin.payment_gateways.update_razorpay_info') }}" method="post">
                    @csrf
                    <input type="hidden" name="is_mobile" value="1">
                    <div class="card-header">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="card-title">{{ __('Razorpay') }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <div class="col-lg-12">
                                <div class="form-group">
                                    <label>{{ __('Razorpay Status') }}</label>
                                    <div class="selectgroup w-100">
                                        <label class="selectgroup-item">
                                            <input type="radio" name="razorpay_status" value="1"
                                                class="selectgroup-input"
                                                {{ $razorpay->mobile_status == 1 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Active') }}</span>
                                        </label>
                                        <label class="selectgroup-item">
                                            <input type="radio" name="razorpay_status" value="0"
                                                class="selectgroup-input"
                                                {{ $razorpay->mobile_status == 0 ? 'checked' : '' }}>
                                            <span class="selectgroup-button">{{ __('Deactive') }}</span>
                                        </label>
                                    </div>
                                    @if ($errors->has('razorpay_status'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('razorpay_status') }}</p>
                                    @endif
                                </div>

                                @php $razorpayInfo = json_decode($razorpay->mobile_information, true); @endphp

                                <div class="form-group">
                                    <label>{{ __('Razorpay Key') }}</label>
                                    <input type="text" class="form-control" name="razorpay_key"
                                        value="{{ @$razorpayInfo['key'] }}">
                                    @if ($errors->has('razorpay_key'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('razorpay_key') }}</p>
                                    @endif
                                </div>

                                <div class="form-group">
                                    <label>{{ __('Razorpay Secret') }}</label>
                                    <input type="text" class="form-control" name="razorpay_secret"
                                        value="{{ @$razorpayInfo['secret'] }}">
                                    @if ($errors->has('razorpay_secret'))
                                        <p class="mt-1 mb-0 text-danger">{{ $errors->first('razorpay_secret') }}</p>
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
