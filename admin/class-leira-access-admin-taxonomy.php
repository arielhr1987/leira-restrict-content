<?php

/**
 * The admin-specific functionality for taxonomy items.
 *
 * @since      1.0.0
 *
 * @package    Leira_Access
 * @subpackage Leira_Access/admin
 * @author     Ariel <arielhr1987@gmail.com>
 */
class Leira_Access_Admin_Taxonomy{

	/**
	 * Constructor.
	 */
	public function __construct() {

	}

	/**
	 * Get the list of available taxonomies
	 *
	 * @return array
	 * @access public
	 * @since  1.0.0
	 */
	public function get_taxonomies() {
		$taxonomies = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'names' );

		$exclude = apply_filters( 'leira_access_excluded_taxonomies', array() );

		$taxonomies = array_diff( $taxonomies, $exclude );

		return $taxonomies;
	}

	/**
	 * Add column header to taxonomy list table
	 *
	 * @param array $columns List of available columns
	 *
	 * @return array
	 * @access public
	 * @since  1.0.0
	 */
	public function custom_column_header( $columns ) {
		$columns['leira-access'] = __( 'Access', 'leira-access' );

		return $columns;
	}

	/**
	 * Add sortable columns to taxonomy list table
	 *
	 * @param array $columns List of available sortable columns
	 *
	 * @return array
	 * @access public
	 * @since  1.0.0
	 */
	public function custom_column_sortable( $columns ) {
		$columns['leira-access'] = 'leira-access';

		return $columns;
	}

	/**
	 * Set content for columns in management page
	 *
	 * @param string $string      Blank string.
	 * @param string $column_name Name of the column.
	 * @param int    $term_id     Term ID.
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function custom_column_content( $string, $column_name, $term_id ) {
		if ( 'leira-access' != $column_name ) {
			return;
		}

		$access = get_term_meta( $term_id, '_leira-access', true );
		$output = __( 'Everyone', 'leira-access' );

		if ( $access == 'out' ) {
			$output = __( 'Logged Out Users', 'leira-access' );
		} else {
			//is "in" or array of roles
			if ( $access == 'in' ) {
				$output = __( 'Logged In Users', 'leira-access' );
			} else if ( is_array( $access ) ) {
				$roles = '';
				if ( ! empty( $access ) ) {
					$roles .= '<ul>';
					foreach ( $access as $role ) {
						$roles .= sprintf( '<li>%s</li>', $role );
					}
					$roles .= '</ul>';
				}
				$output = sprintf( __( 'Logged In Users %s', 'leira-access' ), $roles );
			}
		}

		//Add inline edit values
		$output .= sprintf( '<div class="hidden inline-leira-access">%s</div>', json_encode( $access ) );

		echo $output;
	}

	/**
	 * Handle sort custom column actions
	 *
	 * @param WP_Term_Query $query
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public function custom_column_sort( $query ) {
		global $pagenow, $wpdb;
		$taxonomy = isset( $_GET ['taxonomy'] ) ? $_GET ['taxonomy'] : false;
		$order_by = isset( $_GET ['orderby'] ) ? $_GET ['orderby'] : false;
		$order    = isset( $_GET ['order'] ) ? $_GET ['order'] : 'DESC';
		if ( ! in_array( $order, array( 'ASC', 'DESC' ) ) ) {
			$order = 'DESC';
		}

		$available_taxonomies = $this->get_taxonomies();
		if ( 'leira-access' == $order_by && $pagenow == 'edit-tags.php' && in_array( $taxonomy, $available_taxonomies ) ) {

			$meta_query = new WP_Meta_Query( array(
				'order_clause' => array(
					'relation' => 'OR',
					array(
						'key'  => '_leira-access',
						'type' => 'CHAR'
					),
					array(
						'key'     => '_leira-access',
						'compare' => 'NOT EXISTS'
					),
				)
			) );
			// and ordering matches
			$query->meta_query            = $meta_query;
			$query->query_vars['orderby'] = 'meta_value_num';
			$query->query_vars['order']   = $order;


		}
	}

	/**
	 * Add quick edit form to taxonomies list table
	 *
	 * @param string $column_name
	 * @param string $post_type
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function quick_edit_custom_box( $column_name, $post_type ) {
		$screen   = get_current_screen();
		$taxonomy = isset( $screen->taxonomy ) ? $screen->taxonomy : false;
		if ( 'leira-access' != $column_name || ! in_array( $taxonomy, $this->get_taxonomies() ) || 'edit-tags' !== $post_type ) {
			return;
		}

		$id    = false;
		$roles = array();
		?>
        <fieldset class="">
            <div class="inline-edit-col">
				<?php leira_access()->admin->form( array(
					'roles'           => $roles,
					'id'              => $id,
					'input_id_prefix' => 'inline-leira-access'
				) ); ?>
            </div>
        </fieldset>
		<?php
	}

	/**
	 * Enqueue quick edit list table script
	 *
	 * @param $hook
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function admin_enqueue_quick_edit_scripts( $hook ) {
		$pages = array( 'edit-tags.php' );
		if ( ! in_array( $hook, $pages ) ) {
			return;
		}

		$screen   = get_current_screen();
		$taxonomy = isset( $screen->taxonomy ) ? $screen->taxonomy : false;
		if ( ! in_array( $taxonomy, $this->get_taxonomies() ) ) {
			return;
		}

		wp_enqueue_script( 'leira-access-admin-quick-edit-taxonomy-js', plugins_url( '/js/leira-access-admin-quick-edit-taxonomy.js', __FILE__ ), array(
			'jquery',
			'inline-edit-tax'
		) );
	}

	/**
	 * Add access form to term edit screen admin page
	 *
	 * @param $tag
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function edit_form_fields( $tag ) {
		$roles = get_term_meta( $tag->term_id, '_leira-access', true );

		?>
        <tr>
            <th scope="row">
                <label for=""><?php _e( 'Access', 'leira-access' ) ?></label>
            </th>
            <td>
				<?php leira_access()->admin->form( array(
					'roles'      => $roles,
					//'id'         => $tag->term_id,
					'show_label' => false
				) ) ?>
                <p class="description">
                    <!-- An optional description -->
                </p>
            </td>
        </tr>
		<?php
	}

	/**
	 * Add access form to create term screen admin page
	 *
	 * @param $taxonomy
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function add_form_fields( $taxonomy ) {

		?>
        <div class="form-field">
            <label for=""><?php _e( 'Access', 'leira-access' ) ?> </label>
			<?php leira_access()->admin->form( array(
				'show_label' => false
			) ) ?>
            <p class="">
                <!--An optional description-->
            </p>
        </div>
		<?php
	}

	/**
	 * Handle the term edit request, either ajax or regular POST request. The system will handle the information
	 * provided in the "leira-access-*" fields and save it as metadata
	 *
	 * @param $term_id
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function edit( $term_id ) {
		leira_access()->admin->save( $term_id, 'term', false );
	}

	/**
	 * Handle the term add POST request. The system will handle the information provided in the "leira-access-*"
	 * fields and save it as metadata
	 *
	 * @param $term_id
	 *
	 * @access public
	 * @since  1.0.0
	 */
	public function save( $term_id ) {
		leira_access()->admin->save( $term_id, 'term', false );
	}

}
