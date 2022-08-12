# PLG_SYSTEM_HYPHENATEGHSVS_SLIM aka PLG_SYSTEM_HYPHENATEGHSVS

Joomla system plugin. Hyphenation for browsers that do NOT support CSS property hyphens or do not support some languages. Hyphenopoly-only variant.

This plugin replaces plugin [PLG_SYSTEM_HYPHENATEGHSVS](https://github.com/GHSVS-de/plg_system_hyphenateghsvs) that additionally provides old Hyphenator JavaScript library from https://github.com/mnater/Hyphenator programmed by Mathias Nater (mnater)

Intelligent Hyphenation for browsers that do NOT support CSS property `hyphens` or do not support languages that you can select in this plugin. See supported languages at https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim/tree/master/media/js/hyphenopoly/patterns

## Description/Documentation
See configuration options inside the plugin after installation.

You can report issues, ask questions in english or german: https://github.com/GHSVS-de/plg_system_hyphenateghsvs_slim/issues or via email (see https://ghsvs.de/kontakt)

## Thanks
This Joomla plugin provides Hyphenopoly JavaScript library from https://github.com/mnater/Hyphenopoly programmed by Mathias Nater (mnater).

----------------------

# My personal build procedure (WSL 1, Debian, Win 10)

- Build uses local fork of repo [buildKramGhsvs](https://github.com/GHSVS-de/buildKramGhsvs).
- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_hyphenateghsvs_slim`

## node/npm updates/installation
- `npm install` (if never done before)

### Update dependencies
- `npm run updateCheck` or (faster) `npm outdated`
- `npm run update` (if needed) or (faster) `npm update --save-dev`

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.

### For Joomla update and changelog server
- Create new release with new tag.
  - See and copy and complete release description in `dist/release.txt`.
- Extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Copy/paste and make necessary additions.
