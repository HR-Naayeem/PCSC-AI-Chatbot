<?php
/**
 * Plugin Name: Tech Support Advisor - OpenAI ChatKit
 * Description: ChatKit workflow embedded via shortcode. Uses server-side session minting.
 * Version: 1.1.0
 * Author: Your Site
 */

if (!defined('ABSPATH')) exit;

final class PCSC_OpenAI_ChatKit {
  const SHORTCODE = 'tech_support_advisor_chat';
  const REST_NS   = 'pcsc-chatkit/v1';
  const AJAX_ACTION = 'pcsc_chatkit_client_secret';

  public static function init(): void {
    add_action('rest_api_init', [__CLASS__, 'register_rest_routes']);
    add_action('wp_enqueue_scripts', [__CLASS__, 'register_assets']);
    add_shortcode(self::SHORTCODE, [__CLASS__, 'render_shortcode']);

    add_action('wp_ajax_nopriv_' . self::AJAX_ACTION, [__CLASS__, 'ajax_client_secret']);
    add_action('wp_ajax_' . self::AJAX_ACTION, [__CLASS__, 'ajax_client_secret']);
  }

  public static function register_assets(): void {
    wp_register_script(
      'openai-chatkit',
      'https://cdn.platform.openai.com/deployments/chatkit/chatkit.js',
      [],
      null,
      true
    );

    $base_url  = plugin_dir_url(__FILE__);
    $base_path = plugin_dir_path(__FILE__);

    $css_path = $base_path . 'assets/chatkit-widget.css';
    $js_path  = $base_path . 'assets/chatkit-widget.js';

    $css_ver = file_exists($css_path) ? (string) filemtime($css_path) : '1.1.0';
    $js_ver  = file_exists($js_path) ? (string) filemtime($js_path) : '1.1.0';

    wp_register_style('pcsc-chatkit-ui', $base_url . 'assets/chatkit-widget.css', [], $css_ver);
    wp_register_script('pcsc-chatkit-ui', $base_url . 'assets/chatkit-widget.js', ['openai-chatkit'], $js_ver, true);
  }

  public static function render_shortcode($atts): string {
    $atts = shortcode_atts(
      [
        'height' => '600',
        'title'  => 'Tech Support Advisor',
      ],
      $atts,
      self::SHORTCODE
    );

    $height = preg_replace('/[^0-9]/', '', (string)$atts['height']);
    if ($height === '') $height = '600';

    $title = sanitize_text_field((string)$atts['title']);
    if ($title === '') $title = 'Tech Support Advisor';

    $endpoint = esc_url_raw(rest_url(self::REST_NS . '/client-secret'));
    $ajax_url = esc_url_raw(admin_url('admin-ajax.php'));
    $el_id = 'pcsc-openai-chatkit-' . wp_generate_uuid4();
    $logo_url = plugin_dir_url(__FILE__) . 'assets/pcsc-logo.png';

    wp_enqueue_script('openai-chatkit');
    wp_enqueue_style('pcsc-chatkit-ui');
    wp_enqueue_script('pcsc-chatkit-ui');

    $config = [
      'elId'        => $el_id,
      'endpoint'    => $endpoint,
      'ajaxUrl'     => $ajax_url,
      'ajaxAction'  => self::AJAX_ACTION,
      'height'      => (int)$height,
      'title'       => $title,
      'greeting'    => 'Aloha! How can I help you today?',
      'placeholder' => 'Chat with PCSC AI...',
      'disclaimer'  => 'For urgent or more help, call PCSC at 808-742-2700.',
      'prompts'     => [
        [
          'label'  => 'Slow Wi-Fi',
          'prompt' => 'My office Wi-Fi is slow. Give me step-by-step troubleshooting.'
        ],
        [
          'label'  => 'Outlook not syncing',
          'prompt' => 'Outlook stopped syncing email. Help me troubleshoot it.'
        ],
        [
          'label'  => 'Printer offline',
          'prompt' => 'My printer is offline. Walk me through the checks.'
        ],
      ],
    ];

    $inline = 'window.PCSC_CHATKIT_CONFIGS = window.PCSC_CHATKIT_CONFIGS || {};';
    $inline .= 'window.PCSC_CHATKIT_CONFIGS[' . wp_json_encode($el_id) . '] = ' . wp_json_encode($config) . ';';

    wp_add_inline_script('pcsc-chatkit-ui', $inline, 'before');

    ob_start();
    ?>
    <div class="pcsc-chatkit-root" data-chatkit-root="<?php echo esc_attr($el_id); ?>">
      <button type="button" class="pcsc-chatkit-btn" aria-label="Open chat" data-chatkit-open>
        <img src="<?php echo esc_url($logo_url); ?>" alt="PCSC" class="pcsc-chatkit-btn-logo" />
      </button>

      <div class="pcsc-chatkit-panel" role="dialog" aria-label="Chat" data-chatkit-panel style="--pcsc-chat-height: <?php echo esc_attr($height); ?>px;">
        <div class="pcsc-chatkit-header">
          <div class="pcsc-chatkit-header-left">
            <img src="<?php echo esc_url($logo_url); ?>" alt="PCSC" class="pcsc-chatkit-header-logo" />
            <div class="pcsc-chatkit-header-text"><?php echo esc_html($title); ?></div>
          </div>

          <button type="button" class="pcsc-chatkit-close" aria-label="Close chat" data-chatkit-close>×</button>
        </div>

        <div class="pcsc-chatkit-body">
          <div class="pcsc-chatkit-loading" data-chatkit-loading>Loading chat…</div>
          <openai-chatkit id="<?php echo esc_attr($el_id); ?>" style="display:block;width:100%;height:100%;"></openai-chatkit>
        </div>
      </div>
    </div>
    <?php
    return ob_get_clean();
  }

