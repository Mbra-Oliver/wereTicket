<div class="modal fade" id="seatMappingModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
  aria-hidden="true">
  <div class="modal-dialog modal-fullscreen" role="document" style="max-width:1300px">
    <div class="modal-content">

      <div class="modal-header justify-content-start flex-column">
        <h5 class="modal-title" id="exampleModalLabel">{{ __('Select Your Seats') }}</h5>
        <button type="button" class="close close_slot_modal">
          <span aria-hidden="true">&times;</span>
        </button>
        <div class="selected_sloat_wrap_main">
          <div class="selected_sloat_wrap d-flex justify-content-center align-items-center">
            <div class="d-flex justify-content-center">{{ __('Selected:') }} </div>
            <div id="selected_sloat" class="ml-1">{{ __('No seat selected yet') }}</div>
          </div>
        </div>
      </div>

      <div class="modal-body">
        <div class="position-relative">
          <div class="seat-map-wrapper" id="sloat_map_image" style="transform: scale(1); transform-origin: top start;">
          </div>
          <div class="zoom-controls">
            <button class="btn btn-outline-primary" id="zoomIn">+</button>
            <button class="btn btn-outline-primary" id="zoomOut">-</button>
            <button class="btn btn-outline-primary" id="resetZoom">↺</button>
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between align-items-center">
        <div class="d-flex justify-content-center align-items-center">
          <span class="seat selected"></span> {{ __('Selected') }}
          <span class="seat unavailable"></span> {{ __('Not available') }}
        </div>
        <div class="text-end">
          <button class="theme-btn btn-lg close_slot_modal" id="buyNowSubmit">{{ __('Buy Now') }}:
            <span id="seat_price">{{ $basicInfo->base_currency_symbol }} 0.00</span>
          </button>
        </div>
      </div>

    </div>
  </div>
</div>

