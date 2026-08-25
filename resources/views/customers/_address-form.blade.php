@php
    $address = $address ?? null;
    $mapId = $address?->id ?? 'new';
    $lat = old('latitude', $address?->latitude);
    $lng = old('longitude', $address?->longitude);
@endphp
<div class="mb-2">
    <label class="form-label small">Label (optional)</label>
    <input type="text" name="label" value="{{ old('label', $address?->label) }}" class="form-control form-control-sm" placeholder="Home, Office, etc.">
</div>
<div class="mb-2">
    <label class="form-label small">Address Line 1</label>
    <input type="text" name="address1" id="address1-{{ $mapId }}" value="{{ old('address1', $address?->address1) }}" class="form-control form-control-sm" autocomplete="off" required>
</div>
<div class="mb-2">
    <label class="form-label small">Address Line 2 (optional)</label>
    <input type="text" name="address2" value="{{ old('address2', $address?->address2) }}" class="form-control form-control-sm">
</div>
<div class="row g-2 mb-2">
    <div class="col-6">
        <label class="form-label small">City</label>
        <select name="city" class="form-select form-select-sm" required>
            @foreach (['Lahore'] as $cityOption)
                <option value="{{ $cityOption }}" @selected(old('city', $address?->city) === $cityOption)>{{ $cityOption }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6">
        <label class="form-label small">Country</label>
        <input type="text" name="country" value="{{ old('country', $address?->country ?? 'Pakistan') }}" class="form-control form-control-sm" required>
    </div>
</div>
<div class="mb-2">
    <label class="form-label small">Phone (optional override)</label>
    <input type="text" name="phone" value="{{ old('phone', $address?->phone) }}" class="form-control form-control-sm">
</div>

@if (config('services.google_maps.key'))
    <div class="mb-2">
        <label class="form-label small">Pin Location on Map</label>
        <div id="map-{{ $mapId }}" style="height: 220px;" class="border rounded"></div>
        <div class="form-text small">Search the address above or drag the pin to set the exact delivery location.</div>
    </div>

    @push('scripts')
        <script>
            (function () {
                var mapId = {{ Js::from((string) $mapId) }};
                var initialLat = {{ Js::from($lat) }};
                var initialLng = {{ Js::from($lng) }};
                var LAHORE = {lat: 31.5204, lng: 74.3587};

                function init() {
                    var mapEl = document.getElementById('map-' + mapId);
                    if (!mapEl || mapEl.dataset.initialized) {
                        return;
                    }
                    mapEl.dataset.initialized = '1';

                    var hasCoords = initialLat !== null && initialLng !== null;
                    var center = hasCoords ? {lat: initialLat, lng: initialLng} : LAHORE;

                    var map = new google.maps.Map(mapEl, {center: center, zoom: hasCoords ? 16 : 12});
                    var marker = new google.maps.Marker({position: center, map: map, draggable: true});

                    var latInput = document.getElementById('latitude-' + mapId);
                    var lngInput = document.getElementById('longitude-' + mapId);

                    marker.addListener('dragend', function () {
                        var pos = marker.getPosition();
                        latInput.value = pos.lat();
                        lngInput.value = pos.lng();
                    });

                    var addressInput = document.getElementById('address1-' + mapId);
                    if (addressInput && google.maps.places) {
                        var autocomplete = new google.maps.places.Autocomplete(addressInput, {
                            componentRestrictions: {country: 'pk'},
                            fields: ['geometry', 'formatted_address'],
                        });
                        autocomplete.bindTo('bounds', map);

                        autocomplete.addListener('place_changed', function () {
                            var place = autocomplete.getPlace();
                            if (!place.geometry || !place.geometry.location) {
                                return;
                            }
                            map.setCenter(place.geometry.location);
                            map.setZoom(16);
                            marker.setPosition(place.geometry.location);
                            latInput.value = place.geometry.location.lat();
                            lngInput.value = place.geometry.location.lng();
                        });
                    }
                }

                // Inside a Bootstrap modal, the map container is display:none
                // until the modal is actually shown — Google Maps can't size
                // itself correctly before then, so initialization is deferred
                // to shown.bs.modal rather than running at page load.
                document.addEventListener('DOMContentLoaded', function () {
                    var mapEl = document.getElementById('map-' + mapId);
                    var modal = mapEl ? mapEl.closest('.modal') : null;

                    if (modal) {
                        modal.addEventListener('shown.bs.modal', init);
                    } else {
                        init();
                    }
                });
            })();
        </script>
    @endpush
@else
    <div class="alert alert-warning small py-2 px-2 mb-0">
        Map picker unavailable — set <code>GOOGLE_MAPS_API_KEY</code> in <code>.env</code> to enable it. The address can still be saved without coordinates.
    </div>
@endif
<input type="hidden" name="latitude" id="latitude-{{ $mapId }}" value="{{ $lat }}">
<input type="hidden" name="longitude" id="longitude-{{ $mapId }}" value="{{ $lng }}">
