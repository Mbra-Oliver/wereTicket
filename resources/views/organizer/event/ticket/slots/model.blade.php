<div class="modal fade" id="seatMapModelEdit" tabindex="-1" role="dialog" aria-labelledby="seatMapEdit" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="seatMapEdit">{{ __('Edit Seat') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div class="d-none alert alert-danger pb-1 px-0 mt-4" id="slot_errors_2">
                    <ul></ul>
                </div>
                <form id="ajaxForm"
                    action="{{ route('organizer.event_management.seat_mapping.slot.seat_mapping_update') }}" method="post">
                    @csrf
                    <input type="hidden" name="ticket_id" value="{{ $ticket_id }}">
                    <div id="seat_map_form"></div>
                </form>

            </div>
            <div class="modal-footers d-flex justify-content-center align-items-center mb-3">
                <button type="button" id="submitBtn_Slot" class="btn btn-primary">{{ __('Update') }}</button>
            </div>
        </div>
    </div>
</div>
