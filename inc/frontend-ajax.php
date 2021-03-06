<?php
/**
 * Front-end AJAX Functionalities
 * // @echo HEADER
 */

/*******************************************************
  Start AJAX From Here
*******************************************************/

/**
 * AJAX function for all like/unlike process
 *
 * @author       	Alimir
 * @since           1.0
 * @return			String
 */
function wp_ulike_process(){
	// Global variables
	global $wp_ulike_class;

	$post_ID     = isset( $_POST['id'] ) ? esc_html( $_POST['id'] ) : NULL;
	$post_type   = isset( $_POST['type'] ) ? esc_html( $_POST['type'] ) : NULL;
	$nonce_token = isset( $_POST['nonce'] ) ? esc_html( $_POST['nonce'] ) : NULL;
	$factor      = isset( $_POST['factor'] ) ? esc_html( $_POST['factor'] ) : NULL;
	$template    = isset( $_POST['template'] ) ? esc_html( $_POST['template'] ) : 'wpulike-default';
	$response    = array();
	$status      = 0;

	if( $post_ID == null || ( ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) && wp_ulike_is_cache_exist() ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract post type settings
	extract( $get_settings );

	// Get options
	$options = wp_ulike_get_option( $setting );

	$args = apply_filters( 'wp_ulike_ajax_process_atts', array(
			"id"                   => $post_ID,
			"method"               => $post_type,
			"type"                 => 'process',
			"table"                => $table,
			"column"               => $column,
			"key"                  => $key,
			"slug"                 => $slug,
			"cookie"               => $cookie,
			"factor"               => $factor,
			"style"                => $template,
			"logging_method"       => isset( $options['logging_method'] ) ? $options['logging_method'] : 'by_username',
			"only_logged_in_users" => isset( $options['enable_only_logged_in_users'] ) ? $options['enable_only_logged_in_users'] : 0,
			"logged_out_action"    => isset( $options['logged_out_display_type'] ) ? $options['logged_out_display_type'] : 'button'
		), $post_ID, $get_settings
	);

	if( wp_ulike_is_true( $args['only_logged_in_users'] ) && ! is_user_logged_in() ) {
		$response = array(
			'message'     => wp_ulike_get_option( 'login_required_notice',  __( 'You Should Login To Submit Your Like', WP_ULIKE_SLUG ) ),
			'btnText'     => NULL,
			'messageType' => 'info',
			'status'      => 4,
			'data'        => NULL
		);
	} else{
		if( ! $wp_ulike_class->has_permission( $args ) ){
			$response = array(
				'message'     => wp_ulike_get_option( 'already_registered_notice', __( 'You have already registered a vote.', WP_ULIKE_SLUG ) ),
				'btnText'     => NULL,
				'messageType' => 'warning',
				'status'      => 5,
				'data'        => NULL
			);
		} else {
			$counter = $wp_ulike_class->wp_get_ulike( $args );
			$status  = $wp_ulike_class->get_status();

			switch ( $status ){
				case 1:
					$response = array(
						'message'     => wp_ulike_get_option( 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText'     => wp_ulike_get_button_text( 'like', $setting ),
						'messageType' => 'success',
						'status'      => $status,
						'data'        => apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter, $post_ID )
					);
					break;
				case 2:
					$response = array(
						'message'     => wp_ulike_get_option( 'unlike_notice', __( 'Sorry! You unliked this.', WP_ULIKE_SLUG ) ),
						'btnText'     => wp_ulike_get_button_text( 'like', $setting ),
						'messageType' => 'error',
						'status'      => $status,
						'data'        => apply_filters( 'wp_ulike_respond_for_unliked_data', $counter, $post_ID )
					);
					break;
				case 3:
					$response = array(
						'message'     => wp_ulike_get_option( 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText'     => wp_ulike_get_button_text( 'unlike', $setting ),
						'messageType' => 'success',
						'status'      => $status,
						'data'        => apply_filters( 'wp_ulike_respond_for_liked_data', $counter, $post_ID )
					);
					break;
				case 4:
					$response = array(
						'message'     => wp_ulike_get_option( 'like_notice', __( 'Thanks! You Liked This.', WP_ULIKE_SLUG ) ),
						'btnText'     => wp_ulike_get_button_text( 'like', $setting ),
						'messageType' => 'success',
						'status'      => $status,
						'data'        => apply_filters( 'wp_ulike_respond_for_not_liked_data', $counter, $post_ID )
					);
					break;
			}
		}
	}

	wp_send_json_success( apply_filters( 'wp_ulike_ajax_respond', $response, $post_ID, $status, $args ) );
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_process'			, 'wp_ulike_process' );
add_action( 'wp_ajax_nopriv_wp_ulike_process'	, 'wp_ulike_process' );


/**
 * AJAX function for all like/unlike process
 *
 * @author       	Alimir
 * @since           1.0
 * @return			String
 */
function wp_ulike_get_likers(){

	$post_ID          = isset( $_POST['id'] ) ? esc_html( $_POST['id'] ) : NULL;
	$post_type        = isset( $_POST['type'] ) ? esc_html( $_POST['type'] ) : NULL;
	$nonce_token      = isset( $_POST['nonce'] ) ? esc_html( $_POST['nonce'] ) : NULL;
	$is_refresh       = isset( $_POST['refresh'] ) ? esc_html( $_POST['refresh'] ) : false;
	$display_likers   = isset( $_POST['displayLikers'] ) ? esc_html( $_POST['displayLikers'] ) : false;
	$disable_pophover = isset( $_POST['disablePophover'] ) ? esc_html( $_POST['disablePophover'] ) : false;

	// Check security nonce field
	if( $post_ID == null || ( ! wp_verify_nonce( $nonce_token, $post_type . $post_ID ) && wp_ulike_is_cache_exist() ) ) {
		wp_send_json_error( __( 'Error: Something Wrong Happened!', WP_ULIKE_SLUG ) );
	}

	// If likers box has been disabled
	if ( ! $display_likers ) {
		wp_send_json_error( __( 'Notice: The likers box is not activated!', WP_ULIKE_SLUG ) );
	}

	// Don't refresh likers data, when user is not logged in.
	if( $is_refresh && ! is_user_logged_in() ) {
		wp_send_json_error( __( 'Notice: The likers box is refreshed only for logged in users!', WP_ULIKE_SLUG ) );
	}

	// Get post type settings
	$get_settings = wp_ulike_get_post_settings_by_type( $post_type );

	// If method not exist, then return error message
	if( empty( $get_settings ) ) {
		wp_send_json_error( __( 'Error: This Method Is Not Exist!', WP_ULIKE_SLUG ) );
	}

	// Extract settings array
	extract( $get_settings );

	// Add specific class name with popover checkup
	$class_names = wp_ulike_is_true( $disable_pophover ) ? 'wp_ulike_likers_wrapper wp_ulike_display_inline' : 'wp_ulike_likers_wrapper';
	$users_list  = wp_ulike_get_likers_template( $table, $column, $post_ID, $setting );

	wp_send_json_success( array( 'template' => $users_list, 'class' => $class_names ) );
}
//	wp_ajax hooks for the custom AJAX requests
add_action( 'wp_ajax_wp_ulike_get_likers'		 , 'wp_ulike_get_likers' );
add_action( 'wp_ajax_nopriv_wp_ulike_get_likers' , 'wp_ulike_get_likers' );