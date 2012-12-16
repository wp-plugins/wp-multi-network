<?php

/**
 * WP Multi Network Admin
 *
 * @package WPMN
 * @subpackage Admin
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Main admin interface
 *
 * @since 1.3
 */
class WPMN_Admin {

	function __construct() {
		add_action( 'admin_head',         array( &$this, 'admin_head'         ) );
		add_action( 'admin_menu',		  array( &$this, 'admin_menu'		  ) );
		add_action( 'network_admin_menu', array( &$this, 'network_admin_menu' ) );

		add_filter( 'manage_sites_action_links', array( &$this, 'add_move_blog_link' ), 10, 3 );
		if( ! has_action( 'manage_sites_action_links' ) ) {
			add_action( 'wpmublogsaction',    array( &$this, 'assign_blogs_link'  ) );
		}
	}

	function admin_url() {
		$result = add_query_arg( array( 'page' => 'networks' ), esc_url( network_admin_url( 'admin.php' ) ) );
		return $result;
	}

	function admin_head() {
	?>

		<script type="text/javascript">
			jQuery(document).ready( function() {

				jQuery( '.if-js-closed' ).removeClass( 'if-js-closed' ).addClass( 'closed' );
				jQuery( '.postbox' ).children( 'h3' ).click( function() {
					if (jQuery( this.parentNode ).hasClass( 'closed' ) ) {
						jQuery( this.parentNode ).removeClass( 'closed' );
					} else {
						jQuery( this.parentNode ).addClass( 'closed' );
					}
				} );

				/** add field to signal javascript is enabled */
				jQuery(document.createElement('input'))
					.attr( 'type', 'hidden' )
					.attr( 'name', 'jsEnabled' )
					.attr( 'value', 'true' )
					.appendTo( '#site-assign-form' );

				/** Handle clicks to add/remove sites to/from selected list */
				jQuery( 'input[name=assign]' ).click( function() {		move( 'from', 'to' );	});
				jQuery( 'input[name=unassign]' ).click( function() {	move( 'to', 'from' );	});

				/** Select all sites in "selected" box when submitting */
				jQuery( '#site-assign-form' ).submit( function() {
					jQuery( '#to' ).children( 'option' ).attr( 'selected', true );
				});


			});

			function move( from, to ) {
				jQuery( '#' + from ).children( 'option:selected' ).each( function() {
					jQuery( '#' + to ).append( jQuery( this ).clone() );
					jQuery( this ).remove();
				});
			}

		</script>

	<?php
	}

	/**
	 * Add the Move action to Sites page on WP >= 3.1
	 */
	function add_move_blog_link( $actions, $cur_blog_id, $blog_name ) {
		$url = add_query_arg( array(
			'action'  => 'move',
			'blog_id' => (int) $cur_blog_id ),
			$this->admin_url()
		);
		$actions['move'] = '<a href="' . $url . '" class="edit">' . __( 'Move' ) . '</a>';
		return $actions;
	}

	/**
	 * Legacy - add a Move link on Sites page on WP < 3.1
	 */
	function assign_blogs_link( $cur_blog_id ) {
		$url = add_query_arg( array(
			'action'  => 'move',
			'blog_id' => (int) $cur_blog_id ),
			$this->admin_url()
		);
		echo '<a href="' . $url . '" class="edit">' . __( 'Move' ) . '</a>';
	}

	function admin_menu() {
		if( user_has_networks() ) {
			add_dashboard_page( __('My Networks'), __('My Networks'), 'manage_options', 'my-networks', array( &$this, 'my_networks_page' ) );
		}
	}

	function network_admin_menu() {
		$page = add_menu_page( __( 'Networks' ), __( 'Networks' ), 'manage_options', 'networks', array( &$this, 'networks_page' ), '', -1 );
		add_submenu_page( 'networks', __( 'All Networks' ), __( 'All Networks' ), 'manage_options', 'networks',        array( &$this, 'networks_page' ) );
		add_submenu_page( 'networks', __( 'Add New'      ), __( 'Add New'      ), 'manage_options', 'add-new-network', array( &$this, 'add_network_page' ) );

//		add_submenu_page( 'settings.php', __('Networks Settings'), __('Networks Settings'), 'manage_network_options', 'networks-settings', array( &$this, 'networks_settings_page' ) );

		require dirname(__FILE__) . '/includes/class-wp-ms-networks-list-table.php';
		add_filter( 'manage_' . $page . '-network' . '_columns', array( new WP_MS_Networks_List_Table(), 'get_columns' ), 0 );

	}

