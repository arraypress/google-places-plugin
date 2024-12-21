<?php
/**
 * Google Places API Response Class
 *
 * @package     ArrayPress\Google\Places
 * @copyright   Copyright (c) 2024, ArrayPress Limited
 * @license     GPL2+
 * @version     1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\Google\Places;

/**
 * Class Response
 *
 * Handles and structures the response data from Google Places API.
 *
 * @package ArrayPress\Google\Places
 */
class Response {

	/**
	 * Raw response data from the API
	 *
	 * @var array
	 */
	private array $data;

	/**
	 * Array of day names in order matching the API's numeric representation
	 *
	 * @var array
	 */
	private const DAYS = [
		'Sunday',
		'Monday',
		'Tuesday',
		'Wednesday',
		'Thursday',
		'Friday',
		'Saturday'
	];

	/**
	 * Initialize the response object
	 *
	 * @param array $data Raw response data from Google Places API
	 */
	public function __construct( array $data ) {
		$this->data = $data;
	}

	/**
	 * Get raw data array
	 *
	 * @return array
	 */
	public function get_all(): array {
		return $this->data;
	}

	/**
	 * Get the first result from the response
	 *
	 * @return array|null
	 */
	public function get_first_result(): ?array {
		return $this->data['results'][0] ?? null;
	}

	/**
	 * Get all results from the response
	 *
	 * @return array
	 */
	public function get_results(): array {
		return $this->data['results'] ?? [];
	}

	/**
	 * Get formatted address
	 *
	 * @return string|null
	 */
	public function get_formatted_address(): ?string {
		$result = $this->get_first_result();

		return $result['formatted_address'] ?? null;
	}

	/**
	 * Get coordinates
	 *
	 * @return array|null Array with 'lat' and 'lng' or null if not available
	 */
	public function get_coordinates(): ?array {
		$result = $this->get_first_result();
		if ( isset( $result['geometry']['location'] ) ) {
			return [
				'latitude'  => $result['geometry']['location']['lat'],
				'longitude' => $result['geometry']['location']['lng']
			];
		}

		return null;
	}

	/**
	 * Get place ID
	 *
	 * @return string|null
	 */
	public function get_place_id(): ?string {
		$result = $this->get_first_result();

		return $result['place_id'] ?? null;
	}

	/**
	 * Get address components
	 *
	 * @return array
	 */
	public function get_address_components(): array {
		$result = $this->get_first_result();

		return $result['address_components'] ?? [];
	}

	/**
	 * Get specific address component
	 *
	 * @param string $type The type of address component to retrieve
	 *
	 * @return string|null
	 */
	public function get_address_component( string $type ): ?string {
		$components = $this->get_address_components();
		foreach ( $components as $component ) {
			if ( in_array( $type, $component['types'] ) ) {
				return $component['long_name'];
			}
		}

		return null;
	}

	/**
	 * Get complete formatted address components
	 *
	 * @return array Structured address components
	 */
	public function get_structured_address(): array {
		return [
			'street_number'     => $this->get_street_number(),
			'street_name'       => $this->get_street_name(),
			'subpremise'        => $this->get_address_component( 'subpremise' ),
			'city'              => $this->get_city(),
			'state'             => $this->get_state(),
			'postal_code'       => $this->get_postal_code(),
			'country'           => $this->get_country(),
			'formatted_address' => $this->get_formatted_address()
		];
	}

	/**
	 * Get street number
	 *
	 * @return string|null
	 */
	public function get_street_number(): ?string {
		return $this->get_address_component( 'street_number' );
	}

	/**
	 * Get street name
	 *
	 * @return string|null
	 */
	public function get_street_name(): ?string {
		return $this->get_address_component( 'route' );
	}

	/**
	 * Get city/locality
	 *
	 * @return string|null
	 */
	public function get_city(): ?string {
		return $this->get_address_component( 'locality' );
	}

	/**
	 * Get state/province
	 *
	 * @return string|null
	 */
	public function get_state(): ?string {
		return $this->get_address_component( 'administrative_area_level_1' );
	}

	/**
	 * Get postal code
	 *
	 * @return string|null
	 */
	public function get_postal_code(): ?string {
		return $this->get_address_component( 'postal_code' );
	}

