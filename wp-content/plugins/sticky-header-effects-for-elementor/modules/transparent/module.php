<?php
namespace SheHeader\Modules\Transparent;

use Elementor;
use Elementor\Plugin;
use Elementor\Controls_Manager;
use Elementor\Controls_Stack;
use SheHeader\Base\Module_Base;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Module extends Module_Base {

	public function __construct() {
		parent::__construct();

		$this->add_actions();
	}

	public function get_name() {
		return 'transparent';
	}

	public function register_controls( Controls_Stack $element ) {
		$element->start_controls_section(
			'section_sticky_header_effect',
			[
				'label' => __( 'Sticky Header Effects', 'she-header' ),
				'tab' => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'transparent',
			[
				'label' => __( 'Enable', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'bew-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,				
				'prefix_class'  => 'she-header-'
			]
		);
		
		$element->add_control(
			'sticky_header_notice',
			[
				'raw' => __( 'IMPORTANT: Sticky Header Effects is best used with Elementor Pro sticky option enabled.', 'she-header' ),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-warning',
				'condition' => [
				    
					'transparent!' => '',
				],
			]
		);

		$element->add_control(
			'transparent_on',
			[
				'label' => __( 'Enable On', 'she-header' ),
				'type' => Controls_Manager::SELECT2,
				'multiple' => true,
				'label_block' => 'true',
				'default' => [ 'desktop', 'tablet', 'mobile' ],
				'options' => [
					'desktop' => __( 'Desktop', 'she-header' ),
					'tablet' => __( 'Tablet', 'she-header' ),
					'mobile' => __( 'Mobile', 'she-header' ),
				],
				'condition' => [
					'transparent!' => ''
				],
				'render_type' => 'none',
				'frontend_available' => true,
			]
		);
				
		$element->add_responsive_control(
			'scroll_distance',
			[
				'label' => __( 'Scroll Distance (px)', 'she-header' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 60,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'size_units' => [ 'px'],				
				'description' => __( 'Choose the scroll distance to enable Sticky Header Effects', 'she-header' ),
				'frontend_available' => true,
				'condition' => [
					'transparent!' => '',
				],
			]
		);
		
		$element->add_control(
			'settings_notice',
			[
				'raw' => __( 'Remember: The settings below will not be applied until the page is scrolled the distance set above', 'she-header' ),
				'type' => Controls_Manager::RAW_HTML,
				'content_classes' => 'elementor-descriptor',
				'condition' => [
					'transparent!' => '',
					],
			]
		);
		
		$element->add_control(
			'transparent_header_show',
			[
				'label' => __( 'Transparent Header', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before',
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'bew-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,
				'condition' => [					
					'transparent!' => '',
				],
				'description' => __( 'Initial transparent header', 'she-header' ),
				'prefix_class'  => 'she-header-transparent-'
			]
		);
				
		$element->add_control(
			'background_show',
			[
				'label' => __( 'Header Background', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before',
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'bew-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,
				'condition' => [					
					'transparent!' => '',
				],
				'description' => __( 'Choose background color after scrolling', 'she-header' ),
			]
		);
		
		$element->add_control(
			'background',
			[
				'label' => __( 'Color', 'she-header' ),
				'type' => Controls_Manager::COLOR,				
				'condition' => [
				    'background_show' => 'yes',
					'transparent!' => '',
				],
				'render_type' => 'none',
				'frontend_available' => true,				
			]
		);
		
		$element->add_control(
			'bottom_border',
			[
				'label' => __( 'Bottom Border', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before',
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'she-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,
				'condition' => [
					'transparent!' => '',
				],
				'description' => __( 'Choose bottom border size and color', 'she-header' ),
			]
		);
				

		$element->add_control(
			'custom_bottom_border_color',
			[
				'label' => __( 'Color', 'she-header' ),
				'type' => Controls_Manager::COLOR,
				'condition' => [
				    'bottom_border' => 'yes',
					'transparent!' => '',
				],
				'render_type' => 'none',
				'frontend_available' => true,				
			]
		);
				
		$element->add_responsive_control(
			'custom_bottom_border_width',
			[
				'label' => __( 'Width (px)', 'she-header' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 0,
				],
				'range' => [
					'px' => [ 
						'min' => 0,
						'max' => 100,
					],
				],
				'size_units' => [ 'px'],
				'condition' => [
				    'bottom_border' => 'yes',
					'transparent!' => '',
				],
				'frontend_available' => true,
			]
		);
		
		$element->add_control(
			'shrink_header',
			[
				'label' => __( 'Shrink', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,
				'separator' => 'before',
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'she-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,
				'description' => __( 'Choose header height after scrolling', 'she-header' ),
				'condition' => [
					'transparent!' => '',
				],
			]
		);
				
		$element->add_responsive_control(
			'custom_height_header',
			[
				'label' => __( 'Height (px)', 'she-header' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => 70,
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'size_units' => [ 'px'],
				'condition' => [
				    'shrink_header' => 'yes',
					'transparent!' => '',
				],
				'frontend_available' => true,
			]
		);
		
		$element->add_control(
			'shrink_header_logo',
			[
				'label' => __( 'Logo', 'she-header' ),
				'type' => Controls_Manager::SWITCHER,				
				'label_on' => __( 'On', 'she-header' ),
				'label_off' => __( 'Off', 'she-header' ),
				'return_value' => 'yes',
				'default' => '',
				'frontend_available' => true,
				'description' => __( 'Choose logo height after scrolling', 'she-header' ),
				'condition' => [
					'shrink_header' => 'yes',
					'transparent!' => '',
				],
			]
		);
				
		$element->add_responsive_control(
			'custom_height_header_logo',
			[
				'label' => __( 'Height (px)', 'she-header' ),
				'type' => Controls_Manager::SLIDER,
				'default' => [
					'size' => '',
				],
				'range' => [
					'px' => [
						'min' => 0,
						'max' => 500,
					],
				],
				'size_units' => [ 'px'],
				'condition' => [
				    'shrink_header_logo' => 'yes',
					'transparent!' => '',
				],
				'frontend_available' => true,				
			]
		);
		
		
		$element->end_controls_section();
	}

	private function add_actions() {
		if( !function_exists('is_plugin_active') ) {
			
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			
		}
		
		if( is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
		add_action( 'elementor/element/section/section_effects/after_section_end', [ $this, 'register_controls' ] );
		} else {
		add_action( 'elementor/element/section/section_advanced/after_section_end', [ $this, 'register_controls' ] );	
		}
			
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
		if (Elementor\Plugin::instance()->editor->is_edit_mode()) {		
		}else{
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		}
	}
		
	public function enqueue_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
						
		wp_enqueue_style(
			'she-header-style',
			SHE_HEADER_ASSETS_URL  . 'css/she-header-style' . '.css',
			[],
			SHE_HEADER_VERSION
		);
		
	}
		
	public function enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script(
			'she-header',
			SHE_HEADER_URL . 'assets/js/she-header.js',
			[
				'jquery',
			],
			SHE_HEADER_VERSION,
			false
		);
	}
	
	
}
