<?php
/**
 * Badge Factor 2
 * Copyright (C) 2021 ctrlweb
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package Badge_Factor_2_Certificates
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.VariableConstantNameFound
 * @phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
 * @phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
 */

namespace BadgeFactor2;

use BadgeFactor2\Models\Assertion;
use BadgeFactor2\Models\BadgeClass;
use BadgeFactor2\Post_Types\BadgePage;
use CMB2_Field;

/**
 * Certificates Admin class.
 */
class Basic_Certificates_Admin {

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public static function init_hooks() {
		add_action( 'cmb2_admin_init', array( self::class, 'register_settings_metaboxes' ), 10 );
		add_action( 'admin_init', array( self::class, 'manage_pdf_preview' ), 10 );
		add_action( 'cmb2_save_field_bf2_certificate_slug', array( self::class, 'save_certificate_slug' ), 99, 3 );
		add_action( 'init', array( self::class, 'flush_certificate_slug' ), 10 );
	}

	/**
	 * Registers Add-On settings page.
	 */
	public static function register_settings_metaboxes() {
		$plugin_data = get_plugin_data( BF2_BASIC_CERTIFICATES_FILE );

		$args = array(
			'id'           => 'bf2_certificates_settings_page',
			'menu_title'   => __( 'Certificates', $plugin_data['TextDomain'] ),
			'title'   => __( 'Certificates', $plugin_data['TextDomain'] ),
			'object_types' => array( 'options-page' ),
			'option_key'   => 'bf2_basic_certificates_settings',
			'capability'   => 'manage_badgr',
			'position' => 21,
			'icon_url' => BF2_BASIC_CERTIFICATES_BASEURL . ( 'assets/images/diploma_menu_icon.png' ),
		);

		$plugins2 = new_cmb2_box( $args );

		// Legend.
		$legend  = '';
		$legend .= '<div style="clear:both;">';
		$legend .= '<table>';
		$legend .= sprintf( '<thead><tr><th style="padding: 5px 0">%s</th><th style="padding: 5px 0">%s</th></tr></thead>', __( 'Variable', $plugin_data['TextDomain'] ), __( 'Description', $plugin_data['TextDomain'] ) );
		$legend .= '<tbody>';
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$badge$</strong></td><td style="padding:0">%s</td></tr>', __( 'Issued badge image with link', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$course$</strong></td><td style="padding:0">%s</td></tr>', __( 'Course name with link', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$date$</strong></td><td style="padding:0">%s</td></tr>', __( 'Badge issue date', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$hours$</strong></td><td style="padding:0">%s</td></tr>', __( 'Course duration', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$issuer$</strong></td><td style="padding:0">%s</td></tr>', __( 'Badge issuer name with link', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$name$</strong></td><td style="padding:0">%s</td></tr>', __( 'Recipient name', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0"><strong>$portfolio$</strong></td><td style="padding:0">%s</td></tr>', __( 'Recipient badge portfolio link', $plugin_data['TextDomain'] ) );
		$legend .= sprintf( '<tr><td style="padding:0; padding-top: 1em;"><a target="_blank" href="%s">%s</a></td></tr>', '?page=bf2_certificates_settings&preview=1', __( 'Preview', $plugin_data['TextDomain'] ) );
		$legend .= '</tbody>';
		$legend .= '</table>';
		$legend .= '</div>';

		$plugins2->add_field(
			array(
				'name' => 'Available variables',
				'desc' => $legend,
				'type' => 'title',
				'id'   => 'bf2_certificate_instructions',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Certificate slug', BF2_DATA['TextDomain'] ),
				'desc'    => __( 'When you modify this, you need to flush your rewrite rules.', BF2_DATA['TextDomain'] ),
				'id'      => 'bf2_certificate_slug',
				'type'    => 'text',
				'default' => 'certificate',
			)
		);

		$plugins2->add_field(
			array(
				'name'         => __( 'Certificate Template', $plugin_data['TextDomain'] ),
				'type'         => 'file',
				'id'           => 'bf2_certificate_template',
				'options'      => array(
					'url' => false,
				),
				'text'         => array(
					'add_upload_file_text' => __( 'Add Template', $plugin_data['TextDomain'] ),
				),
				'query_args'   => array(
					'type' => 'application/pdf',
				),
				'preview_size' => 'small',
			)
		);

		$plugins2->add_field(
			array(
				'name' => __( 'Certificate Title', $plugin_data['TextDomain'] ),
				'id'   => 'bf2_basic_certificate_title',
				'type' => 'basic_pdf_field',
			)
		);

		$plugins2->add_field(
			array(
				'name' => __( 'Certificate Subtitle', $plugin_data['TextDomain'] ),
				'id'   => 'bf2_basic_certificate_subtitle',
				'type' => 'basic_pdf_field',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Issued Badge Image', $plugin_data['TextDomain'] ),
				'id'      => 'bf2_basic_certificate_badge',
				'type'    => 'basic_pdf_field',
				'default' => '$badge$',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Course Name', $plugin_data['TextDomain'] ),
				'id'      => 'bf2_basic_certificate_course',
				'type'    => 'basic_pdf_field',
				'default' => '$course$',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Recipient Text', $plugin_data['TextDomain'] ),
				'id'      => 'bf2_basic_certificate_recipient',
				'type'    => 'basic_pdf_field',
				'default' => 'Issued to $name$',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Issued Date Text', $plugin_data['TextDomain'] ),
				'id'      => 'bf2_basic_certificate_date',
				'type'    => 'basic_pdf_field',
				'default' => 'Issued on $date$',
			)
		);

		$plugins2->add_field(
			array(
				'name'    => __( 'Portfolio Link', $plugin_data['TextDomain'] ),
				'id'      => 'bf2_basic_certificate_portfolio',
				'type'    => 'basic_pdf_field',
				'default' => 'Link to recipient portfolio: $portfolio$',
			)
		);

	}


	/**
	 * Hook called on certificate_slug field save.
	 *
	 * @param boolean $updated Updated.
	 * @param string $action Action.
	 * @param CMB2_Field $instance Field instance.
	 * @return void
	 */
	public static function save_certificate_slug( bool $updated, string $action, CMB2_Field $instance ) {
		set_transient( 'flush_certificate_slug', true );
	}


	/**
	 * Hook called to verify if rewrite rules flush is required.
	 *
	 * @return void
	 */
	public static function flush_certificate_slug() {
		if ( delete_transient( 'flush_certificate_slug' ) ) {
			flush_rewrite_rules();
		}
	}


	/**
	 * Manage PDF preview in Certificates admin page.
	 *
	 * @return void
	 */
	public static function manage_pdf_preview() {
		global $pagenow;
		if ( 'admin.php' === $pagenow &&
			'bf2_certificates_settings' === $_GET['page'] &&
			'1' === $_GET['preview']
			) {
				self::generate_preview();
				die;
		}
	}


	/**
	 * Generates a preview.
	 *
	 * @return void
	 */
	private static function generate_preview() {
		$assertion = Assertion::random();
		$entity_id = $assertion->entityId;
		$assertion = Assertion::get( $entity_id );
		$badge     = BadgeClass::get( $assertion->badgeclass );
		$badgepage = BadgePage::get_by_badgeclass_id( $badge->entityId );
		$courses   = BadgePage::get_courses( $badgepage->ID );

		Basic_Certificates_Public::generate( $courses[0], $assertion );
	}
}