	/* Config Page */

	function feedback() {

		if ( isset( $_GET['updated'] ) ) : ?>

			<div id="message" class="updated fade"><p><?php _e( 'Options saved.' ) ?></p></div>

		<?php elseif ( isset( $_GET['added'] ) ) : ?>

			<div id="message" class="updated fade"><p><?php _e( 'Network created.' ); ?></p></div>

		<?php elseif ( isset( $_GET['deleted'] ) ) : ?>

			<div id="message" class="updated fade"><p><?php _e( 'Network(s) deleted.' ); ?></p></div>

		<?php endif;

	}

	function networks_page() {

		if ( !is_super_admin() )
			wp_die( __( 'You do not have permission to access this page.' ) );

		if ( isset( $_POST['update'] ) && isset( $_GET['id'] ) )
			$this->update_network_page();

		if ( isset( $_POST['delete'] ) && isset( $_GET['id'] ) )
			$this->delete_network_page();

		if ( isset( $_POST['delete_multiple'] ) && isset( $_POST['deleted_networks'] ) )
			$this->delete_multiple_network_page();

		if ( isset( $_POST['add'] ) && isset( $_POST['domain'] ) && isset( $_POST['path'] ) )
			$this->add_network_page();

		if ( isset( $_POST['move'] ) && isset( $_GET['blog_id'] ) )
			$this->move_site_page();

		if ( isset( $_POST['reassign'] ) && isset( $_GET['id'] ) )
			$this->reassign_site_page();

		$this->feedback(); ?>

		<div class="wrap" style="position: relative">

		<?php

			$action = isset( $_GET['action'] ) ? $_GET['action'] : '';

			switch ( $action ) {
				case 'move':
					$this->move_site_page();
					break;

				case 'assignblogs':
					$this->reassign_site_page();
					break;

				case 'deletenetwork':
					$this->delete_network_page();
					break;

				case 'editnetwork':
					$this->update_network_page();
					break;

				case 'delete_multinetworks':
					$this->delete_multiple_network_page();
					break;

				default:
					$this->all_networks();
					break;
			}

		?>

		</div>

		<?php
	}

