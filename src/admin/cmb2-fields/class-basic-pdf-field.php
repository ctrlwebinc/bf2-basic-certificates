<?php
/**
 * Badge Factor 2
 * Copyright (C) 2021 ctrlweb
 *
 * This program is distributed WITHOUT ANY WARRANTY.
 *
 * @package Badge_Factor_2
 *
 * @phpcs:disable WordPress.WP.I18n.NonSingularStringLiteralDomain
 */

namespace BadgeFactor2\Admin\CMB2_Fields;

use BadgeFactor2\Helpers\Text;

/**
 * CMB2 PDF Field.
 */
class Basic_PDF_Field {

	/**
	 * Init Hooks.
	 *
	 * @return void
	 */
	public static function init_hooks() {
		add_filter( 'cmb2_render_basic_pdf_field', array( self::class, 'render_pdf_field' ), 10, 5 );
	}

	/**
	 * Render Badge Page Course.
	 *
	 * @param CMB2_Field $field Field.
	 * @param string     $field_escaped_value Field escaped value.
	 * @param string     $field_object_id Field object id.
	 * @param string     $field_object_type Field object type.
	 * @param CMB2_Types $field_type_object Field Type Object.
	 * @return void
	 */
	public static function render_pdf_field( $field, $field_escaped_value, $field_object_id, $field_object_type, $field_type_object ) {
		$plugin_data = get_plugin_data( BF2_BASIC_CERTIFICATES_FILE );

		$fonts_available = array(
			'courier'      => 'Courier',
			'helvetica'    => 'Helvetica',
			'symbol'       => 'Symbol',
			'times'        => 'Times New Roman',
			'zapfdingbats' => 'Zapf Dingbats',
		);

		$styles_available = array(
			''    => 'Regular',
			'B'   => 'Bold',
			'I'   => 'Italic',
			'U'   => 'Underline',
			'BI'  => 'Bold Italic',
			'BU'  => 'Bold Underline',
			'IU'  => 'Italic Underline',
			'BIU' => 'Bold Italic Underline',
		);

		$alignments_available = array(
			'L' => 'Left',
			'C' => 'Center',
			'R' => 'Right',
		);

		$field_escaped_value = wp_parse_args(
			$field_escaped_value,
			array(
				'text'  => '',
				'width' => '',
				'font'  => '',
				'style' => '',
				'size'  => '',
				'pos_x' => '',
				'pos_y' => '',
				'align' => '',
				'color' => '',
			)
		);

		// Text and cell width.
		echo '<div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p style="margin-top:0"><label for="%s">%s</label></p>', $field_type_object->_id( '_text' ), __( 'Text', $plugin_data['TextDomain'] ) );
		echo $field_type_object->input(
			array(
				'name'     => $field_type_object->_name( '[text]' ),
				'id'       => $field_type_object->_id( '_text' ),
				'type'     => 'text',
				'value'    => ! empty( $field_escaped_value['text'] ) ? $field_escaped_value['text'] : $field_type_object->field->args['default'],
				'required' => 'required',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p style="margin-top:0"><label for="%s">%s</label></p>', $field_type_object->_id( '_width' ), __( 'Width (mm)', $plugin_data['TextDomain'] ) );
		echo $field_type_object->input(
			array(
				'class'    => 'cmb_text_small',
				'name'     => $field_type_object->_name( '[width]' ),
				'id'       => $field_type_object->_id( '_width' ),
				'type'     => 'text',
				'value'    => $field_escaped_value['width'],
				'required' => 'required',
			)
		);
		echo '</div>';
		echo '</div>';

		// Font.
		echo '<div style="clear:both;">';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_font' ), __( 'Font', $plugin_data['TextDomain'] ) );
		echo $field_type_object->select(
			array(
				'class'    => 'cmb-type-select',
				'name'     => $field_type_object->_name( '[font]' ),
				'id'       => $field_type_object->_id( '_font' ),
				'default'  => 'Arial',
				'options'  => Text::html_options_from_array( $fonts_available, $field_escaped_value['font'] ),
				'required' => 'required',
				'style'    => 'margin-top: 0',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_style' ), __( 'Style', $plugin_data['TextDomain'] ) );
		echo $field_type_object->select(
			array(
				'class'   => 'cmb-type-select',
				'name'    => $field_type_object->_name( '[style]' ),
				'id'      => $field_type_object->_id( '_style' ),
				'default' => '',
				'options' => Text::html_options_from_array( $styles_available, $field_escaped_value['style'] ),
				'style'   => 'margin-top: 0',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_size' ), __( 'Size (pt)', $plugin_data['TextDomain'] ) );
		echo $field_type_object->input(
			array(
				'class'    => 'cmb_text_small',
				'name'     => $field_type_object->_name( '[size]' ),
				'id'       => $field_type_object->_id( '_size' ),
				'type'     => 'text',
				'value'    => $field_escaped_value['size'],
				'required' => 'required',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_color' ), __( 'Couleur', $plugin_data['TextDomain'] ) );
		echo $field_type_object->colorpicker(
			array(
				'name'     => $field_type_object->_name( '[color]' ),
				'id'       => $field_type_object->_id( '_color' ),
				'value'    => $field_escaped_value['color'],
			),
			"#000000"
		);
		echo '</div>';
		echo '</div>';

		// Coordinates and alignment.
		echo '<div style="clear:both;">';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_pos_x' ), __( 'Pos. X (mm)', $plugin_data['TextDomain'] ) );
		echo $field_type_object->input(
			array(
				'class'    => 'cmb_text_small',
				'name'     => $field_type_object->_name( '[pos_x]' ),
				'id'       => $field_type_object->_id( '_pos_x' ),
				'type'     => 'text',
				'value'    => $field_escaped_value['pos_x'],
				'required' => 'required',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_pos_y' ), __( 'Pos. Y (mm)', $plugin_data['TextDomain'] ) );
		echo $field_type_object->input(
			array(
				'class'    => 'cmb_text_small',
				'name'     => $field_type_object->_name( '[pos_y]' ),
				'id'       => $field_type_object->_id( '_pos_y' ),
				'type'     => 'text',
				'value'    => $field_escaped_value['pos_y'],
				'required' => 'required',
			)
		);
		echo '</div>';
		echo '<div class="alignleft">';
		echo sprintf( '<p><label for="%s">%s</label></p>', $field_type_object->_id( '_align' ), __( 'Alignment', $plugin_data['TextDomain'] ) );
		echo $field_type_object->select(
			array(
				'class'   => 'cmb-type-select',
				'name'    => $field_type_object->_name( '[align]' ),
				'id'      => $field_type_object->_id( '_align' ),
				'default' => '',
				'options' => Text::html_options_from_array( $alignments_available, $field_escaped_value['align'] ),
				'style'   => 'margin-top: 0',
			)
		);
		echo '</div>';
		echo '</div>';
	}
}
