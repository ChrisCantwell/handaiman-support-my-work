# HandAIMan Support My Work

AI-assisted WordPress support and crypto contribution plugin for TheHandAIMan.

Built by **TheHandAIMan with ChatGPT** as part of the broader HandAIMan / HandAIStack WordPress tooling project.

## Current stable version

`0.5.2`

## Features

- Configurable support links and buttons
- GiveSendGo, Cash App, PayPal, Venmo, Amazon Wish List, and custom methods
- BTC/BCH/LTC fresh-address display from public extended keys
- Static alternative crypto network address rows
- Collapsed support panel mode
- Optional auto-append to posts and podcast episodes
- HandAIStack admin menu integration when HandAIStack Core is active

## Shortcodes

- `[handaiman_support]`
- `[ha_support]`
- `[handaiman_crypto]`
- `[handaiman_btc]`
- `[ha_btc_donate]`
- `[handaiman_bch]`
- `[handaiman_ltc]`

## Installation

1. Download the plugin ZIP from a release, or package this repository as a WordPress plugin folder.
2. Upload it in WordPress under **Plugins → Add New → Upload Plugin**.
3. Activate the plugin.
4. Configure support methods and crypto settings from the WordPress admin menu.

When **HandAIStack Core** is active, this plugin's settings appear under the HandAIStack admin menu. Without Core, the plugin keeps standalone fallback admin behavior.

## Security note

This plugin is designed to store public receiving information such as support URLs, public extended keys, and static receiving addresses. Do not store private keys, seed phrases, passwords, API secrets, or wallet recovery material in WordPress settings.

## AI Attribution

This plugin was created through a human-directed, AI-assisted workflow. TheHandAIMan defined the requirements, tested the code on a live WordPress site, made product/design decisions, and approved releases. Primary code generation for this baseline was done by ChatGPT.

See [AI_ATTRIBUTION.md](AI_ATTRIBUTION.md) for the full attribution statement.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
