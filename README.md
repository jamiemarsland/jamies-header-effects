# Jamie's Header Effects

Beautiful sticky header effects for WordPress block themes.

- **Chameleon** — the header samples the colour of the section beneath it as you scroll and recolours itself to match, flipping its text between light and dark for contrast.
- **Frosted blur** — translucent, blurred header (configurable colour, opacity, blur).
- **Solid colour** — classic sticky header with soft shadow.
- **Hide on scroll down / reveal on scroll up** — with sensitivity and delay controls.
- **Transparent overlay** — float the header over the first section for full-bleed heroes.

Plus admin-bar offset, z-index control, push-content-down, mobile breakpoint, disable by post type or template, and `prefers-reduced-motion` support. No jQuery on the front end.

Settings live under **Appearance → Header Effects**.

## Structure

```
jamies-header-effects.php   bootstrap, front-end enqueue, config
includes/settings.php       Settings API: defaults, sanitisation, migration
includes/admin.php          Appearance → Header Effects screen
includes/helpers.php        header selector, template detection
assets/js/frontend.js       sticky / hide / chameleon behaviour (vanilla JS)
assets/css/frontend.css     front-end styles (bsh-* classes)
assets/js/admin.js          colour picker + opacity slider
assets/css/admin.css
.wordpress-org/             listing assets + Playground blueprint (not shipped)
```

There is no build step: the plugin ships plain PHP, JS and CSS.

## Live demo

The WordPress.org "Live Preview" button runs `.wordpress-org/blueprints/blueprint.json`, which:

1. activates the plugin with **Chameleon** + **Overlay** on,
2. creates a five-section "Aurelia" landing page (navy hero, cream, forest green, terracotta, black) and sets it as the front page,
3. lands on the front page so people can scroll and watch the header change colour.

## Releasing

Releases go to WordPress.org with [pressship](https://www.npmjs.com/package/pressship); the SVN config lives in `~/.config/pressship`.

1. Bump the version in `jamies-header-effects.php` (header + `PLUGIN_VERSION`) and `readme.txt` (Stable tag + changelog).
2. Commit and push to GitHub.
3. `npx pressship verify` — lints PHP, checks readme/version consistency, shows the file list.
4. `npx pressship release` — builds the zip (honouring `.pressshipignore`), commits to SVN trunk, tags the release and pushes `.wordpress-org/` assets.

## License

GPL-2.0-or-later.
