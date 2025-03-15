<?php

/*
	Plugin Name: Word Filter
	Description: Replaces a list of words.
	Version: 1.0
	Author: Miguel Caballero
*/

if ( ! defined( 'ABSPATH' ) )
	exit; // Exit if accessed directly in this file

class Word_Filter {

	/**
	 * Initializes the plugin by adding hooks for admin settings and content filtering.
	 */
	public function __construct() {
		add_action( 'admin_menu', [ $this, 'add_word_filter_page' ] );
		add_action( 'admin_init', [ $this, 'add_settings' ] );
		if ( get_option( 'plugin_words_to_filter' ) )
			add_filter( 'the_content', [ $this, 'filter_content' ] );
	}

	/**
	 * Adds the plugin's menu and submenus to the WordPress admin dashboard.
	 * The main menu page displays a textarea for entering a list of words to filter.
	 * The submenu page displays a text field for entering the replacement text.
	 * 
	 * @return void
	 */
	public function add_word_filter_page(): void {
		$word_filter_page = add_menu_page(
			'Words To Filter',
			'Word Filter',
			'manage_options',
			'word-filter',
			[ $this, 'word_filter_html' ],
			'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZmlsbC1ydWxlPSJldmVub2RkIiBjbGlwLXJ1bGU9ImV2ZW5vZGQiIGQ9Ik0xMCAyMEMxNS41MjI5IDIwIDIwIDE1LjUyMjkgMjAgMTBDMjAgNC40NzcxNCAxNS41MjI5IDAgMTAgMEM0LjQ3NzE0IDAgMCA0LjQ3NzE0IDAgMTBDMCAxNS41MjI5IDQuNDc3MTQgMjAgMTAgMjBaTTExLjk5IDcuNDQ2NjZMMTAuMDc4MSAxLjU2MjVMOC4xNjYyNiA3LjQ0NjY2SDEuOTc5MjhMNi45ODQ2NSAxMS4wODMzTDUuMDcyNzUgMTYuOTY3NEwxMC4wNzgxIDEzLjMzMDhMMTUuMDgzNSAxNi45Njc0TDEzLjE3MTYgMTEuMDgzM0wxOC4xNzcgNy40NDY2NkgxMS45OVoiIGZpbGw9IiNGRkRGOEQiLz4KPC9zdmc+',
			100
		);
		add_submenu_page(
			'word-filter',
			'Words To Filter',
			'Word List',
			'manage_options',
			'word-filter',
			[ $this, 'word_filter_html' ]
		);
		add_submenu_page(
			'word-filter',
			'Word Filter Options',
			'Options',
			'manage_options',
			'word-filter-options',
			[ $this, 'options_subpage_html' ]
		);

		// Add custom styles
		add_action( "load-{$word_filter_page}", [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Registers settings and fields for the plugin options.
	 * The settings include the replacement text for filtered words.
	 * 
	 * @return void
	 */
	public function add_settings(): void {
		add_settings_section(
			'replacement-text-section',
			null,
			"__return_false",
			'word-filter-options'
		);

		add_settings_field(
			'replacement-text',
			'Filtered Text',
			[ $this, 'replacement_html' ],
			'word-filter-options',
			'replacement-text-section'
		);
		register_setting(
			'replacement-fields',
			'replacement-text'
		);
	}

	/**
	 * Filters the post content by replacing specified words.
	 * 
	 * @param string $content The original post content.
	 * @return string The filtered content with words replaced.
	 */
	public function filter_content( string $content ): string {
		$bad_words = explode( ',', get_option( 'plugin_words_to_filter' ) );
		$bad_words_trimmed = array_map( 'trim', $bad_words );
		return str_ireplace( $bad_words_trimmed, esc_html( get_option( 'replacement-text' ) ), $content );
	}

	/**
	 * Enqueues styles for the plugin.
	 * 
	 * @return void
	 */
	public function enqueue_scripts(): void {
		wp_enqueue_style( 'word-filter-css', plugin_dir_url( __FILE__ ) . '/assets/css/styles.css' );
	}

	/**
	 * Handles form submissions and saves filtered words.
	 * 
	 * @return void
	 */
	public function handle_form(): void {
		if ( isset( $_POST['word-filter-nonce'] ) && wp_verify_nonce( $_POST['word-filter-nonce'], 'save-word-filter' ) && current_user_can( 'manage_options' ) ) {
			update_option( 'plugin_words_to_filter', sanitize_text_field( $_POST['plugin_words_to_filter'] ) ); ?>
			<div class="updated">
				<p>Your filtered words were saved.</p>
			</div>
			<?php
		} else { ?>
			<div class="error">
				<p>Sorry, you do not have permission to perform that action.</p>
			</div>
			<?php
		}
	}

	/**
	 * Displays the main settings page for the Word Filter plugin.
	 * The page contains a textarea for entering a list of words to filter.
	 * 
	 * @return void
	 */
	public function word_filter_html(): void { ?>
		<div class="wrap">
			<h1>Word Filter.</h1>
			<?php
			if ( isset( $_POST['word-filter-submit'] ) && $_POST['word-filter-submit'] === 'true' ) {
				$this->handle_form();
			} ?>
			<form method="POST">
				<input type="hidden" name="word-filter-submit" value="true">
				<?php wp_nonce_field( 'save-word-filter', 'word-filter-nonce' ); ?>
				<label for="plugin_words_to_filter">
					<p>Enter a <strong>comma-separated</strong> list of words to filter from your site's content.</p>
				</label>
				<div class="word-filter__flex-container">
					<textarea name="plugin_words_to_filter" id="plugin_words_to_filter"
						placeholder="bad, mean, awful, horrible"><?= esc_textarea( get_option( 'plugin_words_to_filter' ) ); ?></textarea>
				</div>
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save changes">
			</form>
		</div>
		<?php
	}

	/**
	 * Displays the input field for the replacement text in the settings page.
	 * 
	 * @return void
	 */
	public function replacement_html(): void { ?>
		<input type="text" name="replacement-text" value="<?= esc_attr( get_option( 'replacement-text', '***' ) ); ?>">
		<p class="description">Leave blank to simply remove the filtered words.</p>
		<?php
	}

	/**
	 * Displays the options subpage for the Word Filter plugin.
	 * The subpage contains a text field for entering the replacement text.
	 * 
	 * @return void
	 */
	public function options_subpage_html(): void { ?>
		<div class="wrap">
			<h1>Word Filter Options</h1>
			<form action="options.php" method="POST">
				<?php
				settings_errors();
				settings_fields( 'replacement-fields' );
				do_settings_sections( 'word-filter-options' );
				submit_button(); ?>
			</form>
		</div>
		<?php
	}
}

$word_filter = new Word_Filter();
