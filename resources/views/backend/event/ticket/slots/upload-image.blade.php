<div class="modal fade" id="uploadMapImage" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="">{{ __('Upload Map Image') }}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <form id="ajaxEditForm" class="modal-form"
                    action="{{ route('admin.event_management.seat_mapping.store_ticket') }}" method="post">
                    @csrf
                    <input type="hidden" name="event_id" value="{{ request()->event_id }}">
                    <div class="form-group">
                        <label for="" class="">{{ __('Image') . '*' }}</label>
                        <br>
                        <div class="thumb-preview">
                            <img src="{{ !is_null($cover_image) ? asset('assets/admin/img/map-image/'.$cover_image) : asset('assets/admin/img/noimage.jpg') }}" alt="..." class="uploaded-img">
                        </div>
                        <div class="mt-3">
                            <div role="button" class="btn btn-primary btn-sm upload-btn">
                                {{ __('Choose Image') }}
                                <input type="file" class="img-input" name="map_image">
                            </div>
                        </div>
                    </div>
                    <p id="editErr_map_image" class="mt-1 mb-0 text-danger em"></p>
                    <p id="editErr_event_id" class="mt-1 mb-0 text-danger em"></p>
                    <p id="editErr_slot_unique_id" class="mt-1 mb-0 text-danger em"></p>
                    <p class="text-warning">{{ __('Upload 1250 * 500 px size image for best quality') }}</p>
                    <input type="hidden" name="event_id" value="{{ $event_id }}">
                    <input type="hidden" name="ticket_id" value="{{ $ticket_id }}">
                    <input type="hidden" name="slot_unique_id" value="{{ $slot_unique_id }}">
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                    {{ __('Close') }}
                </button>
                <button id="updateBtn" type="button" class="btn btn-primary btn-sm">
                    {{ __('Update') }}
                </button>
            </div>
        </div>
    </div>
</div>
