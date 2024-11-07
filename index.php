<?php
/**
 * Plugin Name: WP DocuGenius
 * Description: Displays documentation for all functions in the theme files on a custom admin page.
 * Version: 1.0
 * Author: Melissa Redd
 * License: GPL2
 */
 
 if (!defined('ABSPATH')) exit; // Exit if accessed directly
 
 class ThemeDocumentationGenerator {
    private $transient_key = 'ai_theme_documentation';
    private $option_key = 'ai_openai_api_key';

    public function __construct() {
        add_action('admin_menu', [$this, 'register_admin_page']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('generate_function_docs_cron', [$this, 'generate_function_docs_cron_task']);
        add_action('wp_ajax_refresh_function_docs', [$this, 'handle_refresh_function_docs']);
        add_action('wp_ajax_check_documentation_ready', [$this, 'check_documentation_ready']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    // Register Admin Pages
    public function register_admin_page() {
        add_menu_page(
            'Theme Documentation',
            'Theme Docs',
            'manage_options',
            'theme-documentation',
            [$this, 'display_function_docs_page'],
            'dashicons-welcome-learn-more'
        );

        // Submenu for Settings
        add_submenu_page(
            'theme-documentation',
            'API Settings',
            'API Settings',
            'manage_options',
            'theme-docs-api-settings',
            [$this, 'display_api_settings_page']
        );
    }

    // Register Settings for API Key
    public function register_settings() {
        register_setting('theme_docs_settings', $this->option_key);
    }

    public function handle_refresh_function_docs() {
		// Verify nonce for security
		check_ajax_referer('refresh_function_docs_nonce', '_ajax_nonce');

		// Schedule the cron job to run immediately
		if (!wp_next_scheduled('generate_function_docs_cron')) {
			wp_schedule_single_event(time(), 'generate_function_docs_cron');
		}

		// Respond immediately
		wp_send_json_success(['message' => 'Documentation refresh scheduled.']);
	}
    
    // Enqueue Scripts for Export and Refresh
    public function enqueue_admin_scripts($hook) {
        if ($hook === 'toplevel_page_theme-documentation') {
            wp_enqueue_style('theme-doc-style', plugin_dir_url(__FILE__) . 'style.css');
            wp_enqueue_script('theme-docs-script', plugin_dir_url(__FILE__) . 'script.js', ['jquery'], null, true);
        
            // Load jsPDF, html2canvas, and DOMPurify from CDNs
            wp_enqueue_script('html2canvas', 'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js', [], null, true);
            wp_enqueue_script('dompurify', 'https://cdnjs.cloudflare.com/ajax/libs/dompurify/2.3.4/purify.min.js', [], null, true);
            wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js', ['html2canvas', 'dompurify'], null, true);

            wp_localize_script('theme-docs-script', 'themeDocsSettings', [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('refresh_function_docs_nonce')
            ]);
        }
    }

    // Display Documentation Page
    public function display_function_docs_page() {
        // Check if documentation is cached in the transient

        echo '<div id="loading-indicator" style="display:none;"><p>Refreshing Documentation... Please wait.</p></div>';

        $function_docs = get_transient($this->transient_key);

        if ($function_docs !== false) {
            // Display cached documentation
            echo "<div id='documentation-content' class='documentation-wrapper'>";
            echo '<h1>Theme Documentation</h1>';
            echo '<p>Below is the documentation for all functions in the theme files.</p>';
            echo '<div class="button-wrapper">';
            echo '<button id="refresh-docs" class="button">Refresh Documentation</button>';
            echo '<button id="export-docs" class="button">Export to Markdown File</button>';
            echo '</div>';
            foreach ($function_docs as $doc) {
                echo $doc;
            }
            echo "</div>";
        } else {
            // If not cached, inform the user that documentation is being generated
            echo "<p>Documentation is being generated. Please check back in a few minutes.</p>";
        }

        // Display refresh and export buttons

        // Handle manual refresh if the button is clicked
        if (isset($_POST['refresh_docs'])) {
            delete_transient($this->transient_key); // Clear old transient
            $api_key = get_option($this->option_key);
            if ($api_key) {
                $this->generate_function_docs_process($api_key); // Generate new documentation
            }
            echo "<script>location.reload();</script>"; // Reload page to display updated documentation
        }
    }

    // Display API Settings Page
    public function display_api_settings_page() {
        ?>
        <div class="wrap">
            <h1>AI API Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('theme_docs_settings');
                do_settings_sections('theme_docs_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td>
                            <input type="text" name="<?php echo esc_attr($this->option_key); ?>" value="<?php echo esc_attr(get_option($this->option_key)); ?>" class="regular-text" />
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function check_documentation_ready() {
		// Check for nonce for security
		check_ajax_referer('refresh_function_docs_nonce', '_ajax_nonce');

		// Check if the transient data exists
		$docs_ready = get_transient($this->transient_key) !== false;

		wp_send_json_success(['ready' => $docs_ready]);
	}


    // Activate Plugin: Schedule cron job
    public function activate() {
        if (!wp_next_scheduled('generate_function_docs_cron')) {
            wp_schedule_event(time(), 'daily', 'generate_function_docs_cron');
        }
    }

    // Deactivate Plugin: Clear scheduled cron job
    public function deactivate() {
        wp_clear_scheduled_hook('generate_function_docs_cron');
    }

    // Cron Job Task for Generating Documentation
    public function generate_function_docs_cron_task() {
        $api_key = get_option($this->option_key);

        // Only generate documentation if API key exists and no cached transient
        if ($api_key && get_transient($this->transient_key) === false) {
            $this->generate_function_docs_process($api_key);
        }
    }

    // Generate Documentation and Store in Transient
    private function generate_function_docs_process($api_key) {
        $theme_directory = get_template_directory();
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($theme_directory));
        $php_files = new RegexIterator($files, '/\.php$/');
        $chunk_size = 3;
        $function_docs = [];

        foreach ($php_files as $php_file) {
            $file_content = file_get_contents($php_file->getRealPath());

            // Find all functions in the file
            preg_match_all('/function\s+([a-zA-Z0-9_]+)\s*\(.*?\)\s*\{.*?\}/s', $file_content, $matches, PREG_OFFSET_CAPTURE);

            foreach (array_chunk($matches[0], $chunk_size) as $chunk) {
                foreach ($chunk as $function_code) {
                    $doc = $this->get_ai_generated_documentation($function_code[0], $api_key);
                    if (!empty($doc)) {
                        $function_docs[] = "<div class='function-doc'><pre><code>" . esc_html($function_code[0]) . "</code></pre><p>" . nl2br(esc_html($doc)) . "</p></div>";
                    }
                }
            }
        }

        // Store generated documentation in a transient for 24 hours
        set_transient($this->transient_key, $function_docs, 24 * HOUR_IN_SECONDS);
    }

    // Mockup: Fetch documentation from AI (Replace this with real implementation)
    public function get_ai_generated_documentation($function_code, $api_key) {
        $prompt = "Document this PHP function:\n\n" . $function_code . "\n\nProvide a summary, parameters, and return value details.";

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an assistant that documents PHP functions.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 200,
                'temperature' => 0.7,
            ]),
        ]);

        if (is_wp_error($response)) {
            return 'Error: ' . $response->get_error_message();
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['choices'][0]['message']['content']) ? trim($body['choices'][0]['message']['content']) : 'No documentation available.';
    }
}

// Initialize the class
new ThemeDocumentationGenerator();