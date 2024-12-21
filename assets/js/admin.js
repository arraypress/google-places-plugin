/**
 * Google Places API Tester Admin JavaScript
 */
(function ($) {
    'use strict';

    // Form references
    const $searchForm = $('#places-search-form');
    const $detailsForm = $('#places-details-form');
    const $nearbyForm = $('#places-nearby-form');
    const $autocompleteForm = $('#places-autocomplete-form');
    const $geocodeForm = $('#places-geocode-form');
    const $settingsForm = $('#places-settings-form');

    /**
     * Initialize the admin interface
     */
    function init() {
        bindEvents();
        setupFormValidation();
        setupParameterToggles();
        setupPhotoHandling();
    }

    /**
     * Bind event listeners
     */
    function bindEvents() {
        // Form submissions
        $searchForm.on('submit', handlePlaceSearch);
        $detailsForm.on('submit', handlePlaceDetails);
        $nearbyForm.on('submit', handleNearbySearch);
        $autocompleteForm.on('submit', handleAutocomplete);
        $geocodeForm.on('submit', handleGeocode);
        $settingsForm.on('submit', handleSettingsSave);

        // Clear results on input change
        $('.places-form input, .places-form select').on('change', function () {
            $(this).closest('form').find('.api-results').slideUp();
        });

        // Handle place selection from results
        $(document).on('click', '.place-details-link', function (e) {
            e.preventDefault();
            const placeId = $(this).data('place-id');
            $('#place-id').val(placeId).trigger('change');
            $detailsForm.submit();
        });

        // Cache clearing
        $('#clear-cache').on('click', handleCacheClear);
    }

    /**
     * Set up form validation
     */
    function setupFormValidation() {
        $('.places-form input[required]').on('invalid', function () {
            $(this).addClass('validation-error');
        }).on('input', function () {
            $(this).removeClass('validation-error');
        });
    }

    /**
     * Set up parameter toggles
     */
    function setupParameterToggles() {
        $('.parameter-toggle').on('change', function () {
            const targetId = $(this).data('target');
            $(`#${targetId}`).slideToggle(200);
        });
    }

    /**
     * Set up photo handling
     */
    function setupPhotoHandling() {
        $(document).on('click', '.photo-preview img', function () {
            const src = $(this).attr('src');
            showPhotoModal(src);
        });
    }

    /**
     * Handle Place Search
     */
    async function handlePlaceSearch(e) {
        e.preventDefault();
        const $results = $('#search-results');
        const query = $('#search-query').val().trim();
        const type = $('#search-type').val();

        if (!query) {
            showError($results, 'Please enter a search query');
            return;
        }

        showLoading($results);

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_search',
                    nonce: placesApiTester.nonce,
                    query: query,
                    type: type
                }
            });

            if (response.success) {
                renderSearchResults($results, response.data);
            } else {
                showError($results, response.data.message);
            }
        } catch (error) {
            showError($results, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Place Details
     */
    async function handlePlaceDetails(e) {
        e.preventDefault();
        const $results = $('#details-results');
        const placeId = $('#place-id').val().trim();

        if (!placeId) {
            showError($results, 'Please enter a Place ID');
            return;
        }

        showLoading($results);

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_details',
                    nonce: placesApiTester.nonce,
                    place_id: placeId
                }
            });

            if (response.success) {
                renderPlaceDetails($results, response.data);
            } else {
                showError($results, response.data.message);
            }
        } catch (error) {
            showError($results, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Nearby Search
     */
    async function handleNearbySearch(e) {
        e.preventDefault();
        const $results = $('#nearby-results');
        const lat = parseFloat($('#nearby-lat').val());
        const lng = parseFloat($('#nearby-lng').val());
        const radius = parseInt($('#nearby-radius').val());

        if (isNaN(lat) || isNaN(lng)) {
            showError($results, 'Please enter valid coordinates');
            return;
        }

        showLoading($results);

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_nearby',
                    nonce: placesApiTester.nonce,
                    lat: lat,
                    lng: lng,
                    radius: radius
                }
            });

            if (response.success) {
                renderNearbyResults($results, response.data);
            } else {
                showError($results, response.data.message);
            }
        } catch (error) {
            showError($results, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Autocomplete
     */
    async function handleAutocomplete(e) {
        e.preventDefault();
        const $results = $('#autocomplete-results');
        const input = $('#autocomplete-input').val().trim();
        const types = $('#autocomplete-types').val();

        if (!input) {
            showError($results, 'Please enter text to search');
            return;
        }

        showLoading($results);

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_autocomplete',
                    nonce: placesApiTester.nonce,
                    input: input,
                    types: types
                }
            });

            if (response.success) {
                renderAutocompleteResults($results, response.data);
            } else {
                showError($results, response.data.message);
            }
        } catch (error) {
            showError($results, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Geocoding
     */
    async function handleGeocode(e) {
        e.preventDefault();
        const $results = $('#geocode-results');
        const address = $('#geocode-address').val().trim();

        if (!address) {
            showError($results, 'Please enter an address');
            return;
        }

        showLoading($results);

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_geocode',
                    nonce: placesApiTester.nonce,
                    address: address
                }
            });

            if (response.success) {
                renderGeocodeResults($results, response.data);
            } else {
                showError($results, response.data.message);
            }
        } catch (error) {
            showError($results, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Settings Save
     */
    async function handleSettingsSave(e) {
        e.preventDefault();
        const $form = $(this);
        const $message = $('#settings-message');

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: new FormData($form[0]),
                processData: false,
                contentType: false
            });

            if (response.success) {
                showSuccess($message, 'Settings saved successfully');
            } else {
                showError($message, response.data.message);
            }
        } catch (error) {
            showError($message, `Error: ${error.message}`);
        }
    }

    /**
     * Handle Cache Clear
     */
    async function handleCacheClear(e) {
        e.preventDefault();
        const $message = $('#cache-message');

        try {
            const response = await $.ajax({
                url: placesApiTester.ajaxurl,
                type: 'POST',
                data: {
                    action: 'places_clear_cache',
                    nonce: placesApiTester.nonce
                }
            });

            if (response.success) {
                showSuccess($message, 'Cache cleared successfully');
            } else {
                showError($message, response.data.message);
            }
        } catch (error) {
            showError($message, `Error: ${error.message}`);
        }
    }

    /**
     * Render Functions
     */
    function renderSearchResults($container, data) {
        const results = data.get_results();
        const $content = $('<div>');

        if (!results.length) {
            $content.html('<p class="no-results">No results found</p>');
        } else {
            results.forEach(place => {
                $content.append(createPlaceCard(place));
            });
        }

        showResults($container, $content);
    }

    function renderPlaceDetails($container, data) {
        const place = data.get_first_result();
        const $content = $('<div>', {class: 'place-details'});

        if (!place) {
            $content.html('<p class="no-results">No details found</p>');
        } else {
            // Basic Info
            $content.append(createBasicInfoSection(place));

            // Contact Info
            if (place.formatted_phone_number || place.website) {
                $content.append(createContactSection(place));
            }

            // Address Components
            $content.append(createAddressSection(place));

            // Hours
            if (place.opening_hours) {
                $content.append(createHoursSection(place));
            }

            // Photos
            if (place.photos && place.photos.length) {
                $content.append(createPhotosSection(place));
            }

            // Reviews
            if (place.reviews && place.reviews.length) {
                $content.append(createReviewsSection(place));
            }
        }

        showResults($container, $content);
    }

    function renderNearbyResults($container, data) {
        const results = data.get_results();
        const $content = $('<div>');

        if (!results.length) {
            $content.html('<p class="no-results">No nearby places found</p>');
        } else {
            results.forEach(place => {
                $content.append(createPlaceCard(place, true));
            });
        }

        showResults($container, $content);
    }

    function renderAutocompleteResults($container, data) {
        const predictions = data.get_results();
        const $content = $('<div>');

        if (!predictions.length) {
            $content.html('<p class="no-results">No predictions found</p>');
        } else {
            predictions.forEach(prediction => {
                $content.append(createPredictionCard(prediction));
            });
        }

        showResults($container, $content);
    }

    function renderGeocodeResults($container, data) {
        const result = data.get_first_result();
        const $content = $('<div>', {class: 'geocode-result'});

        if (!result) {
            $content.html('<p class="no-results">No results found</p>');
        } else {
            $content.append(createGeocodeResultCard(result));
        }

        showResults($container, $content);
    }

    /**
     * UI Helper Functions
     */
    function showLoading($container) {
        $container.html(`
            <div class="loading-indicator">
                <span class="spinner is-active"></span>
                <span class="loading-text">Loading...</span>
            </div>
        `).slideDown();
    }

    function showError($container, message) {
        $container.html(`
            <div class="error-message">
                <p>${message}</p>
            </div>
        `).slideDown();
    }

    function showSuccess($container, message) {
        $container.html(`
            <div class="success-message">
                <p>${message}</p>
            </div>
        `).slideDown();
    }

    function showResults($container, $content) {
        $container.empty().append($content).slideDown();
    }

    function showPhotoModal(src) {
        const $modal = $('<div>', {class: 'photo-modal'}).append(
            $('<div>', {class: 'photo-modal-content'}).append(
                $('<img>', {src: src}),
                $('<button>', {class: 'close-modal', text: '×'})
            )
        );

        $('body').append($modal);
        $modal.fadeIn();

        $modal.on('click', function (e) {
            if (e.target === this || $(e.target).hasClass('close-modal')) {
                $modal.fadeOut(function () {
                    $(this).remove();
                });
            }
        });
    }

    // Initialize on document ready
    $(document).ready(init);

})(jQuery);