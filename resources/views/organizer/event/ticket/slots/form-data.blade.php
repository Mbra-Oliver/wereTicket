<input type="hidden" name="slot_unique_id" value="{{ $slot_unique_id }}">
<input type="hidden" name="slot_id" value="{{ $slot_id }}">
<input type="hidden" name="slot_type" value="{{ $slot->type }}">
<input type="hidden" name="pricing_type" value="{{ $slot->pricing_type }}">
<div class="table-responsive" style="width: 100%">
    <table class="table">
        <thead>
            <tr class="text-center">
                <th scope="col">{{ __('Sl') }}</th>
                <th scope="col">{{ __('Seat Name') }}</th>
                @if ($slot->type == 1 && $slot->pricing_type != 'free')
                    <th scope="col">{{ __('Price') }}</th>
                @endif
                <th scope="col">{{ __('Deactive') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($slot->seats()->orderBy('id', 'asc')->get() as $key => $seat)
                <tr>
                    <th scope="row">
                        <div class="input-group mb-2">
                            <input type="hidden" name="seatKey[]" value="{{ $key + 1 }}">
                            <strong>{{ __('Seat') }} - {{ $key + 1 }}</strong>
                            @if (in_array($seat->id, $bookedSeats))
                                <span class="badge badge-xs badge-warning">{{ __('Booked') }}</span>
                            @endif
                        </div>
                    </th>
                    <td>
                        <div class="input-group mb-2 mx-2">
                            <input class="form-control" type="text" name="keyName[{{ $seat->id }}]"
                                value="{{ $seat->name }}">
                            <div class="input-group-append">
                                <span class="input-group-text">{{ __('Name') }}</span>
                            </div>
                        </div>
                    </td>
                    @if ($slot_type == 1 && $slot->pricing_type != 'free')
                        <td>
                            <div class="input-group mb-2">
                                <input class="form-control" type="number" name="keyPrice[{{ $seat->id }}]"
                                    value="{{ $seat->price }}" min="0">
                                <div class="input-group-append">
                                    <span
                                        class="input-group-text">{{ __('Price') }}({{ $settings->base_currency_symbol }})</span>
                                </div>
                            </div>
                        </td>
                    @endif
                    <td>
                        <div class="input-group mb-2 mx-2">
                            <label class="switch">
                                <input type="hidden" class="slot_deactive_input"
                                    name="slot_deactive_input[{{ $seat->id }}]" value="{{ $seat->is_deactive }}">
                                <input type="checkbox" class="slot_deactive" name="slot_deactive"
                                    data-event_id="{{ $event_id }}" data-ticket_id="{{ $ticket_id }}"
                                    data-slot_unique_id="{{ $slot_unique_id }}" data-slot_id=""
                                    {{ $seat->is_deactive == 1 ? 'checked' : '' }}
                                    data-slot_is_disable="{{ $slot->is_deactive }}">
                                <span class="slider round"></span>
                            </label>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
