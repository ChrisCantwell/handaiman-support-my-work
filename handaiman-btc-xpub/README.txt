=== HandAIMan Support My Work ===
Contributors: handaiman
Tags: donations, support, bitcoin, bitcoin-cash, litecoin, xpub, crypto
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 0.5.1
License: GPLv2 or later

Display configurable support links, fresh public-key-derived BTC/BCH/LTC contribution addresses, and optional static alternative cryptocurrency network addresses.

== Description ==

HandAIMan Support My Work renders a reusable support box for posts, pages, and podcast episodes.

Supported ordinary support links:

* GiveSendGo
* Cash App
* PayPal
* Venmo
* Amazon Wish List
* Two custom support links

Supported cryptocurrency address derivation:

* Bitcoin (BTC)
* Bitcoin Cash (BCH, CashAddr)
* Litecoin (LTC)

Crypto addresses are derived from account-level extended public keys and assigned per visitor. The plugin does not store seed phrases, private keys, xprv/yprv/zprv, or spending keys.

Optional static alternative crypto networks:

* Ethereum
* Base
* Solana
* Polygon
* Arbitrum One
* Optimism
* BNB Smart Chain
* Tron
* Avalanche C-Chain
* Cardano
* XRP Ledger
* Monero
* Dogecoin
* Dash
* Five custom static network rows

Static network entries are collapsed by default on the front end and should be used for assets where the same receive address accepts the network's native coin and supported tokens on that same network.

== Shortcodes ==

Main support box:

[handaiman_support]

Collapsed support box:

[handaiman_support collapsed="yes"]

Open collapsed support by default:

[handaiman_support collapsed="yes" open="yes"]

Alias:

[ha_support]

Crypto-only box for BTC/BCH/LTC derived addresses:

[handaiman_crypto]

Static alternative crypto networks only:

[handaiman_alt_crypto]

Legacy BTC shortcodes are preserved:

[handaiman_btc]
[ha_btc_donate]

Single currency shortcodes:

[handaiman_bch]
[handaiman_ltc]

Examples:

[handaiman_support collapsed="yes"]
[handaiman_support collapsed="yes" summary="Support the Project"]
[handaiman_support crypto="no"]
[handaiman_support links="no"]
[handaiman_support currencies="btc,ltc"]
[handaiman_support methods="paypal,venmo,cashapp"]
[handaiman_support networks="eth,solana,base"]
[handaiman_support alt_crypto="no"]
[handaiman_alt_crypto open="yes"]

== Setup ==

1. Upload and activate the plugin.
2. Go to Settings -> HandAIMan Support.
3. Enable and edit support methods such as PayPal, Venmo, Cash App, GiveSendGo, and Amazon Wish List.
4. Add account-level extended public keys for BTC, BCH, and/or LTC if desired.
5. Verify the test addresses shown in admin against your wallet before publishing crypto support.
6. Add static receive addresses for any other crypto networks you want to accept. Verify every address in your wallet before enabling it.
7. Add [handaiman_support] to pages/posts, or enable auto-append for posts and/or podcast episodes. Auto-appended support can be rendered collapsed by default.

== Security Notes ==

Extended public keys cannot spend funds, but they can reveal wallet receive-address history and future receive addresses. Use separate wallet/account-level extended public keys for each currency.

Static alternative crypto network addresses are public receive addresses. Label the network clearly. A token sent on the wrong network may be unrecoverable.

If an attacker gets WordPress admin access, they can change support links or contribution addresses. Protect WordPress admin accordingly.

== Changelog ==

= 0.5.0 =
* Added collapsed support mode with [handaiman_support collapsed="yes"].
* Added configurable collapsed summary label, defaulting to "Support the Project".
* Added setting to render auto-appended support collapsed by default.
* Kept manual [handaiman_support] output expanded by default for full Support pages.

= 0.4.0 =
* Added collapsed static alternative crypto network section.
* Added configurable network/address/memo/note rows for Ethereum, Base, Solana, Polygon, Arbitrum, Optimism, BNB Smart Chain, Tron, Avalanche C-Chain, Cardano, XRP Ledger, Monero, Dogecoin, Dash, and five custom rows.
* Added [handaiman_alt_crypto] shortcode.
* Added shortcode controls for alt_crypto, alt_crypto_open, and networks.

= 0.3.0 =
* Expanded plugin into HandAIMan Support My Work.
* Added configurable support methods for GiveSendGo, Cash App, PayPal, Venmo, Amazon Wish List, and custom links.
* Added [handaiman_support] and [ha_support] shortcodes.
* Added optional auto-append to posts and podcast episodes.
* Preserved crypto support and legacy crypto shortcodes.

= 0.2.0 =
* Added BTC/BCH/LTC multi-currency crypto support.
* Removed public display of fresh address index.
