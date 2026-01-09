@extends('organizer.layout')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/admin/css/slot.css') }}">
@endsection

@section('content')
    <div class="page-header">
        <h4 class="page-title">{{ __('Slot Settings') }}</h4>
        <ul class="breadcrumbs">
            <li class="nav-home">
                <a href="{{ route('organizer.dashboard') }}">
                    <i class="flaticon-home"></i>
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Events Management') }}</a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="">
                    {{ __('All Events') }}
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>

            <li class="nav-item">
                <a href="#">
                    {{ strlen($event_contents->title) > 35 ? mb_substr($event_contents->title, 0, 35, 'UTF-8') . '...' : $event_contents->title }}
                </a>
            </li>
            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">
                    {{ strlen($ticket_contents->title) > 35 ? mb_substr($ticket_contents->title, 0, 35, 'UTF-8') . '...' : $ticket_contents->title }}
                </a>
            </li>

            @if (!empty($variation))
                <li class="separator">
                    <i class="flaticon-right-arrow"></i>
                </li>

                <li class="nav-item">
                    <a
                        href="{{ route('organizer.event.edit.ticket', ['language' => $defaultLang->code, 'event_id' => $event_id, 'id' => $ticket_id, 'event_type' => $event_type]) }}">
                        {{ strlen($variation->name) > 35 ? mb_substr($variation->name, 0, 35, 'UTF-8') . '...' : $variation->name }}
                    </a>
                </li>
            @endif

            <li class="separator">
                <i class="flaticon-right-arrow"></i>
            </li>
            <li class="nav-item">
                <a href="#">{{ __('Slot Settings') }}</a>
            </li>
        </ul>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="card-title d-inline-block d-inline-block">{{ __('Slot Settings') }}</div>
                    <a class="btn btn-info btn-sm float-right d-inline-block"
                        href="{{ route('organizer.event.edit.ticket', ['language' => $defaultLang->code, 'event_id' => $event_id, 'id' => $ticket_id, 'event_type' => $event_type]) }}">
                        <span class="btn-label">
                            <i class="fas fa-backward"></i>
                        </span>
                        {{ __('Back') }}
                    </a>
                    <a class="mr-2 btn btn-success btn-sm float-right d-inline-block"
                        href="{{ route('event.details', [
                            'slug' => eventSlug($defaultLang->id, $event_id),
                            'id' => $event_id,
                        ]) }}"
                        target="_blank">
                        <span class="btn-label">
                            <i class="fas fa-eye"></i>
                        </span>
                        {{ __('Preview') }}
                    </a>
                    <div class="btn btn-primary float-right btn-sm mx-1 font-weight-bold" data-toggle="modal"
                        data-target="#uploadMapImage">{{ __('Upload Map Image') }}</div>
                </div>
                <div class="card-body">

                    <div class="col-lg-12">
                        <div class="alert alert-warning pb-1">
                            <p class="font-weight-bold">
                                <span class="text-danger font-weight-bold">{{ __('Step-by-step') }} :</span>
                                <span class="text-danger">
                                    <span class="">
                                        {{ __(1) }}.
                                        {{ __('First upload venue map image.') }}
                                    </span>
                                    <span class="">
                                        {{ __(2) }}.
                                        {{ __('Then create slots on the map image.') }}
                                    </span>
                                    <span class="">
                                        {{ __(3) }}.
                                        {{ __('You can drag, drap, rotate, change color etc… for each slot.') }}
                                    </span>
                                    <span class="">
                                        {{ __(4) }}.
                                        {{ __('Set prices for each seat of a slot.') }}
                                    </span>

                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="col-lg-12 pb-5">
                        @if (!empty($cover_image))
                            <div class="table-responsive">
                                <div id="image-container">
                                    <img id="target-image" src="{{ asset('assets/admin/img/map-image/' . $cover_image) }}"
                                        alt="Image" class="border border-1">
                                </div>
                                <div id="popup-form" class="popup-form">
                                    <div class="mb-3">
                                        <button type="button" id="editSeatMap"
                                            class="btn btn-primary btn-sm seat_map_modal d-none">
                                            <i class="fas fa-edit"></i>
                                            <span class="editSeatMapText">{{ __('Set Name & Price') }}</span>
                                        </button>
                                        <button class="close-button" type="button">&times;</button>
                                    </div>

                                    <div class="d-none alert alert-danger pb-1 px-0 mt-4" id="slot_errors">
                                        <ul></ul>
                                    </div>

                                    <div class="row">
                                        <div class="col-4">
                                            <label for="" style="color:black !important"
                                                class="font-weight-bold">{{ __('Width (px)') }}
                                                *</label>
                                            <input type="number" name="width" id="width-input" class="form_control"
                                                placeholder="{{ __('Width (px)') }}">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="text-dark font-weight-bold"
                                                style="color:black !important">{{ __('Height (px)') }} *</label>
                                            <input type="number" name="height" id="height-input" class="form_control"
                                                placeholder="{{ __('Height (px)') }}" min="0">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="font-weight-bold"
                                                style="color:black !important">{{ __('Font Size (px)') }}
                                                *</label>
                                            <input type="number" name="font_size" id="font-size" class="form_control"
                                                placeholder="{{ __('Font Size(px)') }}" min="0" value="0"
                                                max="360">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-4">
                                            <label for="" class="font-weight-bold"
                                                style="color:black !important">{{ __('Rotate (deg)') }}
                                                *</label>
                                            <input type="number" name="degree" id="rotate-input" class="form_control"
                                                placeholder="{{ __('Rotate (deg)') }}" min="0" value="0"
                                                max="360">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="font-weight-bold"
                                                style="color:black !important">{{ __('Round (%)') }}
                                                *</label>
                                            <input type="number" name="round" id="round-input" class="form_control"
                                                placeholder="{{ __('Round shape(%)') }}" min="0" value="0"
                                                max="100">
                                        </div>
                                        <div class="col-4">
                                            <label for="" class="text-dark font-weight-bold"
                                                style="color:black !important">{{ __('Background') }}
                                                *</label>
                                            <input type="color" name="background_color" id="background-input"
                                                class="form_control" placeholder="{{ __('Background (px)') }}"
                                                value="#D8D8D8">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-12 mt-1">
                                            <label for="" style="color:black !important"
                                                class="d-flex font-weight-bold">{{ __('Slot type') }} *</label>
                                            <div class="form-check form-check-inline pb-0 px-0">
                                                <input class="form-check-input" type="radio" name="slot_type"
                                                    id="inlineRadio1" value="1">
                                                <label class="form-check-label" for="inlineRadio1"
                                                    style="color:black !important">
                                                    {{ __('Manual Seat Selection') }}</label>
                                            </div>
                                            <div class="form-check form-check-inline pt-0 px-0">
                                                <input class="form-check-input" type="radio" name="slot_type"
                                                    id="inlineRadio2" value="2">
                                                <label class="form-check-label" for="inlineRadio2"
                                                    style="color:black !important">
                                                    {{ __('Auto-Select All Seats') }}</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-6">
                                            <label for="" class="text-dark font-weight-bold"
                                                style="color:black !important">{{ __('Number of Seat') }} *</label>
                                            <input type="number" name="number_of_seat" value=""
                                                class="form_control" placeholder="{{ __('Number of Seat') }}">
                                        </div>
                                        <div class="col-6" id="priceDiv">
                                            <label for="" class="text-dark font-weight-bold"
                                                style="color:black !important">{{ __('Price') }}*
                                                ({{ $settings->base_currency_text }})</label>
                                            <input type="number" name="price" id="price"
                                                placeholder="{{ __('Price') }}" class="form_control" name="price"
                                                min="0">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-8">
                                            <label for="" style="color:black !important"
                                                class="font-weight-bold">
                                                {{ __('Slot Name') }}*</label>
                                            <input type="text" id="slot_name" class="form_control" name="slot_name"
                                                placeholder="{{ __('Slot Name') }}">
                                        </div>
                                        <div class="col-4">
                                            <label for="" style="color:black !important"
                                                class="font-weight-bold">{{ __('Deactive') }}*</label>
                                            <label class="switch">
                                                <input type="checkbox" class="slot_deactive" name="slot_deactive"
                                                    data-event_id="{{ $event_id }}"
                                                    data-ticket_id="{{ $ticket_id }}"
                                                    data-slot_unique_id="{{ $slot_unique_id }}" data-slot_id="">
                                                <span class="slider round"></span>
                                            </label>
                                        </div>
                                    </div>

                                    <input type="hidden" id="slot_id" name="slot_id">
                                    <button id="submit-button">{{ __('Submit') }}</button>
                                </div>
                            </div>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                            <br>
                        @else
                            <p class="text-center mt-5">{{ __('No Image Found !') }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    @include('backend.event.ticket.slots.upload-image')
    @include('backend.event.ticket.slots.model')
@endsection

@section('script')
    <script>
        "use strict"
        $(document).ready(function() {
            var message_1 =
                "{{ __('This slot is already booked. Please delete the booking first, then delete the slot.') }}";
            var selectedBox = null;
            var defaultWidth = 30;
            var defaultHeight = 30;
            var defaultRotateDegree = 0;
            var defaultRoundShape = 10;
            var defaultBackgroundColor = "#00e5b5";
            var defaultFontSize = 14;
            var pricing_type = "{{ $pricing_type }}";
            var boxes = @json($slots);
            var event_id = "{{ $event_id }}";
            var ticket_id = "{{ $ticket_id }}";
            var slot_unique_id = "{{ $slot_unique_id }}";
            var type_one_edit_btn_text = "{{ __('Set Seat Name and Price') }}";
            if (pricing_type == 'free') {
                type_one_edit_btn_text = "{{ __('Set Seat Name') }}";
            }
            var type_two_edit_btn_text = "{{ __('Set Seat Name') }}"
            var modal_type_one_edit_btn_text = "{{ __('Edit Seat Name and Price') }}";
            if (pricing_type == 'free') {
                modal_type_one_edit_btn_text = "{{ __('Edit Seat Name') }}";
            }
            var modal_type_two_edit_btn_text = "{{ __('Edit Seat Name') }}";
            var boxLevelCss;
            var edit_txt = "{{ __('Edit') }}";

            boxes.forEach(function(box) {
                let {
                    pos_x,
                    pos_y,
                    width,
                    height,
                    slot_name,
                    price,
                    id,
                    is_booked,
                    rotate,
                    background_color,
                    slot_type,
                    number_of_seat,
                    font_size,
                    is_deactive,
                    round
                } = box;

                drawBox(
                    pos_x,
                    pos_y,
                    width,
                    height,
                    slot_name,
                    price,
                    id,
                    is_booked,
                    rotate,
                    background_color,
                    slot_type,
                    number_of_seat,
                    font_size,
                    is_deactive,
                    round
                );
            });

            function drawBox(
                pos_x,
                pos_y,
                width,
                height,
                slot_name = '',
                price = 0.00,
                slot_id = null,
                slot_booked = 0,
                slot_rotate = 0,
                slot_background_color = defaultBackgroundColor,
                slot_type = null,
                number_of_seat = null,
                font_size = defaultFontSize,
                is_deactive,
                round = defaultRoundShape
            ) {

                var boxClasses = 'box';
                if (slot_booked == 1) {
                    boxClasses += ' disable_class';
                }
                var pos_x = pos_x - 0;
                var pos_y = pos_y - 0;

                var box = $('<div class="' + boxClasses + '">')
                    .css({
                        left: pos_x,
                        top: pos_y,
                        width: width,
                        height: height,
                        rotate: slot_rotate + 'deg',
                        borderRadius: round + '%',
                        background: slot_background_color,
                        borderColor: darkenColor(slot_background_color, 10),
                        color: darkenColor(slot_background_color, 30)
                    })
                    .data('slot_id', slot_id)
                    .data('pos_x', pos_x)
                    .data('pos_y', pos_y)
                    .data('width', width)
                    .data('height', height)
                    .data('round', round)
                    .data('slot_name', slot_name)
                    .data('price', price)
                    .data('slot_booked', slot_booked)
                    .data('slot_rotate', slot_rotate)
                    .data('slot_background_color', slot_background_color)
                    .data('slot_type', slot_type)
                    .data('number_of_seat', number_of_seat)
                    .data('font_size', font_size)
                    .data('slot_deactive', is_deactive)
                    .appendTo('#image-container');

                let width_height = "height:100px"
                var label = $('<div class="box-label box_css">')
                    .css({
                        width: width - 3,
                        height: height - 3,
                        rotate: 360 - slot_rotate + 'deg',
                        fontSize: font_size + 'px'
                    })
                    .text(slot_name)
                    .appendTo(box);

                box.draggable({
                    containment: "parent",
                    stop: function(event, ui) {
                        selectedBox = $(this);
                        // Get the current position values
                        var pos_x = ui.position.left;
                        var pos_y = ui.position.top;
                        var slot_id = selectedBox.data('slot_id');
                        var event_id = event_id
                        var ticket_id = ticket_id;
                        var slot_unique_id = slot_unique_id;
                        var url =
                            "{{ route('organizer.event_management.seat_mapping.slot.drup_drop') }}";
                        // Update the data attributes with the new position values

                        //when not exists store slot
                        if (!slot_id) {
                            return true;
                        }
                        selectedBox.data('pos_x', pos_x);
                        selectedBox.data('pos_y', pos_y);
                        selectedBox.data('pos_y', pos_y);
                        var inputData = {
                            pos_x: pos_x,
                            pos_y: pos_y,
                            event_id: event_id,
                            ticket_id: ticket_id,
                            slot_unique_id: slot_unique_id,
                            slot_id: slot_id,
                            _token: '{{ csrf_token() }}'
                        };


                        $(".request-loader").addClass('show');

                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: inputData,
                            success: function(response) {
                                $(".request-loader").removeClass('show');
                                bootnotify('Updated Successfully !', 'Success', 'success');
                            },
                            error: function(response) {
                                bootnotify('Oops! Something went wrong.', 'Warning',
                                    'warning');
                                $(".request-loader").removeClass('show');
                            }
                        });

                    }
                });
                //remove button
                var removeButton = $('<button class="remove-button "></button>')
                    .appendTo(box)
                    .click(function(e) {
                        e.stopPropagation(); // Prevent box click event from being triggered
                        var slot_id = box.data('slot_id');
                        var slot_booked = box.data('slot_booked');
                        if (slot_id) {
                            if (slot_booked == 0) {
                                $(".request-loader").addClass('show');
                                var form_url =
                                    "{{ route('organizer.event_management.seat_mapping.slot.delete') }}";
                                $.ajax({
                                    url: form_url,
                                    type: 'POST',
                                    data: {
                                        slot_id: slot_id,
                                        _token: '{{ csrf_token() }}'
                                    },
                                    success: function(response) {
                                        $(".request-loader").removeClass('show');
                                        box.remove();
                                        bootnotify('Delete Successfully!', 'Success', 'success')
                                    },
                                    error: function(response) {
                                        bootnotify('Oops! Something went wrong.', 'Warning',
                                            'warning');
                                    }
                                });

                            } else {
                                bootnotify(message_1, 'Waning', 'warning');
                                return false
                            }
                        } else {
                            $(".request-loader").removeClass('show');
                            box.remove();
                        }
                    });

                //edit button
                var editButton = $('<button class="edit-button"></button>')
                    .appendTo(box)
                    .click(function(e) {
                        e.stopPropagation();
                        $(".request-loader").addClass('show');
                        selectedBox = box;
                        let width_v = selectedBox.data('width');
                        let height_v = selectedBox.data('height');
                        let price_v = selectedBox.data('price');
                        let slot_id_v = selectedBox.data('slot_id');
                        let slot_name_v = selectedBox.data('slot_name');
                        let slot_rotate_v = selectedBox.data('slot_rotate');
                        let slot_background_color_v = selectedBox.data('slot_background_color');
                        let slot_type_v = selectedBox.data('slot_type');
                        let number_of_seat_v = selectedBox.data('number_of_seat');
                        let font_size = selectedBox.data('font_size');
                        let slot_deactive = selectedBox.data('slot_deactive');
                        let round = selectedBox.data('round');
                        showPopupForm(
                            pos_x,
                            pos_y,
                            width_v,
                            height_v,
                            slot_name_v,
                            price_v,
                            slot_id_v,
                            slot_rotate_v,
                            slot_background_color_v,
                            slot_type_v,
                            number_of_seat_v,
                            font_size,
                            slot_deactive,
                            round
                        );
                    });
            }
            //show popup form
            function showPopupForm(
                pos_x,
                pos_y,
                width,
                height,
                slot_name,
                price,
                slot_id = null,
                slot_rotate_v,
                slot_background_color_v,
                slot_type_v,
                number_of_seat_v,
                font_size,
                slot_deactive = 0,
                round
            ) {
                let current_width = getCurrentWindowSize()
                $('.request-loader').addClass('show');
                //error slots
                $('body #slot_errors').removeClass("d-block");
                $('body #slot_errors').addClass("d-none");

                var pos_x = pos_x - 0;
                var pos_y = pos_y - 0;
                var height = height - 0;

                // Update width and height inputs with box dimensions
                $("input[name='width']").val(width);
                $("input[name='height']").val(height);
                $("input[name='price']").val(price);
                $("input[name='slot_id']").val(slot_id);
                $("input[name='slot_name']").val(slot_name);
                $("input[name='degree']").val(slot_rotate_v);
                $("input[name='round']").val(round);
                $("input[name='background_color']").val(slot_background_color_v);
                $("input[name='number_of_seat']").val(number_of_seat_v);
                $("input[name='font_size']").val(font_size);
                $(`input[name='slot_type'][value='${slot_type_v}']`).prop("checked", true);
                $(`input[name='slot_deactive']`).attr("data-slot_id", slot_id);
                $(`input[name='slot_deactive']`).prop("checked", slot_deactive == 0 ? false : true);
                //slot type
                priceDivShowHide(slot_type_v);

                if (slot_id) {
                    $("#editSeatMap").attr('data-slot_id', slot_id)
                    $("#editSeatMap").removeClass('d-none');
                    $("#editSeatMap").addClass('d-block');

                    $("#editSeatMap .editSeatMapText").text(slot_type_v == 1 ? type_one_edit_btn_text :
                        type_two_edit_btn_text);

                    $("#seatMapEdit").text(edit_txt + " > " + slot_name);

                } else {
                    $("#editSeatMap").removeClass('d-block');
                    $("#editSeatMap").addClass('d-none');
                }

                setTimeout(() => {
                    $(".request-loader").removeClass('show');
                    $('#popup-form').css({
                        left: current_width > 576 ? pos_x : 0 + 30,
                        top: pos_y + height,
                    }).fadeIn();

                }, 500);

            }

            $('#target-image').click(function(e) {
                var pos_x = e.pageX - $(this).offset().left;
                var pos_y = e.pageY - $(this).offset().top;
                drawBox(pos_x, pos_y, defaultWidth, defaultHeight);
            });


            $('#draw-button').click(function() {
                $('#image-container').children('.box').remove();
            });

            // Update box dimensions when input values change
            $('#width-input, #height-input, #background-input, #rotate-input, #font-size, #slot_name ,#round-input')
                .on('input',
                    function() {

                        var width = parseInt($('#width-input').val()) || defaultWidth;
                        var height = parseInt($('#height-input').val()) || defaultHeight;
                        var rotateDegree = parseInt($('#rotate-input').val()) || defaultRotateDegree;
                        var roundShape = parseInt($('#round-input').val()) || defaultRoundShape;
                        var backgroundColor = $('#background-input').val() || defaultBackgroundColor;
                        var fontSize_2 = $('#font-size').val() || defaultFontSize;
                        var slot_name = $("#slot_name").val() || '';

                        if (selectedBox) {
                            selectedBox.css({
                                width: width,
                                height: height,
                                rotate: rotateDegree + 'deg',
                                borderRadius: roundShape + '%',
                                background: backgroundColor,
                                borderColor: darkenColor(backgroundColor, 10),
                                color: darkenColor(backgroundColor, 30),
                                backdropFilter: 'unset'
                            });

                        }
                        boxLevelCss = selectedBox.find('.box-label');
                        boxLevelCss.css({
                            width: width - 5,
                            height: height - 5,
                            rotate: 360 - rotateDegree + 'deg',
                            fontSize: fontSize_2 + 'px'
                        });

                        var labal = selectedBox.find('.box-label.box_css');
                        labal.text(slot_name)

                    });

            // Close the popup form when the close button is clicked
            $('.close-button').click(function(e) {
                e.preventDefault();
                $('#popup-form').fadeOut();
            });

            // Close the popup form when clicking outside of the relevant box or the form popup
            $(document).click(function(event) {
                if (!$(event.target).closest('.box, .popup-form').length) {
                    $('#popup-form').fadeOut();
                }
            });

            $('#submit-button').click(function() {
                $(".request-loader").addClass('show');
                var url = "{{ route('organizer.event_management.seat_mapping.slot.store_update') }}"

                var slot_id = $('#slot_id').val();
                var pos_x = selectedBox.position().left;
                var pos_y = selectedBox.position().top;
                var background_color = $("input[name='background_color']").val();
                var degree = $("input[name='degree']").val();
                var round = $("input[name='round']").val();
                var width = $("input[name='width']").val();
                var height = $("input[name='height']").val();
                var price = $("input[name='price']").val();
                var slot_type = $("input[name='slot_type']:checked").val();
                var number_of_seat = $("input[name='number_of_seat']").val();
                var slot_name = $("input[name='slot_name']").val();
                var font_size = $("input[name='font_size']").val();
                var slot_deactive = $("input[name='slot_deactive']").is(':checked') ? 1 : 0;


                var inputData = {
                    pos_x: pos_x,
                    pos_y: pos_y,
                    rotate: degree,
                    width: width,
                    height: height,
                    price: price,
                    background_color: background_color,
                    number_of_seat: number_of_seat,
                    slot_id: slot_id,
                    event_id: event_id,
                    ticket_id: ticket_id,
                    slot_unique_id: slot_unique_id,
                    slot_name: slot_name,
                    font_size: font_size,
                    slot_type: slot_type,
                    slot_deactive: slot_deactive,
                    round: round,
                    pricing_type: pricing_type,
                    _token: '{{ csrf_token() }}',
                };

                $.ajax({
                    url: url,
                    type: 'POST',
                    data: inputData,
                    success: function(response) {
                        selectedBox.find('.box-label').text(response.slot_name);
                        boxLevelCss = selectedBox.find('.box-label');
                        boxLevelCss.css({
                            width: width - 5,
                            height: height - 5,
                        });
                        selectedBox.data('slot_id', response.id);
                        selectedBox.data('width', width);
                        selectedBox.data('height', height);
                        selectedBox.data('slot_name', response.slot_name);
                        selectedBox.data('price', response.price);
                        selectedBox.data('slot_rotate', response.rotate);
                        selectedBox.data('slot_background_color', response.background_color);
                        selectedBox.data('slot_type', response.type);
                        selectedBox.data('number_of_seat', response.number_of_seat);
                        selectedBox.data('font_size', response.font_size);
                        selectedBox.data('slot_deactive', response.is_deactive);
                        selectedBox.data('round', response.round);

                        //data init
                        $('body #slot_errors').removeClass("d-block");
                        $('body #slot_errors').addClass("d-none");

                        $(".request-loader").removeClass('show');
                        $('#popup-form').fadeOut();

                        if (slot_id == "") {
                            if (slot_type == 1) {
                                $("#editSeatMap").attr('data-slot_id', response.id);
                                $("#editSeatMap").trigger('click');
                            } else {
                                bootnotify('Add Successfully', 'Success', 'success')
                            }
                        } else {
                            bootnotify('Updated Successfully', 'Success', 'success')
                        }
                    },
                    error: function(error) {
                        let errors = ``;
                        for (let x in error.responseJSON.errors) {
                            errors += `<li>
                            <p class="text-danger mb-0">${error.responseJSON.errors[x][0]}</p>
                          </li>`;
                        }
                        $('body #slot_errors ul').html(errors);
                        $('body #slot_errors').removeClass("d-none");
                        $('body #slot_errors').addClass("d-block");
                        $('.request-loader').removeClass('show');


                    }
                });
            });

            function getCurrentWindowSize() {
                var windowWidth = window.innerWidth ||
                    document.documentElement.clientWidth ||
                    document.body.clientWidth;
                return windowWidth;
            }

            let slot_type = $("input[name='slot_type']:checked").val();
            priceDivShowHide(slot_type);
            $('body').on('change', "input[name='slot_type']", function($e) {
                let slot_type = $(this).val();
                priceDivShowHide(slot_type);
            });

            function priceDivShowHide(slot_type) {
                let $priceDiv = $("#priceDiv");
                if (slot_type == 1) {
                    $priceDiv.hide();
                } else {
                    $priceDiv.show();
                }
                if (pricing_type == 'free') {
                    $priceDiv.hide();
                }


            }

            function darkenColor(hex = '#fff', percent) {
                let num = parseInt(hex.slice(1), 16),
                    amt = Math.round(2.55 * percent),
                    R = (num >> 16) - amt,
                    G = (num >> 8 & 0x00FF) - amt,
                    B = (num & 0x0000FF) - amt;

                return "#" + (
                    0x1000000 +
                    (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
                    (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
                    (B < 255 ? (B < 1 ? 0 : B) : 255)
                ).toString(16).slice(1);
            }

            $(".seat_map_modal").click(function(e) {
                e.preventDefault();
                $("#seatMapModelEdit").modal("show");
                let url = "{{ route('organizer.event_management.seat_mapping.slot.seat_mapping') }}";
                let slot_id = $(this).attr('data-slot_id');
                $.ajax({
                    url: url,
                    method: 'GET',
                    data: {
                        slot_id: slot_id,
                        event_id: event_id,
                        ticket_id: ticket_id,
                        slot_unique_id: slot_unique_id,
                    },
                    success: function(data) {
                        $("#seat_map_form").html(data.view);
                    }
                });
            })

            $("#submitBtn_Slot").on('click', function(e) {
                $(".request-loader").addClass("show");
                if ($(".iconpicker-component").length > 0) {
                    $("#inputIcon").val($(".iconpicker-component").find('i').attr('class'));
                }
                let ajaxForm = document.getElementById('ajaxForm');
                let fd = new FormData(ajaxForm);
                let url = $("#ajaxForm").attr('action');
                let method = $("#ajaxForm").attr('method');

                //if summernote has then get summernote content
                $('.form-control').each(function(i) {
                    let index = i;
                    let $toInput = $('.form-control').eq(index);
                    if ($(this).hasClass('summernote')) {
                        let tmcId = $toInput.attr('id');
                        let content = tinyMCE.get(tmcId).getContent();
                        fd.delete($(this).attr('name'));
                        fd.append($(this).attr('name'), content);
                    }
                });

                $.ajax({
                    url: url,
                    method: method,
                    data: fd,
                    contentType: false,
                    processData: false,
                    success: function(data) {
                        $(e.target).attr('disabled', false);
                        $('.request-loader').removeClass('show');
                        $('.em').each(function() {
                            $(this).html('');
                        });
                        if (data.status == 'success') {
                            location.reload();
                        }
                    },
                    error: function(error) {
                        let errors = ``;
                        for (let x in error.responseJSON.errors) {
                            errors += `<li>
                            <p class="text-danger mb-0">${error.responseJSON.errors[x][0]}</p>
                          </li>`;
                        }
                        $('body #slot_errors_2 ul').html(errors);
                        $('body #slot_errors_2').removeClass("d-none");
                        $('body #slot_errors_2').addClass("d-block");
                        $('.request-loader').removeClass('show');
                    }
                });
            })

            $('body').on('change', '.slot_deactive', function(event) {
                let btn = $(this);
                let is_checked = btn.is(':checked') == true ? 1 : 0;
                btn.siblings('.slot_deactive_input').val(is_checked);
            });
        });
    </script>
@endsection
