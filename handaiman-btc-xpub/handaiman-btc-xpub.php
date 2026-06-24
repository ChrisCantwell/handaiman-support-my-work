<?php
/**
 * Plugin Name: HandAIMan Support My Work
 * Description: Displays configurable support options, fresh public-key-derived BTC/BCH/LTC contribution addresses, and optional static alternative crypto network addresses.
 * Version: 0.5.2
 * Author: HandAIMan / ChatGPT
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) { exit; }

class HandAIMan_Crypto_Contributions {
    const OPTION_KEY = 'ha_crypto_xpub_options';
    const OLD_OPTION_KEY = 'ha_btc_xpub_options';
    const COOKIE_KEY = 'ha_crypto_addr_token';
    const OLD_COOKIE_KEY = 'ha_btc_addr_token';
    const VERSION = '0.5.2';

    public static function init() {
        register_activation_hook(__FILE__, array(__CLASS__, 'activate'));
        add_action('init', array(__CLASS__, 'maybe_set_visitor_cookie'), 1);
        add_action('admin_menu', array(__CLASS__, 'admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));

        add_filter('the_content', array(__CLASS__, 'append_support_to_content'), 20);

        add_shortcode('handaiman_support', array(__CLASS__, 'support_shortcode'));
        add_shortcode('ha_support', array(__CLASS__, 'support_shortcode'));
        add_shortcode('handaiman_crypto', array(__CLASS__, 'shortcode'));
        add_shortcode('handaiman_btc', array(__CLASS__, 'shortcode'));
        add_shortcode('ha_btc_donate', array(__CLASS__, 'shortcode'));
        add_shortcode('handaiman_bch', array(__CLASS__, 'shortcode'));
        add_shortcode('handaiman_ltc', array(__CLASS__, 'shortcode'));
        add_shortcode('handaiman_alt_crypto', array(__CLASS__, 'alt_crypto_shortcode'));
    }

    public static function activate() {
        self::create_table();
        self::options(); // also performs one-time migration from the old BTC-only option, if present.
    }

    private static function defaults() {
        return array(
            'global' => array(
                'support_heading' => 'Support TheHandAIMan',
                'support_intro' => 'The HandAIMan is just a guy trying to keep the chaos at bay, with a little help from his friends. Care to be one?',
                'support_note' => '',
                'support_collapsed_summary' => 'Support the Project',
                'auto_append_collapsed' => 1,
                'auto_append_posts' => 0,
                'auto_append_podcasts' => 0,
                'label' => 'TheHandAIMan',
                'button_text' => 'Copy address',
                'reserve_days' => 30,
                'show_wallet_link' => 1,
                'alt_crypto_enabled' => 1,
                'alt_crypto_summary' => 'We also accept other cryptocurrency networks',
                'alt_crypto_intro' => 'Choose the correct network before sending. Tokens must be sent on the network shown for that address.',
                'alt_crypto_open' => 0,
            ),
            'methods' => array(
                'givesendgo' => array(
                    'enabled' => 1,
                    'label' => 'GiveSendGo',
                    'url' => 'https://www.givesendgo.com/the-handaiman-show-blog?utm_source=sharelink&utm_medium=copy_link&utm_campaign=the-handaiman-show-blog',
                    'description' => 'Contribute through GiveSendGo.',
                    'order' => 10,
                ),
                'cashapp' => array(
                    'enabled' => 1,
                    'label' => 'Cash App',
                    'url' => 'https://cash.app/$EdgyChris',
                    'description' => 'Send support through Cash App.',
                    'order' => 20,
                ),
                'paypal' => array(
                    'enabled' => 1,
                    'label' => 'PayPal',
                    'url' => 'https://paypal.me/thehandaiman',
                    'description' => 'Send support through PayPal.',
                    'order' => 30,
                ),
                'venmo' => array(
                    'enabled' => 1,
                    'label' => 'Venmo',
                    'url' => 'https://venmo.com/u/protalkercc',
                    'description' => 'Send support through Venmo.',
                    'order' => 40,
                ),
                'amazon' => array(
                    'enabled' => 1,
                    'label' => 'Amazon Wish List',
                    'url' => 'https://www.amazon.com/hz/wishlist/ls/VA4Y26AP8BXC?ref_=wl_share',
                    'description' => 'Tools, materials, and useful chaos-control supplies.',
                    'order' => 50,
                ),
                'custom1' => array(
                    'enabled' => 0,
                    'label' => 'Custom Support Link 1',
                    'url' => '',
                    'description' => '',
                    'order' => 90,
                ),
                'custom2' => array(
                    'enabled' => 0,
                    'label' => 'Custom Support Link 2',
                    'url' => '',
                    'description' => '',
                    'order' => 100,
                ),
            ),
            'coins' => array(
                'btc' => array(
                    'enabled' => 1,
                    'xpub' => '',
                    'title' => 'Bitcoin',
                    'intro' => 'Prefer Bitcoin? Send support to this fresh address:',
                    'start_index' => 0,
                    'next_index' => 0,
                ),
                'bch' => array(
                    'enabled' => 0,
                    'xpub' => '',
                    'title' => 'Bitcoin Cash',
                    'intro' => 'Prefer Bitcoin Cash? Send support to this fresh address:',
                    'start_index' => 0,
                    'next_index' => 0,
                ),
                'ltc' => array(
                    'enabled' => 0,
                    'xpub' => '',
                    'title' => 'Litecoin',
                    'intro' => 'Prefer Litecoin? Send support to this fresh address:',
                    'start_index' => 0,
                    'next_index' => 0,
                ),
            ),
            'alt_networks' => array(
                'eth' => array(
                    'enabled' => 0,
                    'order' => 10,
                    'label' => 'Ethereum network',
                    'ticker' => 'ETH / ERC-20',
                    'network' => 'Ethereum',
                    'accepts' => 'ETH and supported Ethereum/ERC-20 tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Ethereum mainnet to this address.',
                ),
                'base' => array(
                    'enabled' => 0,
                    'order' => 20,
                    'label' => 'Base network',
                    'ticker' => 'ETH / Base tokens',
                    'network' => 'Base',
                    'accepts' => 'ETH on Base and supported Base tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Base to this address.',
                ),
                'solana' => array(
                    'enabled' => 0,
                    'order' => 30,
                    'label' => 'Solana network',
                    'ticker' => 'SOL / SPL',
                    'network' => 'Solana',
                    'accepts' => 'SOL and supported Solana/SPL tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Solana to this address.',
                ),
                'polygon' => array(
                    'enabled' => 0,
                    'order' => 40,
                    'label' => 'Polygon network',
                    'ticker' => 'POL / Polygon tokens',
                    'network' => 'Polygon',
                    'accepts' => 'POL/MATIC and supported Polygon tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Polygon to this address.',
                ),
                'arbitrum' => array(
                    'enabled' => 0,
                    'order' => 50,
                    'label' => 'Arbitrum One network',
                    'ticker' => 'ETH / Arbitrum tokens',
                    'network' => 'Arbitrum One',
                    'accepts' => 'ETH on Arbitrum One and supported Arbitrum tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Arbitrum One to this address.',
                ),
                'optimism' => array(
                    'enabled' => 0,
                    'order' => 60,
                    'label' => 'Optimism network',
                    'ticker' => 'ETH / Optimism tokens',
                    'network' => 'Optimism',
                    'accepts' => 'ETH on Optimism and supported Optimism tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Optimism to this address.',
                ),
                'bsc' => array(
                    'enabled' => 0,
                    'order' => 70,
                    'label' => 'BNB Smart Chain network',
                    'ticker' => 'BNB / BEP-20',
                    'network' => 'BNB Smart Chain',
                    'accepts' => 'BNB and supported BNB Smart Chain/BEP-20 tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on BNB Smart Chain to this address.',
                ),
                'tron' => array(
                    'enabled' => 0,
                    'order' => 80,
                    'label' => 'Tron network',
                    'ticker' => 'TRX / TRC-20',
                    'network' => 'Tron',
                    'accepts' => 'TRX and supported Tron/TRC-20 tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Tron to this address.',
                ),
                'avalanche' => array(
                    'enabled' => 0,
                    'order' => 90,
                    'label' => 'Avalanche C-Chain network',
                    'ticker' => 'AVAX / C-Chain tokens',
                    'network' => 'Avalanche C-Chain',
                    'accepts' => 'AVAX and supported Avalanche C-Chain tokens',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only C-Chain assets to this address.',
                ),
                'cardano' => array(
                    'enabled' => 0,
                    'order' => 100,
                    'label' => 'Cardano network',
                    'ticker' => 'ADA',
                    'network' => 'Cardano',
                    'accepts' => 'ADA and supported Cardano native assets',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only assets on Cardano to this address.',
                ),
                'xrp' => array(
                    'enabled' => 0,
                    'order' => 110,
                    'label' => 'XRP Ledger network',
                    'ticker' => 'XRP',
                    'network' => 'XRP Ledger',
                    'accepts' => 'XRP on the XRP Ledger',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Use the destination tag shown here only if one is provided.',
                ),
                'monero' => array(
                    'enabled' => 0,
                    'order' => 120,
                    'label' => 'Monero network',
                    'ticker' => 'XMR',
                    'network' => 'Monero',
                    'accepts' => 'XMR on Monero',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only Monero/XMR to this address.',
                ),
                'dogecoin' => array(
                    'enabled' => 0,
                    'order' => 130,
                    'label' => 'Dogecoin network',
                    'ticker' => 'DOGE',
                    'network' => 'Dogecoin',
                    'accepts' => 'DOGE on Dogecoin',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only Dogecoin/DOGE to this address.',
                ),
                'dash' => array(
                    'enabled' => 0,
                    'order' => 140,
                    'label' => 'Dash network',
                    'ticker' => 'DASH',
                    'network' => 'Dash',
                    'accepts' => 'DASH on Dash',
                    'address' => '',
                    'memo' => '',
                    'note' => 'Send only Dash/DASH to this address.',
                ),
                'custom1' => array(
                    'enabled' => 0,
                    'order' => 900,
                    'label' => 'Custom crypto network 1',
                    'ticker' => '',
                    'network' => '',
                    'accepts' => '',
                    'address' => '',
                    'memo' => '',
                    'note' => '',
                ),
                'custom2' => array(
                    'enabled' => 0,
                    'order' => 910,
                    'label' => 'Custom crypto network 2',
                    'ticker' => '',
                    'network' => '',
                    'accepts' => '',
                    'address' => '',
                    'memo' => '',
                    'note' => '',
                ),
                'custom3' => array(
                    'enabled' => 0,
                    'order' => 920,
                    'label' => 'Custom crypto network 3',
                    'ticker' => '',
                    'network' => '',
                    'accepts' => '',
                    'address' => '',
                    'memo' => '',
                    'note' => '',
                ),
                'custom4' => array(
                    'enabled' => 0,
                    'order' => 930,
                    'label' => 'Custom crypto network 4',
                    'ticker' => '',
                    'network' => '',
                    'accepts' => '',
                    'address' => '',
                    'memo' => '',
                    'note' => '',
                ),
                'custom5' => array(
                    'enabled' => 0,
                    'order' => 940,
                    'label' => 'Custom crypto network 5',
                    'ticker' => '',
                    'network' => '',
                    'accepts' => '',
                    'address' => '',
                    'memo' => '',
                    'note' => '',
                ),
            ),
        );
    }

    private static function coin_defs() {
        return array(
            'btc' => array(
                'ticker' => 'BTC',
                'name' => 'Bitcoin',
                'scheme' => 'bitcoin',
                'xpub_label' => 'XPUB / YPUB / ZPUB',
                'xpub_help' => 'Bitcoin account-level xpub/ypub/zpub. Derives external receive addresses at /0/n.',
                'versions' => array(
                    '0488b21e' => 'p2pkh',       // xpub mainnet
                    '049d7cb2' => 'p2sh_p2wpkh', // ypub mainnet
                    '04b24746' => 'p2wpkh',      // zpub mainnet
                ),
                'private_versions' => array('0488ade4','049d7878','04b2430c'),
                'p2pkh_version' => 0x00,
                'p2sh_version' => 0x05,
                'bech32_hrp' => 'bc',
                'address_modes' => array('p2pkh', 'p2sh_p2wpkh', 'p2wpkh'),
            ),
            'bch' => array(
                'ticker' => 'BCH',
                'name' => 'Bitcoin Cash',
                'scheme' => 'bitcoincash',
                'xpub_label' => 'BCH XPUB',
                'xpub_help' => 'Bitcoin Cash account-level xpub. Derives external receive addresses at /0/n and displays CashAddr addresses. Do not paste a BTC wallet xpub here.',
                'versions' => array(
                    '0488b21e' => 'p2pkh', // xpub mainnet
                ),
                'private_versions' => array('0488ade4','049d7878','04b2430c'),
                'cashaddr_prefix' => 'bitcoincash',
                'address_modes' => array('p2pkh'),
            ),
            'ltc' => array(
                'ticker' => 'LTC',
                'name' => 'Litecoin',
                'scheme' => 'litecoin',
                'xpub_label' => 'Litecoin XPUB / extended public key',
                'xpub_help' => 'Litecoin account-level extended public key. Supports common xpub/ypub/zpub-style keys and Litecoin Ltub/Mtub variants. Derives external receive addresses at /0/n. Do not paste a BTC wallet xpub here.',
                'versions' => array(
                    '0488b21e' => 'p2pkh',       // xpub-style Litecoin wallets
                    '049d7cb2' => 'p2sh_p2wpkh', // ypub-style Litecoin wallets
                    '04b24746' => 'p2wpkh',      // zpub-style Litecoin wallets
                    '019da462' => 'p2sh_p2wpkh', // Ltub, commonly nested SegWit
                    '01b26ef6' => 'p2wpkh',      // Mtub, commonly native SegWit
                ),
                'private_versions' => array('0488ade4','049d7878','04b2430c','019d9cfe','01b26792'),
                'p2pkh_version' => 0x30, // L...
                'p2sh_version' => 0x32,  // M...
                'bech32_hrp' => 'ltc',   // ltc1...
                'address_modes' => array('p2pkh', 'p2sh_p2wpkh', 'p2wpkh'),
            ),
        );
    }

    private static function recursive_merge_defaults($defaults, $value) {
        if (!is_array($value)) { return $defaults; }
        $out = $defaults;
        foreach ($value as $k => $v) {
            if (isset($defaults[$k]) && is_array($defaults[$k])) {
                $out[$k] = self::recursive_merge_defaults($defaults[$k], $v);
            } else {
                $out[$k] = $v;
            }
        }
        return $out;
    }

    private static function migrate_old_options_if_needed($existing) {
        if (!empty($existing) && is_array($existing)) { return $existing; }
        $old = get_option(self::OLD_OPTION_KEY, array());
        if (empty($old) || !is_array($old)) { return $existing; }

        $migrated = self::defaults();
        $migrated['global']['label'] = isset($old['label']) ? $old['label'] : $migrated['global']['label'];
        $migrated['global']['button_text'] = isset($old['button_text']) ? $old['button_text'] : $migrated['global']['button_text'];
        $migrated['global']['reserve_days'] = isset($old['reserve_days']) ? intval($old['reserve_days']) : $migrated['global']['reserve_days'];
        $migrated['global']['show_wallet_link'] = isset($old['show_bip21']) ? intval($old['show_bip21']) : $migrated['global']['show_wallet_link'];

        $migrated['coins']['btc']['enabled'] = 1;
        $migrated['coins']['btc']['xpub'] = isset($old['xpub']) ? $old['xpub'] : '';
        $migrated['coins']['btc']['title'] = isset($old['title']) ? $old['title'] : $migrated['coins']['btc']['title'];
        $migrated['coins']['btc']['intro'] = isset($old['intro']) ? $old['intro'] : $migrated['coins']['btc']['intro'];
        $migrated['coins']['btc']['start_index'] = isset($old['start_index']) ? intval($old['start_index']) : 0;
        $migrated['coins']['btc']['next_index'] = isset($old['next_index']) ? intval($old['next_index']) : $migrated['coins']['btc']['start_index'];

        update_option(self::OPTION_KEY, $migrated);
        return $migrated;
    }

    private static function options() {
        $existing = get_option(self::OPTION_KEY, array());
        $existing = self::migrate_old_options_if_needed($existing);
        $merged = self::recursive_merge_defaults(self::defaults(), is_array($existing) ? $existing : array());
        return $merged;
    }

    private static function table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ha_crypto_addresses';
    }

    private static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $table = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            currency varchar(16) NOT NULL,
            token_hash varchar(64) DEFAULT NULL,
            address_index bigint(20) unsigned NOT NULL,
            address varchar(256) NOT NULL,
            payment_uri text DEFAULT NULL,
            visitor_hash varchar(64) DEFAULT NULL,
            created_at datetime NOT NULL,
            reserved_until datetime DEFAULT NULL,
            status varchar(20) NOT NULL DEFAULT 'reserved',
            PRIMARY KEY  (id),
            UNIQUE KEY currency_address_index (currency, address_index),
            UNIQUE KEY currency_address (currency, address(191)),
            KEY currency_token_status (currency, token_hash, status),
            KEY status (status)
        ) $charset_collate;";
        dbDelta($sql);
    }

    public static function maybe_set_visitor_cookie() {
        if (is_admin()) { return; }
        $opts = self::options();
        $days = max(1, min(365, intval($opts['global']['reserve_days'])));

        $token = '';
        if (isset($_COOKIE[self::COOKIE_KEY])) {
            $token = sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_KEY]));
        } elseif (isset($_COOKIE[self::OLD_COOKIE_KEY])) {
            $token = sanitize_text_field(wp_unslash($_COOKIE[self::OLD_COOKIE_KEY]));
        }

        if (!preg_match('/^[A-Za-z0-9]{32,80}$/', $token)) {
            $token = wp_generate_password(48, false, false);
        }

        $_COOKIE[self::COOKIE_KEY] = $token;
        if (!headers_sent()) {
            setcookie(self::COOKIE_KEY, $token, time() + DAY_IN_SECONDS * $days, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        }
    }

    public static function admin_menu() {
        if (function_exists('handaistack_parent_slug')) {
            add_submenu_page(
                handaistack_parent_slug(),
                'HandAIMan Support My Work',
                'Support',
                'manage_options',
                'ha-crypto-contributions',
                array(__CLASS__, 'settings_page')
            );
        } else {
            add_options_page(
                'HandAIMan Support My Work',
                'HandAIMan Support',
                'manage_options',
                'ha-crypto-contributions',
                array(__CLASS__, 'settings_page')
            );
        }
    }

    public static function register_settings() {
        register_setting('ha_crypto_xpub_group', self::OPTION_KEY, array(__CLASS__, 'sanitize_options'));
    }

    public static function sanitize_options($input) {
        $old = self::options();
        $out = self::defaults();
        $defs = self::coin_defs();

        $in_global = isset($input['global']) && is_array($input['global']) ? $input['global'] : array();
        $out['global']['support_heading'] = isset($in_global['support_heading']) ? sanitize_text_field($in_global['support_heading']) : $out['global']['support_heading'];
        $out['global']['support_intro'] = isset($in_global['support_intro']) ? sanitize_textarea_field($in_global['support_intro']) : $out['global']['support_intro'];
        $out['global']['support_note'] = isset($in_global['support_note']) ? sanitize_textarea_field($in_global['support_note']) : $out['global']['support_note'];
        $out['global']['support_collapsed_summary'] = isset($in_global['support_collapsed_summary']) ? sanitize_text_field($in_global['support_collapsed_summary']) : $out['global']['support_collapsed_summary'];
        $out['global']['auto_append_collapsed'] = !empty($in_global['auto_append_collapsed']) ? 1 : 0;
        $out['global']['auto_append_posts'] = !empty($in_global['auto_append_posts']) ? 1 : 0;
        $out['global']['auto_append_podcasts'] = !empty($in_global['auto_append_podcasts']) ? 1 : 0;
        $out['global']['label'] = isset($in_global['label']) ? sanitize_text_field($in_global['label']) : $out['global']['label'];
        $out['global']['button_text'] = isset($in_global['button_text']) ? sanitize_text_field($in_global['button_text']) : $out['global']['button_text'];
        $out['global']['reserve_days'] = isset($in_global['reserve_days']) ? max(1, min(365, intval($in_global['reserve_days']))) : 30;
        $out['global']['show_wallet_link'] = !empty($in_global['show_wallet_link']) ? 1 : 0;
        $out['global']['alt_crypto_enabled'] = !empty($in_global['alt_crypto_enabled']) ? 1 : 0;
        $out['global']['alt_crypto_summary'] = isset($in_global['alt_crypto_summary']) ? sanitize_text_field($in_global['alt_crypto_summary']) : $out['global']['alt_crypto_summary'];
        $out['global']['alt_crypto_intro'] = isset($in_global['alt_crypto_intro']) ? sanitize_textarea_field($in_global['alt_crypto_intro']) : $out['global']['alt_crypto_intro'];
        $out['global']['alt_crypto_open'] = !empty($in_global['alt_crypto_open']) ? 1 : 0;

        $in_methods = isset($input['methods']) && is_array($input['methods']) ? $input['methods'] : array();
        foreach ($out['methods'] as $method => $method_defaults) {
            $in = isset($in_methods[$method]) && is_array($in_methods[$method]) ? $in_methods[$method] : array();
            $out['methods'][$method]['enabled'] = !empty($in['enabled']) ? 1 : 0;
            $out['methods'][$method]['label'] = isset($in['label']) ? sanitize_text_field($in['label']) : $method_defaults['label'];
            $out['methods'][$method]['url'] = isset($in['url']) ? esc_url_raw(trim($in['url'])) : '';
            $out['methods'][$method]['description'] = isset($in['description']) ? sanitize_text_field($in['description']) : '';
            $out['methods'][$method]['order'] = isset($in['order']) ? intval($in['order']) : intval($method_defaults['order']);
        }

        $in_coins = isset($input['coins']) && is_array($input['coins']) ? $input['coins'] : array();
        foreach ($defs as $coin => $def) {
            $in = isset($in_coins[$coin]) && is_array($in_coins[$coin]) ? $in_coins[$coin] : array();
            $old_coin = isset($old['coins'][$coin]) && is_array($old['coins'][$coin]) ? $old['coins'][$coin] : $out['coins'][$coin];

            $out['coins'][$coin]['enabled'] = !empty($in['enabled']) ? 1 : 0;
            $out['coins'][$coin]['xpub'] = isset($in['xpub']) ? trim(sanitize_textarea_field($in['xpub'])) : '';
            $out['coins'][$coin]['title'] = isset($in['title']) ? sanitize_text_field($in['title']) : $out['coins'][$coin]['title'];
            $out['coins'][$coin]['intro'] = isset($in['intro']) ? sanitize_text_field($in['intro']) : $out['coins'][$coin]['intro'];
            $out['coins'][$coin]['start_index'] = isset($in['start_index']) ? max(0, intval($in['start_index'])) : 0;

            if (!empty($in['reset_next_index'])) {
                $out['coins'][$coin]['next_index'] = $out['coins'][$coin]['start_index'];
            } else {
                $old_next = isset($old_coin['next_index']) ? intval($old_coin['next_index']) : $out['coins'][$coin]['start_index'];
                $out['coins'][$coin]['next_index'] = max($out['coins'][$coin]['start_index'], $old_next);
            }
        }

        $in_alt = isset($input['alt_networks']) && is_array($input['alt_networks']) ? $input['alt_networks'] : array();
        foreach ($out['alt_networks'] as $key => $network_defaults) {
            $in = isset($in_alt[$key]) && is_array($in_alt[$key]) ? $in_alt[$key] : array();
            $out['alt_networks'][$key]['enabled'] = !empty($in['enabled']) ? 1 : 0;
            $out['alt_networks'][$key]['order'] = isset($in['order']) ? intval($in['order']) : intval($network_defaults['order']);
            $out['alt_networks'][$key]['label'] = isset($in['label']) ? sanitize_text_field($in['label']) : $network_defaults['label'];
            $out['alt_networks'][$key]['ticker'] = isset($in['ticker']) ? sanitize_text_field($in['ticker']) : $network_defaults['ticker'];
            $out['alt_networks'][$key]['network'] = isset($in['network']) ? sanitize_text_field($in['network']) : $network_defaults['network'];
            $out['alt_networks'][$key]['accepts'] = isset($in['accepts']) ? sanitize_text_field($in['accepts']) : $network_defaults['accepts'];
            $out['alt_networks'][$key]['address'] = isset($in['address']) ? trim(sanitize_textarea_field($in['address'])) : '';
            $out['alt_networks'][$key]['memo'] = isset($in['memo']) ? trim(sanitize_text_field($in['memo'])) : '';
            $out['alt_networks'][$key]['note'] = isset($in['note']) ? sanitize_text_field($in['note']) : '';
        }

        return $out;
    }

    public static function settings_page() {
        if (!current_user_can('manage_options')) { return; }
        self::create_table();
        $opts = self::options();
        $defs = self::coin_defs();
        $gmp_ok = extension_loaded('gmp');
        ?>
        <div class="wrap">
            <h1>HandAIMan Support My Work</h1>
            <?php if (!$gmp_ok): ?>
                <div class="notice notice-error"><p><strong>PHP GMP is not loaded.</strong> This plugin needs the PHP GMP extension for BIP32 public-key derivation. Install/enable GMP for the PHP version used by WordPress, then restart PHP/OpenLiteSpeed/Apache.</p></div>
            <?php endif; ?>
            <p>This plugin renders configurable support links and, optionally, fresh cryptocurrency receive addresses derived from account-level extended public keys. It stores no seed phrases, private keys, xprv/yprv/zprv, or spending keys.</p>
            <p><strong>Main shortcode:</strong> <code>[handaiman_support]</code>. Collapsed support: <code>[handaiman_support collapsed=&quot;yes&quot;]</code>. Crypto-only shortcode: <code>[handaiman_crypto]</code>. Legacy BTC shortcodes still work: <code>[handaiman_btc]</code> and <code>[ha_btc_donate]</code>. Single-currency shortcodes also exist: <code>[handaiman_bch]</code> and <code>[handaiman_ltc]</code>. Static alternative crypto networks can be shown with <code>[handaiman_alt_crypto]</code> or inside <code>[handaiman_support]</code>.</p>

            <form method="post" action="options.php">
                <?php settings_fields('ha_crypto_xpub_group'); ?>
                <?php submit_button('Save Changes', 'primary', 'submit_top'); ?>

                <h2>Support box</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="ha_support_heading">Heading</label></th><td><input id="ha_support_heading" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][support_heading]" value="<?php echo esc_attr($opts['global']['support_heading']); ?>"></td></tr>
                    <tr><th scope="row"><label for="ha_support_intro">Intro text</label></th><td><textarea id="ha_support_intro" class="large-text" rows="3" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][support_intro]"><?php echo esc_textarea($opts['global']['support_intro']); ?></textarea></td></tr>
                    <tr><th scope="row"><label for="ha_support_note">Closing note</label></th><td><textarea id="ha_support_note" class="large-text" rows="2" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][support_note]"><?php echo esc_textarea($opts['global']['support_note']); ?></textarea><p class="description">Optional text shown below the support links.</p></td></tr>
                    <tr><th scope="row"><label for="ha_support_collapsed_summary">Collapsed summary</label></th><td><input id="ha_support_collapsed_summary" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][support_collapsed_summary]" value="<?php echo esc_attr($opts['global']['support_collapsed_summary']); ?>"><p class="description">Clickable label used when support is rendered collapsed, such as in post footers.</p></td></tr>
                    <tr><th scope="row">Auto-append</th><td>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][auto_append_posts]" value="1" <?php checked($opts['global']['auto_append_posts'], 1); ?>> Automatically append <code>[handaiman_support]</code> to blog posts</label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][auto_append_podcasts]" value="1" <?php checked($opts['global']['auto_append_podcasts'], 1); ?>> Automatically append <code>[handaiman_support]</code> to podcast episodes</label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][auto_append_collapsed]" value="1" <?php checked($opts['global']['auto_append_collapsed'], 1); ?>> Render auto-appended support collapsed by default</label>
                    </td></tr>
                </table>

                <h2>Support methods</h2>
                <p>Enable the support options you want to show. The URL field can be a PayPal, Venmo, Cash App, GiveSendGo, Amazon Wish List, or any ordinary link.</p>
                <table class="widefat striped" style="max-width: 1100px;">
                    <thead><tr><th>Enabled</th><th>Order</th><th>Method</th><th>Button label</th><th>URL</th><th>Description</th></tr></thead>
                    <tbody>
                    <?php foreach ($opts['methods'] as $method => $m): ?>
                        <tr>
                            <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[methods][<?php echo esc_attr($method); ?>][enabled]" value="1" <?php checked($m['enabled'], 1); ?>></td>
                            <td><input type="number" style="width:70px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[methods][<?php echo esc_attr($method); ?>][order]" value="<?php echo esc_attr($m['order']); ?>"></td>
                            <td><code><?php echo esc_html($method); ?></code></td>
                            <td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[methods][<?php echo esc_attr($method); ?>][label]" value="<?php echo esc_attr($m['label']); ?>"></td>
                            <td><input class="large-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[methods][<?php echo esc_attr($method); ?>][url]" value="<?php echo esc_attr($m['url']); ?>"></td>
                            <td><input class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[methods][<?php echo esc_attr($method); ?>][description]" value="<?php echo esc_attr($m['description']); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <h2>Crypto display settings</h2>
                <table class="form-table" role="presentation">
                    <tr><th scope="row"><label for="ha_crypto_label">Wallet label</label></th><td><input id="ha_crypto_label" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][label]" value="<?php echo esc_attr($opts['global']['label']); ?>"></td></tr>
                    <tr><th scope="row"><label for="ha_crypto_button">Copy button text</label></th><td><input id="ha_crypto_button" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][button_text]" value="<?php echo esc_attr($opts['global']['button_text']); ?>"></td></tr>
                    <tr><th scope="row"><label for="ha_crypto_reserve">Reserve days</label></th><td><input id="ha_crypto_reserve" type="number" min="1" max="365" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][reserve_days]" value="<?php echo esc_attr($opts['global']['reserve_days']); ?>"></td></tr>
                    <tr><th scope="row">Wallet links</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][show_wallet_link]" value="1" <?php checked($opts['global']['show_wallet_link'], 1); ?>> Show an "Open wallet" link for each currency</label></td></tr>
                    <tr><th scope="row">Other crypto networks</th><td>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][alt_crypto_enabled]" value="1" <?php checked($opts['global']['alt_crypto_enabled'], 1); ?>> Enable collapsed static-address network section in <code>[handaiman_support]</code></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][alt_crypto_open]" value="1" <?php checked($opts['global']['alt_crypto_open'], 1); ?>> Open this section by default</label>
                    </td></tr>
                    <tr><th scope="row"><label for="ha_alt_crypto_summary">Other crypto summary</label></th><td><input id="ha_alt_crypto_summary" class="large-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][alt_crypto_summary]" value="<?php echo esc_attr($opts['global']['alt_crypto_summary']); ?>"></td></tr>
                    <tr><th scope="row"><label for="ha_alt_crypto_intro">Other crypto intro</label></th><td><textarea id="ha_alt_crypto_intro" class="large-text" rows="2" name="<?php echo esc_attr(self::OPTION_KEY); ?>[global][alt_crypto_intro]"><?php echo esc_textarea($opts['global']['alt_crypto_intro']); ?></textarea></td></tr>
                </table>

                <h2>Cryptocurrency receive addresses</h2>
                <?php foreach ($defs as $coin => $def):
                    $c = $opts['coins'][$coin];
                    $test = null;
                    if ($gmp_ok && !empty($c['xpub'])) {
                        try {
                            $test = self::derive_receive_address($coin, $c['xpub'], intval($c['start_index']));
                        } catch (Exception $e) {
                            $test = 'ERROR: ' . $e->getMessage();
                        }
                    }
                ?>
                    <h3><?php echo esc_html($def['name'] . ' (' . $def['ticker'] . ')'); ?></h3>
                    <table class="form-table" role="presentation">
                        <tr><th scope="row">Enabled</th><td><label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][enabled]" value="1" <?php checked($c['enabled'], 1); ?>> Display this currency in <code>[handaiman_crypto]</code></label></td></tr>
                        <tr>
                            <th scope="row"><label for="ha_crypto_<?php echo esc_attr($coin); ?>_xpub"><?php echo esc_html($def['xpub_label']); ?></label></th>
                            <td>
                                <textarea id="ha_crypto_<?php echo esc_attr($coin); ?>_xpub" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][xpub]" rows="3" cols="90" class="large-text code"><?php echo esc_textarea($c['xpub']); ?></textarea>
                                <p class="description"><?php echo esc_html($def['xpub_help']); ?> Never paste a seed phrase, private key, or private extended key.</p>
                            </td>
                        </tr>
                        <tr><th scope="row"><label for="ha_crypto_<?php echo esc_attr($coin); ?>_title">Display title</label></th><td><input id="ha_crypto_<?php echo esc_attr($coin); ?>_title" class="regular-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][title]" value="<?php echo esc_attr($c['title']); ?>"></td></tr>
                        <tr><th scope="row"><label for="ha_crypto_<?php echo esc_attr($coin); ?>_intro">Intro text</label></th><td><input id="ha_crypto_<?php echo esc_attr($coin); ?>_intro" class="large-text" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][intro]" value="<?php echo esc_attr($c['intro']); ?>"></td></tr>
                        <tr><th scope="row"><label for="ha_crypto_<?php echo esc_attr($coin); ?>_start">Start index</label></th><td><input id="ha_crypto_<?php echo esc_attr($coin); ?>_start" type="number" min="0" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][start_index]" value="<?php echo esc_attr($c['start_index']); ?>"> <p class="description">Usually 0. If this wallet already used addresses, set this beyond the last used receive index.</p></td></tr>
                        <tr><th scope="row">Current next index</th><td><code><?php echo esc_html($c['next_index']); ?></code> <label><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[coins][<?php echo esc_attr($coin); ?>][reset_next_index]" value="1"> Reset next index to start index on save</label></td></tr>
                        <?php if ($test): ?>
                            <tr><th scope="row">Test address at start index</th><td><code><?php echo esc_html(is_array($test) ? $test['address'] : $test); ?></code><p class="description">Verify this against your wallet before accepting contributions.</p></td></tr>
                        <?php endif; ?>
                    </table>
                <?php endforeach; ?>

                <h2>Other cryptocurrency networks</h2>
                <p>These are static receive addresses for chains/networks where one address receives the native coin and supported tokens on that same network. They render collapsed by default on the front end.</p>
                <table class="widefat striped" style="max-width: 1200px;">
                    <thead><tr><th>Enabled</th><th>Order</th><th>Key</th><th>Label</th><th>Ticker</th><th>Network</th><th>Accepts</th><th>Address</th><th>Memo/tag</th><th>Note</th></tr></thead>
                    <tbody>
                    <?php foreach ($opts['alt_networks'] as $key => $n): ?>
                        <tr>
                            <td><input type="checkbox" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked($n['enabled'], 1); ?>></td>
                            <td><input type="number" style="width:70px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][order]" value="<?php echo esc_attr($n['order']); ?>"></td>
                            <td><code><?php echo esc_html($key); ?></code></td>
                            <td><input style="width:170px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][label]" value="<?php echo esc_attr($n['label']); ?>"></td>
                            <td><input style="width:130px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][ticker]" value="<?php echo esc_attr($n['ticker']); ?>"></td>
                            <td><input style="width:140px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][network]" value="<?php echo esc_attr($n['network']); ?>"></td>
                            <td><input style="width:220px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][accepts]" value="<?php echo esc_attr($n['accepts']); ?>"></td>
                            <td><textarea rows="2" style="width:260px;" class="code" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][address]"><?php echo esc_textarea($n['address']); ?></textarea></td>
                            <td><input style="width:130px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][memo]" value="<?php echo esc_attr($n['memo']); ?>"></td>
                            <td><input style="width:240px;" name="<?php echo esc_attr(self::OPTION_KEY); ?>[alt_networks][<?php echo esc_attr($key); ?>][note]" value="<?php echo esc_attr($n['note']); ?>"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button('Save Changes', 'primary', 'submit_bottom'); ?>
            </form>
            <hr>
            <h2>Recent reserved addresses</h2>
            <?php self::render_recent_addresses(); ?>
            <h2>Security notes</h2>
            <ul style="list-style: disc; margin-left: 2em;">
                <li>Extended public keys cannot spend your funds, but they can reveal wallet receive-address history and future receive addresses.</li>
                <li>Use a separate wallet/account-level extended public key for each currency. Do not paste a Bitcoin xpub into the BCH or LTC setting unless that key came from the corresponding coin wallet/account.</li>
                <li>If an attacker gets WordPress admin access, they can change contribution addresses and redirect funds. Protect WordPress admin accordingly.</li>
                <li>For static alternative crypto networks, verify each receive address in Exodus and label network-specific entries clearly. A token sent on the wrong network may be unrecoverable.</li>
                <li>Always verify the first few derived addresses against your wallet before publishing the shortcode.</li>
            </ul>
        </div>
        <?php
    }

    private static function render_recent_addresses() {
        global $wpdb;
        $table = self::table_name();
        $rows = $wpdb->get_results("SELECT currency, address_index, address, created_at, reserved_until, status FROM $table ORDER BY id DESC LIMIT 30");
        if (!$rows) { echo '<p>No addresses reserved yet.</p>'; return; }
        echo '<table class="widefat striped"><thead><tr><th>Currency</th><th>Index</th><th>Address</th><th>Status</th><th>Created</th><th>Reserved until</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            echo '<tr><td>' . esc_html(strtoupper($r->currency)) . '</td><td>' . esc_html($r->address_index) . '</td><td><code>' . esc_html($r->address) . '</code></td><td>' . esc_html($r->status) . '</td><td>' . esc_html($r->created_at) . '</td><td>' . esc_html($r->reserved_until) . '</td></tr>';
        }
        echo '</tbody></table>';
    }

    private static function is_noish($value) {
        return in_array(strtolower(trim((string) $value)), array('0', 'false', 'no', 'off'), true);
    }

    private static function is_yesish($value) {
        return in_array(strtolower(trim((string) $value)), array('1', 'true', 'yes', 'on', 'open'), true);
    }

    public static function support_shortcode($atts = array(), $content = null, $tag = '') {
        $opts = self::options();
        $atts = shortcode_atts(array(
            'crypto' => 'yes',
            'alt_crypto' => 'yes',
            'alt_crypto_open' => '',
            'links' => 'yes',
            'currencies' => '',
            'networks' => '',
            'methods' => '',
            'collapsed' => 'no',
            'open' => 'no',
            'summary' => '',
        ), $atts, $tag);

        $show_links = !self::is_noish($atts['links']);
        $show_crypto = !self::is_noish($atts['crypto']);
        $show_alt_crypto = $show_crypto && !self::is_noish($atts['alt_crypto']);

        $parts = array();
        if ($show_links) {
            $parts[] = self::render_support_links($opts, $atts['methods']);
        }
        if ($show_crypto) {
            $parts[] = self::shortcode(array('currencies' => $atts['currencies']), null, 'handaiman_crypto');
        }
        if ($show_alt_crypto) {
            $parts[] = self::render_alt_crypto_networks($opts, $atts['networks'], $atts['alt_crypto_open']);
        }

        $parts = array_values(array_filter($parts));
        if (empty($parts)) { return ''; }

        $body = '<div class="ha-support-my-work-wrap">' . implode("\n", $parts) . '</div>';
        if (self::is_yesish($atts['collapsed'])) {
            $summary = trim((string) $atts['summary']);
            if ($summary === '') { $summary = !empty($opts['global']['support_collapsed_summary']) ? $opts['global']['support_collapsed_summary'] : 'Support the Project'; }
            $open = self::is_yesish($atts['open']);
            $body = '<details class="ha-support-collapsible ha-cta-panel"' . ($open ? ' open' : '') . ' style="border:1px solid #dcdcde; padding:16px; border-radius:8px; max-width:760px; margin:1em 0; background:#fff; box-sizing:border-box;"><summary style="cursor:pointer; font-weight:600; font-size:1.05em;">' . esc_html($summary) . '</summary><div class="ha-support-collapsible-inner">' . $body . '</div></details>';
        }

        return $body . self::copy_script_once() . self::support_style_once();
    }

    public static function append_support_to_content($content) {
        if (is_admin() || !is_singular() || !in_the_loop() || !is_main_query()) { return $content; }
        if (has_shortcode($content, 'handaiman_support') || has_shortcode($content, 'ha_support') || has_shortcode($content, 'handaiman_crypto')) { return $content; }

        $post_type = get_post_type();
        $opts = self::options();
        $auto_atts = !empty($opts['global']['auto_append_collapsed']) ? array('collapsed' => 'yes') : array();
        if ($post_type === 'post' && !empty($opts['global']['auto_append_posts'])) {
            return $content . "\n\n" . self::support_shortcode($auto_atts, null, 'handaiman_support');
        }
        if ($post_type === 'podcast' && !empty($opts['global']['auto_append_podcasts'])) {
            return $content . "\n\n" . self::support_shortcode($auto_atts, null, 'handaiman_support');
        }
        return $content;
    }

    public static function alt_crypto_shortcode($atts = array(), $content = null, $tag = '') {
        $opts = self::options();
        $atts = shortcode_atts(array(
            'networks' => '',
            'open' => '',
        ), $atts, $tag);
        $out = self::render_alt_crypto_networks($opts, $atts['networks'], $atts['open']);
        if ($out === '') { return ''; }
        return $out . self::copy_script_once() . self::support_style_once();
    }

    private static function render_alt_crypto_networks($opts, $networks_attr = '', $open_attr = '') {
        if (empty($opts['global']['alt_crypto_enabled']) || empty($opts['alt_networks']) || !is_array($opts['alt_networks'])) { return ''; }

        $requested = null;
        if (trim($networks_attr) !== '') {
            $requested = array();
            foreach (explode(',', strtolower($networks_attr)) as $network_key) {
                $network_key = trim($network_key);
                if ($network_key !== '') { $requested[$network_key] = true; }
            }
        }

        $networks = array();
        foreach ($opts['alt_networks'] as $key => $n) {
            if ($requested !== null && !isset($requested[$key])) { continue; }
            if (empty($n['enabled']) || empty($n['address'])) { continue; }
            $n['key'] = $key;
            $networks[] = $n;
        }
        if (!$networks) { return ''; }

        usort($networks, function($a, $b) {
            $ao = isset($a['order']) ? intval($a['order']) : 0;
            $bo = isset($b['order']) ? intval($b['order']) : 0;
            if ($ao === $bo) { return strcasecmp($a['label'], $b['label']); }
            return $ao <=> $bo;
        });

        $open = self::is_yesish($open_attr) || ($open_attr === '' && !empty($opts['global']['alt_crypto_open']));
        $summary = !empty($opts['global']['alt_crypto_summary']) ? $opts['global']['alt_crypto_summary'] : 'We also accept other cryptocurrency networks';
        $intro = !empty($opts['global']['alt_crypto_intro']) ? $opts['global']['alt_crypto_intro'] : '';

        ob_start();
        ?>
        <div class="ha-alt-crypto" style="border:1px solid #dcdcde; padding:14px 16px; border-radius:8px; max-width:760px; margin:1em 0; background:#fff;">
            <details <?php echo $open ? 'open' : ''; ?>>
                <summary style="cursor:pointer; font-weight:600;"><?php echo esc_html($summary); ?></summary>
                <?php if ($intro !== ''): ?>
                    <p style="font-size:0.94em; opacity:0.88;"><?php echo esc_html($intro); ?></p>
                <?php endif; ?>
                <div class="ha-alt-crypto-list" style="display:grid; gap:12px; margin-top:12px;">
                    <?php foreach ($networks as $n):
                        $uid = 'ha-alt-crypto-' . sanitize_html_class($n['key']) . '-' . wp_generate_password(8, false, false);
                        $memo_uid = $uid . '-memo';
                    ?>
                        <div class="ha-alt-crypto-network ha-alt-crypto-<?php echo esc_attr($n['key']); ?>" style="border:1px solid #eee; border-radius:8px; padding:12px;">
                            <h4 style="margin:0 0 0.35em 0;"><?php echo esc_html($n['label']); ?><?php if (!empty($n['ticker'])): ?> <span style="font-size:0.85em; opacity:0.72;">(<?php echo esc_html($n['ticker']); ?>)</span><?php endif; ?></h4>
                            <?php if (!empty($n['network'])): ?><p style="margin:0.25em 0;"><strong>Network:</strong> <?php echo esc_html($n['network']); ?></p><?php endif; ?>
                            <?php if (!empty($n['accepts'])): ?><p style="margin:0.25em 0;"><strong>Accepts:</strong> <?php echo esc_html($n['accepts']); ?></p><?php endif; ?>
                            <?php if (!empty($n['note'])): ?><p style="margin:0.35em 0; font-size:0.92em; opacity:0.82;"><?php echo esc_html($n['note']); ?></p><?php endif; ?>
                            <p style="margin:0.7em 0 0.35em 0;"><input id="<?php echo esc_attr($uid); ?>" type="text" readonly value="<?php echo esc_attr($n['address']); ?>" style="width:100%; font-family:monospace; font-size:15px; padding:8px;"></p>
                            <p style="margin:0.35em 0;"><button type="button" class="button ha-crypto-copy" data-target="<?php echo esc_attr($uid); ?>"><?php echo esc_html($opts['global']['button_text']); ?></button></p>
                            <?php if (!empty($n['memo'])): ?>
                                <p style="margin:0.7em 0 0.25em 0;"><strong>Memo / destination tag:</strong></p>
                                <p style="margin:0.25em 0;"><input id="<?php echo esc_attr($memo_uid); ?>" type="text" readonly value="<?php echo esc_attr($n['memo']); ?>" style="width:100%; font-family:monospace; font-size:15px; padding:8px;"></p>
                                <p style="margin:0.35em 0;"><button type="button" class="button ha-crypto-copy" data-target="<?php echo esc_attr($memo_uid); ?>">Copy memo/tag</button></p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </details>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function render_support_links($opts, $methods_attr = '') {
        if (empty($opts['methods']) || !is_array($opts['methods'])) { return ''; }

        $requested = null;
        if (trim($methods_attr) !== '') {
            $requested = array();
            foreach (explode(',', strtolower($methods_attr)) as $method) {
                $method = trim($method);
                if ($method !== '') { $requested[$method] = true; }
            }
        }

        $methods = array();
        foreach ($opts['methods'] as $key => $m) {
            if ($requested !== null && !isset($requested[$key])) { continue; }
            if (empty($m['enabled']) || empty($m['url'])) { continue; }
            $m['key'] = $key;
            $methods[] = $m;
        }
        if (!$methods) { return ''; }

        usort($methods, function($a, $b) {
            $ao = isset($a['order']) ? intval($a['order']) : 0;
            $bo = isset($b['order']) ? intval($b['order']) : 0;
            if ($ao === $bo) { return strcasecmp($a['label'], $b['label']); }
            return $ao <=> $bo;
        });

        ob_start();
        ?>
        <div class="ha-support-my-work" style="border:1px solid #dcdcde; padding:18px; border-radius:10px; max-width:760px; margin:1.5em 0; background:#fff;">
            <?php if (!empty($opts['global']['support_heading'])): ?>
                <h3 style="margin-top:0;"><?php echo esc_html($opts['global']['support_heading']); ?></h3>
            <?php endif; ?>
            <?php if (!empty($opts['global']['support_intro'])): ?>
                <p><?php echo esc_html($opts['global']['support_intro']); ?></p>
            <?php endif; ?>
            <div class="ha-support-methods" style="display:flex; flex-wrap:wrap; gap:10px; margin:1em 0;">
                <?php foreach ($methods as $m): ?>
                    <a class="ha-support-button ha-support-<?php echo esc_attr($m['key']); ?>" href="<?php echo esc_url($m['url']); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block; border:1px solid #111; border-radius:999px; padding:0.55em 0.95em; text-decoration:none; line-height:1.2; background:#111; color:#fff;">
                        <?php echo esc_html($m['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php
            $descriptions = array_filter($methods, function($m) { return !empty($m['description']); });
            if (!empty($descriptions)):
            ?>
                <ul class="ha-support-method-descriptions" style="margin:0.75em 0 0 1.2em; font-size:0.92em; opacity:0.85;">
                    <?php foreach ($descriptions as $m): ?>
                        <li><strong><?php echo esc_html($m['label']); ?>:</strong> <?php echo esc_html($m['description']); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if (!empty($opts['global']['support_note'])): ?>
                <p style="font-size:0.92em; opacity:0.85;"><?php echo esc_html($opts['global']['support_note']); ?></p>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function support_style_once() {
        static $done = false;
        if ($done) { return ''; }
        $done = true;
        return '<style>.ha-support-my-work-wrap .ha-crypto-contributions{margin-top:1.5em}.ha-support-my-work-wrap input[readonly]{box-sizing:border-box}.ha-support-button:hover{filter:brightness(1.15);text-decoration:none!important;}.ha-alt-crypto summary::-webkit-details-marker{display:inline-block}.ha-alt-crypto input[readonly]{box-sizing:border-box;}.ha-support-collapsible{border:1px solid #dcdcde;padding:16px;border-radius:8px;max-width:760px;margin:1em 0;background:#fff;box-sizing:border-box;}.ha-support-collapsible>summary{cursor:pointer;font-weight:600;font-size:1.05em;}.ha-support-collapsible-inner{margin-top:14px;}.ha-support-collapsible .ha-support-my-work-wrap{padding:0;}.ha-support-collapsible .ha-support-my-work,.ha-support-collapsible .ha-crypto-donate,.ha-support-collapsible .ha-alt-crypto{max-width:none;margin:1em 0;}.ha-support-collapsible .ha-support-my-work{border:0!important;padding:0!important;border-radius:0!important;margin-top:0!important;background:transparent!important;}</style>';
    }

    public static function shortcode($atts = array(), $content = null, $tag = '') {
        self::create_table();
        $opts = self::options();

        if (!extension_loaded('gmp')) {
            return current_user_can('manage_options') ? '<div class="ha-crypto-donate ha-crypto-error">HandAIMan Crypto plugin: PHP GMP extension is required.</div>' : '';
        }

        $atts = shortcode_atts(array('currencies' => ''), $atts, $tag);
        $coins = self::coins_for_shortcode($tag, $atts['currencies']);
        $rendered = array();
        $admin_errors = array();

        foreach ($coins as $coin) {
            if (empty($opts['coins'][$coin]) || empty($opts['coins'][$coin]['enabled']) && $tag === 'handaiman_crypto') { continue; }
            if (empty($opts['coins'][$coin]['xpub'])) {
                if (current_user_can('manage_options')) { $admin_errors[] = strtoupper($coin) . ' is enabled/requested but has no extended public key configured.'; }
                continue;
            }
            try {
                $assignment = self::get_or_create_assignment($coin, $opts);
                $rendered[] = self::render_coin_box($coin, $opts, $assignment);
            } catch (Exception $e) {
                if (current_user_can('manage_options')) { $admin_errors[] = strtoupper($coin) . ': ' . $e->getMessage(); }
            }
        }

        if (empty($rendered) && !current_user_can('manage_options')) { return ''; }

        $out = '<div class="ha-crypto-contributions">' . implode("\n", $rendered) . '</div>';
        if (!empty($admin_errors)) {
            $out .= '<div class="ha-crypto-donate ha-crypto-error" style="border:1px solid #d63638; padding:12px; margin:1em 0;"><strong>HandAIMan Crypto plugin admin notice:</strong><ul style="margin-left:1.5em; list-style:disc;">';
            foreach ($admin_errors as $err) { $out .= '<li>' . esc_html($err) . '</li>'; }
            $out .= '</ul></div>';
        }
        $out .= self::copy_script_once();
        return $out;
    }

    private static function coins_for_shortcode($tag, $currency_attr) {
        $defs = self::coin_defs();
        if ($tag === 'handaiman_btc' || $tag === 'ha_btc_donate') { return array('btc'); }
        if ($tag === 'handaiman_bch') { return array('bch'); }
        if ($tag === 'handaiman_ltc') { return array('ltc'); }
        if (trim($currency_attr) !== '') {
            $requested = array();
            foreach (explode(',', strtolower($currency_attr)) as $coin) {
                $coin = trim($coin);
                if (isset($defs[$coin])) { $requested[] = $coin; }
            }
            return array_values(array_unique($requested));
        }
        return array_keys($defs);
    }

    private static function render_coin_box($coin, $opts, $assignment) {
        $defs = self::coin_defs();
        $def = $defs[$coin];
        $c = $opts['coins'][$coin];
        $address = $assignment['address'];
        $uri = $assignment['payment_uri'];
        $uid = 'ha-crypto-' . esc_attr($coin) . '-' . wp_generate_password(8, false, false);
        $allowed_protocols = array('bitcoin', 'bitcoincash', 'litecoin');
        ob_start();
        ?>
        <div class="ha-crypto-donate ha-crypto-<?php echo esc_attr($coin); ?>" style="border:1px solid #dcdcde; padding:16px; border-radius:8px; max-width:760px; margin:1em 0; background:#fff;">
            <h3 style="margin-top:0;"><?php echo esc_html($c['title']); ?></h3>
            <p><?php echo esc_html($c['intro']); ?></p>
            <p><input id="<?php echo esc_attr($uid); ?>" type="text" readonly value="<?php echo esc_attr($address); ?>" style="width:100%; font-family:monospace; font-size:16px; padding:8px;"></p>
            <p>
                <button type="button" class="button ha-crypto-copy" data-target="<?php echo esc_attr($uid); ?>"><?php echo esc_html($opts['global']['button_text']); ?></button>
                <?php if (!empty($opts['global']['show_wallet_link'])): ?>
                    <a class="button" href="<?php echo esc_url($uri, $allowed_protocols); ?>">Open wallet</a>
                <?php endif; ?>
            </p>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function copy_script_once() {
        static $done = false;
        if ($done) { return ''; }
        $done = true;
        ob_start();
        ?>
        <script>
        (function(){
            document.addEventListener('click', function(e){
                if (!e.target.classList.contains('ha-crypto-copy')) return;
                var input = document.getElementById(e.target.getAttribute('data-target'));
                if (!input) return;
                input.select(); input.setSelectionRange(0, 99999);
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(input.value);
                } else {
                    document.execCommand('copy');
                }
                var old = e.target.textContent;
                e.target.textContent = 'Copied';
                setTimeout(function(){ e.target.textContent = old; }, 1500);
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    private static function get_or_create_assignment($coin, $opts) {
        global $wpdb;
        $table = self::table_name();
        self::maybe_set_visitor_cookie();

        $token = isset($_COOKIE[self::COOKIE_KEY]) ? sanitize_text_field(wp_unslash($_COOKIE[self::COOKIE_KEY])) : '';
        if (!preg_match('/^[A-Za-z0-9]{32,80}$/', $token)) {
            $token = wp_generate_password(48, false, false);
            $_COOKIE[self::COOKIE_KEY] = $token;
        }

        $token_hash = hash('sha256', $token . wp_salt('auth'));
        $existing = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE currency=%s AND token_hash=%s AND status='reserved' ORDER BY id DESC LIMIT 1", $coin, $token_hash), ARRAY_A);
        if ($existing) {
            if (empty($existing['payment_uri'])) {
                $existing['payment_uri'] = self::payment_uri($coin, $existing['address'], $opts['global']['label']);
            }
            return $existing;
        }

        $coin_opts = $opts['coins'][$coin];
        $idx = intval($coin_opts['next_index']);
        for ($tries = 0; $tries < 10; $tries++) {
            $derived = self::derive_receive_address($coin, $coin_opts['xpub'], $idx);
            $uri = self::payment_uri($coin, $derived['address'], $opts['global']['label']);
            $now = current_time('mysql');
            $until = gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS * intval($opts['global']['reserve_days']));
            $visitor_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . wp_salt('nonce'));
            $ok = $wpdb->insert($table, array(
                'currency' => $coin,
                'token_hash' => $token_hash,
                'address_index' => $idx,
                'address' => $derived['address'],
                'payment_uri' => $uri,
                'visitor_hash' => $visitor_hash,
                'created_at' => $now,
                'reserved_until' => $until,
                'status' => 'reserved',
            ), array('%s','%s','%d','%s','%s','%s','%s','%s','%s'));
            if ($ok) {
                $opts['coins'][$coin]['next_index'] = $idx + 1;
                update_option(self::OPTION_KEY, $opts);
                return array(
                    'currency' => $coin,
                    'address_index' => $idx,
                    'address' => $derived['address'],
                    'payment_uri' => $uri,
                );
            }
            $idx++;
        }
        throw new Exception('Could not reserve a fresh address after several attempts.');
    }

    private static function payment_uri($coin, $address, $label) {
        $defs = self::coin_defs();
        if (empty($defs[$coin])) { return $address; }
        if ($coin === 'bch') {
            $uri = $address; // address already includes bitcoincash: CashAddr prefix.
        } else {
            $uri = $defs[$coin]['scheme'] . ':' . $address;
        }
        if ($label !== '') {
            $uri .= (strpos($uri, '?') === false ? '?' : '&') . 'label=' . rawurlencode($label);
        }
        return $uri;
    }

    // ----- BIP32 / address code -----

    public static function derive_receive_address($coin, $extended_key, $address_index) {
        $key = self::parse_extended_public_key($coin, $extended_key);
        // Account-level extended public key -> external chain /0 -> address index /n.
        $chain0 = self::ckd_pub($key, 0);
        $child = self::ckd_pub($chain0, intval($address_index));
        $pubkey = $child['pubkey'];
        $script_type = $key['script_type'];
        $address = self::address_from_pubkey($coin, $pubkey, $script_type);
        return array('address' => $address, 'script_type' => $script_type);
    }

    private static function parse_extended_public_key($coin, $xpub) {
        $defs = self::coin_defs();
        if (!isset($defs[$coin])) { throw new Exception('Unsupported currency.'); }
        $def = $defs[$coin];

        $payload = self::base58check_decode(trim($xpub));
        if (strlen($payload) !== 78) { throw new Exception('Invalid extended key length.'); }
        $version = bin2hex(substr($payload, 0, 4));
        if (in_array($version, $def['private_versions'], true)) {
            throw new Exception('Refusing private extended key. Paste only an extended public key.');
        }
        if (!isset($def['versions'][$version])) {
            throw new Exception('Unsupported extended public key version for ' . $def['ticker'] . '.');
        }
        $depth = ord($payload[4]);
        $chaincode = substr($payload, 13, 32);
        $pubkey = substr($payload, 45, 33);
        if (strlen($chaincode) !== 32 || strlen($pubkey) !== 33) { throw new Exception('Malformed extended public key.'); }
        if ($pubkey[0] !== "\x02" && $pubkey[0] !== "\x03") { throw new Exception('Expected compressed public key.'); }
        return array(
            'depth' => $depth,
            'chaincode' => $chaincode,
            'pubkey' => $pubkey,
            'script_type' => $def['versions'][$version],
        );
    }

    private static function address_from_pubkey($coin, $pubkey, $script_type) {
        $defs = self::coin_defs();
        $def = $defs[$coin];
        if (!in_array($script_type, $def['address_modes'], true)) {
            throw new Exception('Unsupported script type for ' . $def['ticker'] . '.');
        }
        if ($coin === 'bch') {
            if ($script_type !== 'p2pkh') { throw new Exception('Bitcoin Cash adapter supports xpub/P2PKH only.'); }
            return self::cashaddr_encode($def['cashaddr_prefix'], 'p2pkh', self::hash160($pubkey));
        }
        if ($script_type === 'p2pkh') {
            return self::base58check_encode(chr($def['p2pkh_version']) . self::hash160($pubkey));
        }
        if ($script_type === 'p2sh_p2wpkh') {
            $redeem = "\x00\x14" . self::hash160($pubkey);
            return self::base58check_encode(chr($def['p2sh_version']) . self::hash160($redeem));
        }
        if ($script_type === 'p2wpkh') {
            return self::bech32_encode($def['bech32_hrp'], 0, self::hash160($pubkey));
        }
        throw new Exception('Unsupported script type.');
    }

    private static function ckd_pub($key, $index) {
        if ($index < 0 || $index >= 0x80000000) { throw new Exception('Only non-hardened public derivation is supported.'); }
        $data = $key['pubkey'] . pack('N', $index);
        $I = hash_hmac('sha512', $data, $key['chaincode'], true);
        $IL = substr($I, 0, 32);
        $IR = substr($I, 32, 32);
        $il_int = self::gmp_from_bin($IL);
        $n = self::ec_n();
        if (gmp_cmp($il_int, 0) <= 0 || gmp_cmp($il_int, $n) >= 0) { throw new Exception('Invalid child derivation; try next index.'); }
        $parent_point = self::point_from_compressed($key['pubkey']);
        $il_point = self::point_mul($il_int, self::ec_g());
        $child_point = self::point_add($il_point, $parent_point);
        if ($child_point === null) { throw new Exception('Invalid derived child point.'); }
        return array(
            'chaincode' => $IR,
            'pubkey' => self::point_to_compressed($child_point),
            'script_type' => $key['script_type'],
        );
    }

    private static function hash160($bin) {
        return hash('ripemd160', hash('sha256', $bin, true), true);
    }

    private static function base58check_decode($s) {
        $bin = self::base58_decode($s);
        if (strlen($bin) < 5) { throw new Exception('Invalid Base58Check payload.'); }
        $payload = substr($bin, 0, -4);
        $checksum = substr($bin, -4);
        $expected = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        if (!hash_equals($expected, $checksum)) { throw new Exception('Invalid Base58Check checksum.'); }
        return $payload;
    }

    private static function base58check_encode($payload) {
        $checksum = substr(hash('sha256', hash('sha256', $payload, true), true), 0, 4);
        return self::base58_encode($payload . $checksum);
    }

    private static function base58_decode($s) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num = gmp_init(0);
        for ($i = 0; $i < strlen($s); $i++) {
            $p = strpos($alphabet, $s[$i]);
            if ($p === false) { throw new Exception('Invalid Base58 character.'); }
            $num = gmp_add(gmp_mul($num, 58), $p);
        }
        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2) { $hex = '0' . $hex; }
        $bin = $hex === '00' ? '' : hex2bin($hex);
        $leading = 0;
        while ($leading < strlen($s) && $s[$leading] === '1') { $leading++; }
        return str_repeat("\x00", $leading) . $bin;
    }

    private static function base58_encode($bin) {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $num = self::gmp_from_bin($bin);
        $out = '';
        while (gmp_cmp($num, 0) > 0) {
            $qr = gmp_div_qr($num, 58);
            $num = $qr[0];
            $rem = intval(gmp_strval($qr[1]));
            $out = $alphabet[$rem] . $out;
        }
        for ($i = 0; $i < strlen($bin) && $bin[$i] === "\x00"; $i++) { $out = '1' . $out; }
        return $out === '' ? '1' : $out;
    }

    private static function gmp_from_bin($bin) {
        $hex = bin2hex($bin);
        if ($hex === '') { return gmp_init(0); }
        return gmp_init($hex, 16);
    }

    private static function gmp_to_bin($num, $pad = 32) {
        $hex = gmp_strval($num, 16);
        if (strlen($hex) % 2) { $hex = '0' . $hex; }
        $bin = hex2bin($hex);
        if ($pad > 0) { $bin = str_pad($bin, $pad, "\x00", STR_PAD_LEFT); }
        return $bin;
    }

    private static function ec_p() { static $p; if (!$p) $p = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEFFFFFC2F', 16); return $p; }
    private static function ec_n() { static $n; if (!$n) $n = gmp_init('FFFFFFFFFFFFFFFFFFFFFFFFFFFFFFFEBAAEDCE6AF48A03BBFD25E8CD0364141', 16); return $n; }
    private static function ec_g() {
        return array(
            'x' => gmp_init('79BE667EF9DCBBAC55A06295CE870B07029BFCDB2DCE28D959F2815B16F81798', 16),
            'y' => gmp_init('483ADA7726A3C4655DA4FBFC0E1108A8FD17B448A68554199C47D08FFB10D4B8', 16),
        );
    }

    private static function mod($x) {
        $p = self::ec_p();
        $r = gmp_mod($x, $p);
        if (gmp_cmp($r, 0) < 0) { $r = gmp_add($r, $p); }
        return $r;
    }

    private static function point_from_compressed($pubkey) {
        $prefix = ord($pubkey[0]);
        $x = self::gmp_from_bin(substr($pubkey, 1, 32));
        $p = self::ec_p();
        $alpha = self::mod(gmp_add(gmp_powm($x, 3, $p), 7));
        $beta = gmp_powm($alpha, gmp_div_q(gmp_add($p, 1), 4), $p);
        $is_odd = gmp_testbit($beta, 0);
        if (($prefix === 0x03 && !$is_odd) || ($prefix === 0x02 && $is_odd)) {
            $beta = gmp_sub($p, $beta);
        }
        return array('x' => $x, 'y' => $beta);
    }

    private static function point_to_compressed($P) {
        $prefix = gmp_testbit($P['y'], 0) ? "\x03" : "\x02";
        return $prefix . self::gmp_to_bin($P['x'], 32);
    }

    private static function point_add($P, $Q) {
        if ($P === null) return $Q;
        if ($Q === null) return $P;
        $p = self::ec_p();
        if (gmp_cmp($P['x'], $Q['x']) == 0) {
            if (gmp_cmp(self::mod(gmp_add($P['y'], $Q['y'])), 0) == 0) { return null; }
            return self::point_double($P);
        }
        $lambda = self::mod(gmp_mul(gmp_sub($Q['y'], $P['y']), gmp_invert(self::mod(gmp_sub($Q['x'], $P['x'])), $p)));
        $x = self::mod(gmp_sub(gmp_sub(gmp_powm($lambda, 2, $p), $P['x']), $Q['x']));
        $y = self::mod(gmp_sub(gmp_mul($lambda, gmp_sub($P['x'], $x)), $P['y']));
        return array('x' => $x, 'y' => $y);
    }

    private static function point_double($P) {
        if ($P === null) return null;
        $p = self::ec_p();
        if (gmp_cmp($P['y'], 0) == 0) return null;
        $lambda = self::mod(gmp_mul(gmp_mul(3, gmp_powm($P['x'], 2, $p)), gmp_invert(self::mod(gmp_mul(2, $P['y'])), $p)));
        $x = self::mod(gmp_sub(gmp_powm($lambda, 2, $p), gmp_mul(2, $P['x'])));
        $y = self::mod(gmp_sub(gmp_mul($lambda, gmp_sub($P['x'], $x)), $P['y']));
        return array('x' => $x, 'y' => $y);
    }

    private static function point_mul($k, $P) {
        $N = null;
        $Q = $P;
        while (gmp_cmp($k, 0) > 0) {
            if (gmp_testbit($k, 0)) { $N = self::point_add($N, $Q); }
            $Q = self::point_double($Q);
            $k = gmp_div_q($k, 2);
        }
        return $N;
    }

    private static function bech32_polymod($values) {
        $chk = 1;
        $gen = array(0x3b6a57b2, 0x26508e6d, 0x1ea119fa, 0x3d4233dd, 0x2a1462b3);
        foreach ($values as $v) {
            $top = $chk >> 25;
            $chk = (($chk & 0x1ffffff) << 5) ^ $v;
            for ($i = 0; $i < 5; $i++) {
                if (($top >> $i) & 1) { $chk ^= $gen[$i]; }
            }
        }
        return $chk;
    }

    private static function bech32_hrp_expand($hrp) {
        $ret = array();
        for ($i = 0; $i < strlen($hrp); $i++) { $ret[] = ord($hrp[$i]) >> 5; }
        $ret[] = 0;
        for ($i = 0; $i < strlen($hrp); $i++) { $ret[] = ord($hrp[$i]) & 31; }
        return $ret;
    }

    private static function convert_bits($data, $from, $to, $pad = true) {
        $acc = 0; $bits = 0; $ret = array(); $maxv = (1 << $to) - 1; $max_acc = (1 << ($from + $to - 1)) - 1;
        foreach ($data as $value) {
            if ($value < 0 || ($value >> $from)) { throw new Exception('Invalid data for bit conversion.'); }
            $acc = (($acc << $from) | $value) & $max_acc;
            $bits += $from;
            while ($bits >= $to) {
                $bits -= $to;
                $ret[] = ($acc >> $bits) & $maxv;
            }
        }
        if ($pad) {
            if ($bits) { $ret[] = ($acc << ($to - $bits)) & $maxv; }
        } elseif ($bits >= $from || (($acc << ($to - $bits)) & $maxv)) {
            throw new Exception('Invalid padding in bit conversion.');
        }
        return $ret;
    }

    private static function bech32_create_checksum($hrp, $data) {
        $values = array_merge(self::bech32_hrp_expand($hrp), $data);
        $polymod = self::bech32_polymod(array_merge($values, array(0,0,0,0,0,0))) ^ 1;
        $ret = array();
        for ($p = 0; $p < 6; $p++) { $ret[] = ($polymod >> (5 * (5 - $p))) & 31; }
        return $ret;
    }

    private static function bech32_encode($hrp, $witver, $program) {
        $charset = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
        $bytes = array_values(unpack('C*', $program));
        $data = array_merge(array($witver), self::convert_bits($bytes, 8, 5, true));
        $combined = array_merge($data, self::bech32_create_checksum($hrp, $data));
        $out = $hrp . '1';
        foreach ($combined as $d) { $out .= $charset[$d]; }
        return $out;
    }

    // ----- Bitcoin Cash CashAddr code -----

    private static function cashaddr_encode($prefix, $type, $hash) {
        $prefix = strtolower($prefix);
        $charset = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
        $sizes = array(20 => 0, 24 => 1, 28 => 2, 32 => 3, 40 => 4, 48 => 5, 56 => 6, 64 => 7);
        $types = array('p2pkh' => 0, 'p2sh' => 1);
        $hash_len = strlen($hash);
        if (!isset($sizes[$hash_len])) { throw new Exception('Unsupported CashAddr hash length.'); }
        if (!isset($types[$type])) { throw new Exception('Unsupported CashAddr type.'); }
        $version = ($types[$type] << 3) | $sizes[$hash_len];
        $payload = array_merge(array($version), array_values(unpack('C*', $hash)));
        $data = self::convert_bits($payload, 8, 5, true);
        $checksum = self::cashaddr_create_checksum($prefix, $data);
        $combined = array_merge($data, $checksum);
        $out = $prefix . ':';
        foreach ($combined as $d) { $out .= $charset[$d]; }
        return $out;
    }

    private static function cashaddr_prefix_expand($prefix) {
        $ret = array();
        for ($i = 0; $i < strlen($prefix); $i++) { $ret[] = ord($prefix[$i]) & 0x1f; }
        $ret[] = 0;
        return $ret;
    }

    private static function cashaddr_polymod($values) {
        $c = 1;
        $gen = array(0x98f2bc8e61, 0x79b76d99e2, 0xf33e5fb3c4, 0xae2eabe2a8, 0x1e4f43e470);
        foreach ($values as $d) {
            $c0 = $c >> 35;
            $c = (($c & 0x07ffffffff) << 5) ^ $d;
            for ($i = 0; $i < 5; $i++) {
                if (($c0 >> $i) & 1) { $c ^= $gen[$i]; }
            }
        }
        return $c;
    }

    private static function cashaddr_create_checksum($prefix, $data) {
        $values = array_merge(self::cashaddr_prefix_expand($prefix), $data, array(0,0,0,0,0,0,0,0));
        $polymod = self::cashaddr_polymod($values) ^ 1;
        $ret = array();
        for ($i = 0; $i < 8; $i++) { $ret[] = ($polymod >> (5 * (7 - $i))) & 31; }
        return $ret;
    }
}

HandAIMan_Crypto_Contributions::init();
