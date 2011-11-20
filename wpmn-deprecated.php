<?php

/**
 * WP Multi Network Deprecated Functions
 *
 * @package WPMN
 * @subpackage Deprecated
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

function wpmn_network_exists( $site_id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'network_exists()' );
	network_exists( $site_id );
}

function wpmn_switch_to_network( $new_network ) {
	_deprecated_function( __FUNCTION__, '1.3', 'switch_to_network()' );
	switch_to_network( $new_network );
}

function wpmn_restore_current_network() {
	_deprecated_function( __FUNCTION__, '1.3', 'restore_current_network()' );
	restore_current_network();
}

function wpmn_add_network( $domain, $path, $site_name = NULL, $clone_network = NULL, $options_to_clone = NULL ) {
	_deprecated_function( __FUNCTION__, '1.3', 'add_network()' );
	add_network( $domain, $path, $site_name, $clone_network, $options_to_clone );
}

function wpmn_update_network( $id, $domain, $path = '' ) {
	_deprecated_function( __FUNCTION__, '1.3', 'update_network()' );
	update_network( $id, $domain, $path );
}

function wpmn_delete_network( $id, $delete_blogs = false ) {
	_deprecated_function( __FUNCTION__, '1.3', 'delete_network()' );
	delete_network( $id, $delete_blogs );
}

function wpmn_move_site( $site_id, $new_network_id ) {
	_deprecated_function( __FUNCTION__, '1.3', 'move_site()' );
	move_site( $site_id, $new_network_id );
}

?>