	function all_networks() {
		$wp_list_table = new WP_MS_Networks_List_Table();
		$wp_list_table->prepare_items(); ?>

		<div class="wrap">
			<?php screen_icon( 'ms-admin' ); ?>
			<h2><?php _e( 'Networks' ) ?>

			<?php if ( current_user_can( 'manage_network_options' ) ) : ?>

				<a href="<?php echo add_query_arg( array('page' => 'add-new-network' ), $this->admin_url() ); ?>" class="add-new-h2"><?php echo esc_html_x( 'Add New', 'site' ); ?></a>

			<?php endif;

			if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] ) {
				printf( '<span class="subtitle">' . __( 'Search results for &#8220;%s&#8221;' ) . '</span>', esc_html( $_REQUEST['s'] ) );
			} ?>
			</h2>

			<form action="" method="get" id="domain-search">
				<?php $wp_list_table->search_box( __( 'Search Networks' ), 'networks' ); ?>
				<input type="hidden" name="action" value="domains" />
			</form>

			<form id="form-domain-list" action="admin.php?action=domains" method="post">
				<?php $wp_list_table->display(); ?>
			</form>
		</div>

		<?php
	}

	function add_network_page() {
		global $wpdb;

		// Strip off URL parameters
		$query_str = remove_query_arg( array(
			'action', 'id', 'updated', 'deleted'
		) ); ?>

		<div class="wrap">
			<?php screen_icon( 'ms-admin' ); ?>
			<h2><?php _e( 'Networks' ) ?></h2>

			<div id="col-container">
				<p><?php _e( 'A site will be created at the root of the new network' ); ?>.</p>
				<form method="POST" action="<?php echo add_query_arg( array( 'action' => 'addnetwork' ), $query_str ); ?>">
					<table class="form-table">
						<tr><th scope="row"><label for="newName"><?php _e( 'Network Name' ); ?>:</label></th><td><input type="text" name="name" id="newName" title="<?php _e( 'A friendly name for your new network' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newDom"><?php  _e( 'Domain' ); ?>:</label></th><td> http://<input type="text" name="domain" id="newDom" title="<?php _e( 'The domain for your new network' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newPath"><?php _e( 'Path' ); ?>:</label></th><td><input type="text" name="path" id="newPath" title="<?php _e( 'If you are unsure, put in /' ); ?>" /></td></tr>
						<tr><th scope="row"><label for="newSite"><?php _e( 'Site Name' ); ?>:</label></th><td><input type="text" name="newSite" id="newSite" title="<?php _e( 'The name for the new network\'s site.' ); ?>" /></td></tr>
					</table>
					<div class="metabox-holder meta-box-sortables" id="advanced_network_options">
						<div class="postbox if-js-closed">
							<div title="Click to toggle" class="handlediv"><br/></div>
							<h3><span><?php _e( 'Clone Network Options' ); ?></span></h3>
							<div class="inside">
								<table class="form-table">
									<tr>
										<th scope="row"><label for="cloneNetwork"><?php _e( 'Clone Network' ); ?>:</label></th>
											<?php $network_list = $wpdb->get_results( 'SELECT id, domain FROM ' . $wpdb->site, ARRAY_A ); ?>
										<td colspan="2">
											<select name="cloneNetwork" id="cloneNetwork">
												<option value="0"><?php _e( 'Do Not Clone' ); ?></option>
												<?php foreach ( $network_list as $network ) { ?>
												<option value="<?php echo $network['id'] ?>" <?php selected( $network['id'] ) ?>><?php echo $network['domain'] ?></option>
												<?php } ?>
											</select>
										</td>
									</tr>
									<tr>
										<?php
											$class                     = '';
											$all_network_options       = $wpdb->get_results( 'SELECT DISTINCT meta_key FROM ' . $wpdb->sitemeta );
											$known_networkmeta_options = network_options_to_copy();
											$known_networkmeta_options = apply_filters( 'manage_sitemeta_descriptions', $known_networkmeta_options );
										?>

										<td colspan="3">
											<table class="widefat">
												<thead>
													<tr>
														<th scope="col" class="check-column"></th>
														<th scope="col"><?php _e( 'Meta Value' ); ?></th>
														<th scope="col"><?php _e( 'Description' ); ?></th>
													</tr>
												</thead>
												<tbody>

													<?php foreach ( $all_network_options as $count => $option ) { ?>

														<tr class="<?php echo $class = ('alternate' == $class) ? '' : 'alternate'; ?>">
															<th scope="row" class="check-column"><input type="checkbox" id="option_<?php echo $count; ?>" name="options_to_clone[<?php echo $option->meta_key; ?>]"<?php echo (array_key_exists( $option->meta_key, network_options_to_copy() ) ? ' checked' : '' ); ?> /></th>
															<td><label for="option_<?php echo $count; ?>"><?php echo $option->meta_key; ?></label></td>
															<td><label for="option_<?php echo $count; ?>"><?php echo (array_key_exists( $option->meta_key, $known_networkmeta_options ) ? __( $known_networkmeta_options[$option->meta_key] ) : '' ); ?></label></td>
														</tr>

													<?php } ?>

												</tbody>
											</table>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>

					<input type="submit" class="button" name="add" value="<?php _e( 'Create Network' ); ?>" />

				</form>
			</div>
		</div>

		<?php
	}

	function move_site_page() {
		global $wpdb;

		if ( isset( $_POST['move'] ) && isset( $_GET['blog_id'] ) ) {
			if ( isset( $_POST['from'] ) && isset( $_POST['to'] ) ) {
				move_site( $_GET['blog_id'], $_POST['to'] );
				$_GET['updated'] = 'yes';
				$_GET['action']  = 'saved';
			}
		} else {
			if ( !isset( $_GET['blog_id'] ) ) {
				die( __( 'You must select a blog to move.' ) );
			}

			$site = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE blog_id = %d", (int)$_GET['blog_id'] ) );

			if ( !$site )
				die( __( 'Invalid blog id.' ) );

			$table_name = $wpdb->get_blog_prefix( $site->blog_id ) . "options";
			$details    = $wpdb->get_row( "SELECT * FROM {$table_name} WHERE option_name = 'blogname'" );

			if ( !$details )
				die( __( 'Invalid blog id.' ) );

			$sites = $wpdb->get_results( "SELECT * FROM {$wpdb->site}" );

			foreach ( $sites as $key => $network ) {
				if ( $network->id == $site->site_id ) {
					$myNetwork = $sites[$key];
				}
			} ?>

			<div class="wrap">
				<?php screen_icon( 'ms-admin' ); ?>
				<h2><?php _e( 'Networks' ) ?></h2>
				<h3><?php echo __( 'Moving' ) . ' ' . stripslashes( $details->option_value ); ?></h3>
				<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<table class="widefat">
						<thead>
							<tr>
								<th scope="col"><?php _e( 'From' ); ?>:</th>
								<th scope="col"><label for="to"><?php _e( 'To' ); ?>:</label></th>
							</tr>
						</thead>
						<tr>
							<td><?php echo $myNetwork->domain; ?></td>
							<td>
								<select name="to" id="to">
									<option value="0"><?php _e( 'Select a Network' ); ?></option>
				<?php
				foreach ( $sites as $network ) {
					if ( $network->id != $myNetwork->id )  : ?>
					<option value="<?php echo $network->id ?>"><?php echo $network->domain; ?></option>
					<?php
					endif;
				}
				?>
								</select>
							</td>
						</tr>
					</table>
					<br />
				<?php if ( has_action( 'add_move_blog_option' ) ) : ?>
						<table class="widefat">
							<thead>
								<tr scope="col"><th colspan="2"><?php _e( 'Options' ); ?>:</th></tr>
							</thead>
							<?php do_action( 'add_move_blog_option', $site->blog_id ); ?>
						</table>
						<br />
					<?php endif; ?>
					<div>
						<input type="hidden" name="from" value="<?php echo $site->site_id; ?>" />
						<input class="button" type="submit" name="move" value="<?php _e( 'Move Site' ); ?>" />
						<a class="button" href="./sites.php"><?php _e( 'Cancel' ); ?></a>
					</div>
				</form>
			</div>
			<?php
		}
	}

	function reassign_site_page() {
		global $wpdb;

		if ( isset( $_POST['reassign'] ) && isset( $_GET['id'] ) ) {
			/** Javascript enabled for client - check the 'to' box */
			if ( isset( $_POST['jsEnabled'] ) ) {
				if ( !isset( $_POST['to'] ) )
					die( __( 'No blogs selected.' ) );

				$sites = $_POST['to'];

				/** Javascript disabled for client - check the 'from' box */
			} else {
				if ( !isset( $_POST['from'] ) )
					die( __( 'No blogs seleceted.' ) );

				$sites = $_POST['from'];
			}

			$current_blogs = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE site_id = %d", (int) $_GET['id'] ) );

			foreach ( $sites as $site ) {
				move_site( $site, (int) $_GET['id'] );
			}

			/* true sync - move any unlisted blogs to 'zero' network */
			if ( ENABLE_NETWORK_ZERO ) {
				foreach ( $current_blogs as $current_blog ) {
					if ( !in_array( $current_blog->blog_id, $sites ) ) {
						move_site( $current_blog->blog_id, 0 );
					}
				}
			}

			$_GET['updated'] = 'yes';
			$_GET['action'] = 'saved';
		} else {

			// get network by id
			$network = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->site} WHERE id = %d", (int) $_GET['id'] ) );

			if ( !$network )
				die( __( 'Invalid network id.' ) );

			$sites = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs}" );
			if ( !$sites )
				die( __( 'Site table inaccessible.' ) );

			foreach ( $sites as $key => $site ) {
				$table_name = $wpdb->get_blog_prefix( $site->blog_id ) . "options";
				$site_name = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE option_name = %s", 'blogname' ) );

				if ( !$site_name )
					die( __( 'Invalid blog.' ) );

				$sites[$key]->name = stripslashes( $site_name->option_value );
			}

			?>
			<div class="wrap">
				<?php screen_icon( 'ms-admin' ); ?>
				<h2><?php _e( 'Networks' ) ?></h2>
				<h3><?php _e( 'Assign Sites to' ); ?>: http://<?php echo $network->domain . $network->path ?></h3>
				<noscript>
				<div id="message" class="updated"><p><?php _e( 'Select the blogs you want to assign to this network from the column at left, and click "Update Assignments."' ); ?></p></div>
				</noscript>
				<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
					<table class="widefat">
						<thead>
							<tr>
								<th><?php _e( 'Available' ); ?></th>
								<th style="width: 2em;"></th>
								<th><?php _e( 'Assigned' ); ?></th>
							</tr>
						</thead>
						<tr>
							<td>
								<select name="from[]" id="from" multiple style="height: auto; width: 98%;">
								<?php
								foreach ( $sites as $site ) {
									if ( $site->site_id != $network->id )
										echo '<option value="' . $site->blog_id . '">' . $site->name . ' (' . $site->domain . ')</option>';
								}
								?>
								</select>
							</td>
							<td>
								<input type="button" name="unassign" id="unassign" value="<<" /><br />
								<input type="button" name="assign" id="assign" value=">>" />
							</td>
							<td valign="top">
							<?php if ( ! ENABLE_NETWORK_ZERO ) { ?>
								<ul style="margin: 0; padding: 0; list-style-type: none;">
								<?php foreach ( $sites as $site ) { ?>
									<?php if ( $site->site_id == $network->id ) { ?>
									<li><?php echo $site->name . ' (' . $site->domain . ')'; ?></li>
									<?php } ?>
								<?php } ?>
								</ul>
							<?php } ?>
								<select name="to[]" id="to" multiple style="height: auto; width: 98%">
									<?php
									if ( ENABLE_NETWORK_ZERO ) {
										foreach ( $sites as $site ) {
											if ( $site->site_id == $network->id )
												echo '<option value="' . $site->blog_id . '">' . $site->name . ' (' . $site->domain . ')</option>';
										}
									}
									?>
								</select>
							</td>
						</tr>
					</table>
					<br class="clear" />
				<?php if ( has_action( 'add_move_blog_option' ) ) : ?>
						<table class="widefat">
							<thead>
								<tr scope="col"><th colspan="2"><?php _e( 'Options' ); ?>:</th></tr>
							</thead>
					<?php do_action( 'add_move_blog_option', $site->blog_id ); ?>
						</table>
						<br />
					<?php endif; ?>
					<input type="submit" name="reassign" value="<?php _e( 'Update Assigments' ); ?>" class="button" />
					<a href="<?php echo $this->admin_url(); ?>"><?php _e( 'Cancel' ); ?></a>
				</form>
			</div>
			<?php
		}
	}

	function add_network_handler() {
		global $wpdb;

		if ( isset( $_POST['add'] ) && isset( $_POST['domain'] ) && isset( $_POST['path'] ) ) {

			/** grab custom options to clone if set */
			if ( isset( $_POST['options_to_clone'] ) && is_array( $_POST['options_to_clone'] ) )
				$options_to_clone = array_keys( $_POST['options_to_clone'] );
			else
				$options_to_clone = network_options_to_copy();

			$result = add_network(
				$_POST['domain'], $_POST['path'], ( isset( $_POST['newSite'] ) ? $_POST['newSite'] : __( 'New Network Created' ) ), ( isset( $_POST['cloneNetwork'] ) ? $_POST['cloneNetwork'] : NULL ), $options_to_clone
			);

			if ( $result && !is_wp_error( $result ) ) {
				if ( isset( $_POST['name'] ) ) {
					switch_to_network( $result );
					add_site_option( 'site_name', $_POST['name'] );
					restore_current_network();
				}

				$_GET['added'] = 'yes';
				$_GET['action'] = 'saved';
			} else {
				foreach ( $result->errors as $i => $error ) {
					echo("<h2>Error: " . $error[0] . "</h2>");
				}
			}
		}
	}

	function update_network_page() {
		global $wpdb;

		if ( isset( $_POST['update'] ) && isset( $_GET['id'] ) ) {
			$network = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->site} WHERE id = %d", (int) $_GET['id'] ) );
			if ( !$network )
				die( __( 'Invalid network id.' ) );

			update_network( (int) $_GET['id'], $_POST['domain'], $_POST['path'] );
			$_GET['updated'] = 'true';
			$_GET['action']  = 'saved';
		} else {

			// get network by id
			$network = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->site} WHERE id = %d", (int) $_GET['id'] ) );

			if ( !$network )
				wp_die( __( 'Invalid network id.' ) );

			?>
			<div class="wrap">
				<?php screen_icon( 'ms-admin' ); ?>
				<h2><?php _e( 'Networks' ) ?></h2>
				<h3><?php _e( 'Edit Network' ); ?>: http://<?php echo $network->domain . $network->path ?></h3>
				<form method="post" action="<?php echo remove_query_arg( 'action' ); ?>">
					<table class="form-table">
						<tr class="form-field"><th scope="row"><label for="domain"><?php _e( 'Domain' ); ?></label></th><td> http://<input type="text" id="domain" name="domain" value="<?php echo $network->domain; ?>"></td></tr>
						<tr class="form-field"><th scope="row"><label for="path"><?php _e( 'Path' ); ?></label></th><td><input type="text" id="path" name="path" value="<?php echo $network->path; ?>" /></td></tr>
					</table>
				<?php if ( has_action( 'add_edit_network_option' ) ) : ?>
						<h3><?php _e( 'Options:' ) ?></h3>
						<table class="form-table">
					<?php do_action( 'add_edit_network_option' ); ?>
						</table>
					<?php endif; ?>
					<p>
						<input type="hidden" name="networkId" value="<?php echo $network->id; ?>" />
						<input class="button" type="submit" name="update" value="<?php _e( 'Update Network' ); ?>" />
						<a href="<?php echo $this->admin_url(); ?>"><?php _e( 'Cancel' ); ?></a>
					</p>
				</form>
			</div>
			<?php
		}
	}

	function delete_network_page() {
		global $wpdb;

		if ( isset( $_POST['delete'] ) && isset( $_GET['id'] ) ) {
			$result = delete_network( (int) $_GET['id'], ( isset( $_POST['override'] ) ) );

			if ( is_a( $result, 'WP_Error' ) )
				wp_die( $result->get_error_message() );

			$_GET['deleted'] = 'yes';
			$_GET['action'] = 'saved';
		} else {

			/* get network by id */
			$network = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->site} WHERE id = %d", (int) $_GET['id'] ) );

			if ( !$network )
				die( __( 'Invalid network id.' ) );

			$sites = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->blogs} WHERE site_id = %d", (int) $_GET['id'] ) ); ?>

			<div class="wrap">
				<?php screen_icon( 'ms-admin' ); ?>
				<h2><?php _e( 'Networks' ) ?></h2>
				<h3><?php _e( 'Delete Network' ); ?>: <?php echo $network->domain . $network->path; ?></h3>
				<form method="POST" action="<?php echo remove_query_arg( 'action' ); ?>">
					<?php
					if ( $sites ) {
						if ( RESCUE_ORPHANED_BLOGS && ENABLE_NETWORK_ZERO ) {

							?>
									<div id="message" class="error">
										<p><?php _e( 'There are blogs associated with this network. ' );
							_e( 'Deleting it will move them to the holding network.' ); ?></p>
										<p><label for="override"><?php _e( 'If you still want to delete this network, check the following box' ); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
									</div>
							<?php } else { ?>
									<div id="message" class="error">
										<p><?php _e( 'There are blogs associated with this network. ' );
							_e( 'Deleting it will delete those blogs as well.' ); ?></p>
										<p><label for="override"><?php _e( 'If you still want to delete this network, check the following box' ); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
									</div>
							<?php
						}
					}

					?>
					<p><?php _e( 'Are you sure you want to delete this network?' ); ?></p>
					<input type="submit" name="delete" value="<?php _e( 'Delete Network' ); ?>" class="button" /> <a href="<?php echo $this->admin_url(); ?>"><?php _e( 'Cancel' ); ?></a>
				</form>
			</div>
			<?php
		}
	}


	function delete_multiple_network_page() {

		global $wpdb;

		if ( isset( $_POST['delete_multiple'] ) && isset( $_POST['deleted_networks'] ) ) {
			foreach ( $_POST['deleted_networks'] as $deleted_network ) {
				$result = delete_network( (int) $deleted_network, (isset( $_POST['override'] ) ) );
				if ( is_a( $result, 'WP_Error' ) ) {
					wp_die( $result->get_error_message() );
				}
			}
			$_GET['deleted'] = 'yes';
			$_GET['action'] = 'saved';
		} else {

			/** ensure a list of networks was sent */
			if ( !isset( $_POST['allnetworks'] ) ) {
				wp_die( __( 'You have not selected any networks to delete.' ) );
			}
			$allnetworks = array_map( create_function( '$val', 'return (int)$val;' ), $_POST['allnetworks'] );

			/** ensure each network is valid */
			foreach ( $allnetworks as $network ) {
				if ( !network_exists( (int) $network ) ) {
					wp_die( __( 'You have selected an invalid network for deletion.' ) );
				}
			}
			/** remove primary network from list */
			if ( in_array( 1, $allnetworks ) ) {
				$sites = array( );
				foreach ( $allnetworks as $network ) {
					if ( $network != 1 )
						$sites[] = $network;
				}
				$allnetworks = $sites;
			}

			$network = $wpdb->get_results( "SELECT * FROM {$wpdb->site} WHERE id IN (" . implode( ',', $allnetworks ) . ')' );
			if ( !$network ) {
				wp_die( __( 'You have selected an invalid network or networks for deletion' ) );
			}

			$sites = $wpdb->get_results( "SELECT * FROM {$wpdb->blogs} WHERE site_id IN (" . implode( ',', $allnetworks ) . ')' ); ?>

			<div class="wrap">
				<?php screen_icon( 'ms-admin' ); ?>
				<h2><?php _e( 'Networks' ) ?></h2>
				<h3><?php _e( 'Delete Multiple Networks' ); ?></h3>
				<form method="POST" action="<?php echo $this->admin_url(); ?>"><div>
					<?php if ( $sites ) {
						if ( RESCUE_ORPHANED_BLOGS && ENABLE_NETWORK_ZERO ) { ?>
							<div id="message" class="error">
								<h3><?php _e( 'You have selected the following networks for deletion' ); ?>:</h3>
								<ul>
									<?php foreach ( $network as $deleted_network ) { ?>
										<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
									<?php } ?>
								</ul>
								<p><?php _e( 'There are blogs associated with one or more of these networks.  Deleting them will move these blgos to the holding network.' ); ?></p>
								<p><label for="override"><?php _e( 'If you still want to delete these networks, check the following box' ); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
							</div>
						<?php } else { ?>
							<div id="message" class="error">
								<h3><?php _e( 'You have selected the following networks for deletion' ); ?>:</h3>
								<ul>
									<?php foreach ( $network as $deleted_network ) { ?>
										<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
									<?php } ?>
								</ul>
								<p><?php _e( 'There are blogs associated with one or more of these networks.  Deleting them will delete those blogs as well.' ); ?></p>
								<p><label for="override"><?php _e( 'If you still want to delete these networks, check the following box' ); ?>:</label> <input type="checkbox" name="override" id="override" /></p>
							</div>
						<?php
						}
					} else { ?>
						<div id="message">
							<h3><?php _e( 'You have selected the following networks for deletion' ); ?>:</h3>
							<ul>
								<?php foreach ( $network as $deleted_network ) { ?>
									<li><input type="hidden" name="deleted_networks[]" value="<?php echo $deleted_network->id; ?>" /><?php echo $deleted_network->domain . $deleted_network->path ?></li>
								<?php } ?>
							</ul>
						</div>
					<?php } ?>
					<p><?php _e( 'Are you sure you want to delete these networks?' ); ?></p>
					<input type="submit" name="delete_multiple" value="<?php _e( 'Delete Networks' ); ?>" class="button" /> <input type="submit" name="cancel" value="<?php _e( 'Cancel' ); ?>" class="button" />
				</form>
			</div>

			<?php
		}
	}

	/**
	 * Admin page for users who are network admins on another network, but possibly not the current one
	 */
	function my_networks_page() {

	}

	/**
	 * Admin page for Networks settings -
	 */
	function networks_settings_page() {

	}
}