	/**
	 * Get country
	 *
	 * @return string|null
	 */
	public function get_country(): ?string {
		return $this->get_address_component( 'country' );
	}

	/**
	 * Get place types
	 *
	 * @return array
	 */
	public function get_types(): array {
		$result = $this->get_first_result();

		return $result['types'] ?? [];
	}

	/**
	 * Get viewport coordinates
	 *
	 * @return array|null Array with northeast and southwest bounds
	 */
	public function get_viewport(): ?array {
		$result = $this->get_first_result();

		return $result['geometry']['viewport'] ?? null;
	}

	/**
	 * Get place rating
	 *
	 * @return float|null
	 */
	public function get_rating(): ?float {
		$result = $this->get_first_result();

		return isset( $result['rating'] ) ? (float) $result['rating'] : null;
	}

	/**
	 * Get total user ratings
	 *
	 * @return int|null
	 */
	public function get_user_ratings_total(): ?int {
		$result = $this->get_first_result();

		return $result['user_ratings_total'] ?? null;
	}

	/**
	 * Get place photos
	 *
	 * @return array
	 */
	public function get_photos(): array {
		$result = $this->get_first_result();

		return $result['photos'] ?? [];
	}

	/**
	 * Get place website
	 *
	 * @return string|null
	 */
	public function get_website(): ?string {
		$result = $this->get_first_result();

		return $result['website'] ?? null;
	}

	/**
	 * Get place phone number
	 *
	 * @return string|null
	 */
	public function get_phone_number(): ?string {
		$result = $this->get_first_result();

		return $result['formatted_phone_number'] ?? null;
	}

	/**
	 * Get formatted phone number
	 *
	 * @return string|null Formatted phone number or null if not available
	 */
	public function get_formatted_phone_number(): ?string {
		$phone_number = $this->get_phone_number();
		if ( ! $phone_number ) {
			return null;
		}

		// Remove everything except digits
		$phone_number = preg_replace( '/[^0-9]/', '', $phone_number );

		if ( strlen( $phone_number ) === 10 ) {
			return sprintf(
				'(%s) %s-%s',
				substr( $phone_number, 0, 3 ),
				substr( $phone_number, 3, 3 ),
				substr( $phone_number, 6 )
			);
		}

		return $phone_number;
	}

	/**
	 * Get place international phone number
	 *
	 * @return string|null
	 */
	public function get_international_phone_number(): ?string {
		$result = $this->get_first_result();

		return $result['international_phone_number'] ?? null;
	}

	/**
	 * Get opening hours
	 *
	 * @return array|null
	 */
	public function get_opening_hours(): ?array {
		$result = $this->get_first_result();

		return $result['opening_hours'] ?? null;
	}

	/**
	 * Check if place is currently open
	 *
	 * @return bool|null
	 */
	public function is_open_now(): ?bool {
		$hours = $this->get_opening_hours();

		return $hours['open_now'] ?? null;
	}

