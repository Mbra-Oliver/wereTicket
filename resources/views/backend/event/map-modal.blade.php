  <div class="modal fade" id="GoogleMapModal" tabindex="-1" role="dialog" aria-labelledby="GoogleMapModalCenterTitle"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
          <div class="modal-content">
              <div class="modal-header">
                  <h5 class="modal-title" id="GoogleMapModalLongTitle">{{ __('Google Map') }}</h5>
                  <div>
                      <button type="button" class="btn btn-secondary btn-xs"
                          data-dismiss="modal">{{ __('Choose') }}</button>
                      <button type="button" class="btn btn-danger btn-xs" data-dismiss="modal">X</button>
                  </div>
              </div>
              <div class="modal-body">
                  <div id="map"></div>
              </div>
          </div>
      </div>
  </div>