  public static function register_rest_routes(): void {
    register_rest_route(self::REST_NS, '/client-secret', [
      'methods'  => 'POST',
      'callback' => [__CLASS__, 'rest_client_secret'],
      'permission_callback' => '__return_true',
    ]);
  }

  public static function ajax_client_secret(): void {
    $resp = self::rest_client_secret(new WP_REST_Request('POST'));

    if ($resp instanceof WP_REST_Response) {
      $data = $resp->get_data();
      $status = (int) $resp->get_status();
    } else {
      $data = $resp;
      $status = 200;
    }

    if ($status >= 200 && $status < 300 && is_array($data) && !empty($data['client_secret'])) {
      wp_send_json_success(['client_secret' => $data['client_secret']]);
    }

    wp_send_json_error($data, $status ? $status : 500);
  }

  private static function require_constant(string $name): string {
    if (!defined($name) || !is_string(constant($name)) || constant($name) === '') {
      throw new RuntimeException("Missing required constant: {$name}");
    }
    return constant($name);
  }

  private static function get_visitor_user_id(): string {
    if (is_user_logged_in()) {
      return 'wp_user_' . (string)get_current_user_id();
    }

    $cookie_name = 'pcsc_chatkit_uid';
    if (!empty($_COOKIE[$cookie_name]) && is_string($_COOKIE[$cookie_name])) {
      $value = preg_replace('/[^a-zA-Z0-9_\-]/', '', $_COOKIE[$cookie_name]);
      if ($value !== '') {
        return 'anon_' . $value;
      }
    }

    $uid = wp_generate_uuid4();
    setcookie($cookie_name, $uid, [
      'expires'  => time() + 30 * DAY_IN_SECONDS,
      'path'     => COOKIEPATH ?: '/',
      'domain'   => COOKIE_DOMAIN ?: '',
      'secure'   => is_ssl(),
      'httponly' => true,
      'samesite' => 'Lax',
    ]);

    return 'anon_' . str_replace('-', '', $uid);
  }

  public static function rest_client_secret(WP_REST_Request $request) {
    try {
      $api_key     = self::require_constant('OPENAI_API_KEY');
      $workflow_id = self::require_constant('OPENAI_CHATKIT_WORKFLOW_ID');
      $workflow_version = defined('OPENAI_CHATKIT_WORKFLOW_VERSION') ? (string)OPENAI_CHATKIT_WORKFLOW_VERSION : '';
      $visitor_id = self::get_visitor_user_id();

      if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
      }

      $vis_rate_key = 'pcsc_ck_rate_vis_' . md5($visitor_id);
      $vis_count = (int) get_transient($vis_rate_key);
      if ($vis_count >= 15)                                   // Example public repo value
      {
        return new WP_REST_Response(['error' => 'Rate limit exceeded'], 429);
      }
      set_transient($vis_rate_key, $vis_count + 1, HOUR_IN_SECONDS);

      $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : 'unknown';
      $ip_rate_key = 'pcsc_ck_rate_ip_' . md5($ip);
      $ip_count = (int) get_transient($ip_rate_key);
      if ($ip_count >= 250)                                 // Example public repo value
      {
        return new WP_REST_Response(['error' => 'Rate limit exceeded'], 429);
      }
      set_transient($ip_rate_key, $ip_count + 1, HOUR_IN_SECONDS);

      $workflow = ['id' => $workflow_id];
      if ($workflow_version !== '') $workflow['version'] = $workflow_version;

      $payload = [
        'workflow' => $workflow,
        'user'     => $visitor_id,
      ];

      $res = wp_remote_post('https://api.openai.com/v1/chatkit/sessions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type'  => 'application/json',
          'OpenAI-Beta'   => 'chatkit_beta=v1',
        ],
        'body'    => wp_json_encode($payload),
        'timeout' => 20,
      ]);

      if (is_wp_error($res)) {
        return new WP_REST_Response(['error' => $res->get_error_message()], 500);
      }

      $status   = (int) wp_remote_retrieve_response_code($res);
      $body_raw = (string) wp_remote_retrieve_body($res);
      $body     = json_decode($body_raw, true);

      if ($status < 200 || $status >= 300) {
        return new WP_REST_Response([
          'error'  => 'OpenAI error creating ChatKit session',
          'status' => $status,
          'body'   => $body ?: $body_raw,
        ], 500);
      }

      if (!is_array($body) || empty($body['client_secret']) || !is_string($body['client_secret'])) {
        return new WP_REST_Response([
          'error' => 'Invalid OpenAI response (missing client_secret)',
          'body'  => $body ?: $body_raw,
        ], 500);
      }

      return new WP_REST_Response(['client_secret' => $body['client_secret']], 200);

    } catch (Throwable $e) {
      return new WP_REST_Response(['error' => $e->getMessage()], 500);
    }
  }
}

PCSC_OpenAI_ChatKit::init();