	/**
	 * Get formatted opening hours as HTML
	 *
	 * @return string HTML formatted opening hours
	 */
	public function get_opening_hours_html(): string {
		$hours = $this->get_formatted_opening_hours();
		if ( ! $hours ) {
			return '';
		}

		$html = '<ul class="place-opening-hours">';
		foreach ( $hours as $day => $times ) {
			$html .= sprintf(
				'<li><strong>%s:</strong> %s</li>',
				esc_html( $day ),
				$times['is_24_7']
					? esc_html__( 'Open 24 hours', 'arraypress' )
					: sprintf(
					esc_html__( '%s - %s', 'arraypress' ),
					esc_html( $times['open'] ),
					esc_html( $times['close'] )
				)
			);
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Get place price level
	 *
	 * @return int|null Price level from 0 (free) to 4 (very expensive)
	 */
	public function get_price_level(): ?int {
		$result = $this->get_first_result();

		return $result['price_level'] ?? null;
	}

	/**
	 * Get formatted price level
	 *
	 * @return string|null Human-readable price level or null if not available
	 */
	public function get_formatted_price_level(): ?string {
		$price_level = $this->get_price_level();
		if ( $price_level === null ) {
			return null;
		}

		switch ( $price_level ) {
			case 0:
				return __( 'Free', 'arraypress' );
			case 1:
				return __( 'Inexpensive', 'arraypress' );
			case 2:
				return __( 'Moderate', 'arraypress' );
			case 3:
				return __( 'Expensive', 'arraypress' );
			case 4:
				return __( 'Very Expensive', 'arraypress' );
			default:
				return __( 'Unknown', 'arraypress' );
		}
	}

	/**
	 * Get place business status
	 *
	 * @return string|null
	 */
	public function get_business_status(): ?string {
		$result = $this->get_first_result();

		return $result['business_status'] ?? null;
	}

	/**
	 * Get formatted business status
	 *
	 * @return string Human-readable business status
	 */
	public function get_formatted_business_status(): string {
		$status = $this->get_business_status();
		if ( ! $status ) {
			return __( 'Unknown', 'arraypress' );
		}

		switch ( $status ) {
			case 'OPERATIONAL':
				return __( 'Open', 'arraypress' );
			case 'CLOSED_TEMPORARILY':
				return __( 'Temporarily Closed', 'arraypress' );
			case 'CLOSED_PERMANENTLY':
				return __( 'Permanently Closed', 'arraypress' );
			default:
				return __( 'Unknown', 'arraypress' );
		}
	}

	/**
	 * Get editorial summary
	 *
	 * @return string|null
	 */
	public function get_editorial_summary(): ?string {
		$result = $this->get_first_result();

		return $result['editorial_summary']['overview'] ?? null;
	}

	/**
	 * Get place reviews
	 *
	 * @return array
	 */
	public function get_reviews(): array {
		$result = $this->get_first_result();

		return $result['reviews'] ?? [];
	}

	/**
	 * Get wheelchair accessible entrance status
	 *
	 * @return bool|null
	 */
	public function has_wheelchair_accessible_entrance(): ?bool {
		$result = $this->get_first_result();

		return $result['wheelchair_accessible_entrance'] ?? null;
	}

	/**
	 * Get the place icon URL
	 *
	 * @return string|null
	 */
	public function get_icon_url(): ?string {
		$result = $this->get_first_result();

		return $result['icon'] ?? null;
	}

	/**
	 * Get the place icon background color
	 *
	 * @return string|null
	 */
	public function get_icon_background_color(): ?string {
		$result = $this->get_first_result();

		return $result['icon_background_color'] ?? null;
	}

	/**
	 * Get the place icon mask base URI
	 *
	 * @return string|null
	 */
	public function get_icon_mask_base_uri(): ?string {
		$result = $this->get_first_result();

		return $result['icon_mask_base_uri'] ?? null;
	}

	/**
	 * Get formatted opening hours
	 *
	 * @return array|null Formatted opening hours array or null if not available
	 */
	public function get_formatted_opening_hours(): ?array {
		$hours = $this->get_opening_hours();
		if ( ! $hours || ! isset( $hours['periods'] ) ) {
			return null;
		}

		$formatted = [];
		foreach ( $hours['periods'] as $period ) {
			$day = self::DAYS[ $period['open']['day'] ];

			$open_time = substr( $period['open']['time'], 0, 2 ) . ':' .
			             substr( $period['open']['time'], 2 );

			$close_time = isset( $period['close'] ) ?
				substr( $period['close']['time'], 0, 2 ) . ':' .
				substr( $period['close']['time'], 2 ) :
				'24:00';

			$formatted[ $day ] = [
				'open'    => $open_time,
				'close'   => $close_time,
				'is_24_7' => ! isset( $period['close'] )
			];
		}

		return $formatted;
	}

	/**
	 * Get the place's current opening period
	 *
	 * @return array|null Current opening period
	 */
	public function get_current_opening_period(): ?array {
		$hours = $this->get_opening_hours();
		if ( ! $hours || ! isset( $hours['periods'] ) ) {
			return null;
		}

		$current_time = current_time( 'timestamp' );
		$current_day  = (int) date( 'w', $current_time );
		$current_time = (int) date( 'Hi', $current_time );

		foreach ( $hours['periods'] as $period ) {
			if ( $period['open']['day'] === $current_day ) {
				$open_time  = (int) $period['open']['time'];
				$close_time = isset( $period['close'] ) ? (int) $period['close']['time'] : null;

				if ( $close_time === null || ( $current_time >= $open_time && $current_time <= $close_time ) ) {
					return [
						'open_time'  => $period['open']['time'],
						'close_time' => $close_time ? $period['close']['time'] : null,
						'is_24_7'    => $close_time === null
					];
				}
			}
		}

		return null;
	}

	/**
	 * Check if there are more results available
	 *
	 * @return bool
	 */
	public function has_more_results(): bool {
		return isset( $this->data['next_page_token'] );
	}

	/**
	 * Get the API response status
	 *
	 * @return string
	 */
	public function get_status(): string {
		return $this->data['status'] ?? '';
	}

	/**
	 * Get prediction description (for autocomplete responses)
	 *
	 * @return string|null
	 */
	public function get_description(): ?string {
		$result = $this->get_first_result();

		return $result['description'] ?? null;
	}

	/**
	 * Get structured formatting data (for autocomplete responses)
	 *
	 * @return array|null
	 */
	public function get_structured_formatting(): ?array {
		$result = $this->get_first_result();

		return $result['structured_formatting'] ?? null;
	}

	/**
	 * Get distance from a given point (if available in response)
	 *
	 * @return float|null Distance in meters
	 */
	public function get_distance(): ?float {
		$result = $this->get_first_result();

		return isset( $result['distance'] ) ? (float) $result['distance'] : null;
	}

	/**
	 * Check if the place is permanently closed
	 *
	 * @return bool
	 */
	public function is_permanently_closed(): bool {
		$result = $this->get_first_result();

		return $result['permanently_closed'] ?? false;
	}

	/**
	 * Get place URL
	 *
	 * @return string|null Google Maps URL for the place
	 */
	public function get_place_url(): ?string {
		$result = $this->get_first_result();

		return $result['url'] ?? null;
	}

	/**
	 * Get place compound code (plus code)
	 *
	 * @return string|null
	 */
	public function get_plus_code(): ?string {
		$result = $this->get_first_result();

		return $result['plus_code']['compound_code'] ?? null;
	}

	/**
	 * Get place UTC offset
	 *
	 * @return int|null UTC offset in minutes
	 */
	public function get_utc_offset(): ?int {
		$result = $this->get_first_result();

		return $result['utc_offset'] ?? null;
	}

	/**
	 * Get place amenities
	 *
	 * @return array Array of available amenities
	 */
	public function get_amenities(): array {
		$result    = $this->get_first_result();
		$amenities = [];

		// Common amenity fields to check
		$amenity_fields = [
			'serves_breakfast'               => __( 'Serves Breakfast', 'arraypress' ),
			'serves_lunch'                   => __( 'Serves Lunch', 'arraypress' ),
			'serves_dinner'                  => __( 'Serves Dinner', 'arraypress' ),
			'serves_beer'                    => __( 'Serves Beer', 'arraypress' ),
			'serves_wine'                    => __( 'Serves Wine', 'arraypress' ),
			'serves_vegetarian_food'         => __( 'Serves Vegetarian Food', 'arraypress' ),
			'takeout'                        => __( 'Takeout Available', 'arraypress' ),
			'delivery'                       => __( 'Delivery Available', 'arraypress' ),
			'dine_in'                        => __( 'Dine-in Available', 'arraypress' ),
			'reservable'                     => __( 'Reservations Accepted', 'arraypress' ),
			'wheelchair_accessible_entrance' => __( 'Wheelchair Accessible', 'arraypress' ),
			'outdoor_seating'                => __( 'Outdoor Seating', 'arraypress' ),
			'parking'                        => __( 'Parking Available', 'arraypress' )
		];

		foreach ( $amenity_fields as $field => $label ) {
			if ( isset( $result[ $field ] ) && $result[ $field ] === true ) {
				$amenities[ $field ] = $label;
			}
		}

		return $amenities;
	}

	/**
	 * Get popular times data if available
	 *
	 * @return array|null Popular times data
	 */
	public function get_popular_times(): ?array {
		$result = $this->get_first_result();

		return $result['popular_times'] ?? null;
	}

}