# plg_system_hyphenateghsvs

Joomla-Plugin.

Intelligent Hyphenation for browsers that do NOT support CSS property `hyphens` or do not support languages that you can select in this plugin. See supported languages at https://github.com/GHSVS-de/plg_system_hyphenateghsvs/tree/master/media/js/hyphenopoly/patterns

You can report issues, ask questions in english or german: https://github.com/GHSVS-de/plg_system_hyphenateghsvs/issues or via email (see https://ghsvs.de/kontakt)

## Description/Documentation
You'll find detailed descriptions of all configuration options inside the plugin after installation.

## Uses since release 2019.03.10 **recommended** Hyphenopoly.js.
https://github.com/GHSVS-de/plg_system_hyphenateghsvs/releases

## DEUTSCH: Update-Hinweis für Versionen <= 2022.06.22
In Version 2022.08.12 dieses Joomla-Plugins wurden einige Features entfernt. Bitte beachten Sie das bei einem Update auf eine höhere Version.

- Veralteter Hyphenator-Modus. Updaten Sie nicht, wenn Sie diesen im Plugin eingestellt haben!
- Diverse Log- und Debug-Funktionalitäten.
- JQuery-Javascript-Alternative.

### Wenn Sie updaten wollen
Laden Sie sich die Version `2022.08.01` dieses Plugins herunter und installieren es im Joomla-Backend.

https://github.com/GHSVS-de/plg_system_hyphenateghsvs/releases/tag/2022.08.01

### Wenn Sie NICHT updaten wollen...
und zukünftig keine Update-Hinweise im Backend mehr sehen wollen.

## ENGLISCH: Update note for versions <= 2022.06.22

## Thanks
This Joomla plugin...

...provides old Hyphenator JavaScript library from https://github.com/mnater/Hyphenator programmed by Mathias Nater (mnater) (**Only versions up to version 2022.06.22 of this Joomla plugin**).

...provides newer Hyphenopoly JavaScript library from https://github.com/mnater/Hyphenopoly programmed by Mathias Nater (mnater).

# My personal build procedure (WSL 1, Debian, Win 10)

**@since v2022.06.22: Build procedure uses local repo fork of https://github.com/GHSVS-de/buildKramGhsvs**

- Prepare/adapt `./package.json`.
- `cd /mnt/z/git-kram/plg_system_hyphenateghsvs`

## node/npm updates/installation
- `npm install` (if never done before)

### Update dependencies
- `npm run updateCheck` or (faster) `npm outdated`
- `npm run update` (if needed) or (faster) `npm update --save-dev`

## PHP Codestyle
If you think it's worth it.
- `cd /mnt/z/git-kram/php-cs-fixer-ghsvs`
- `npm run plg_system_hyphenateghsvsDry` (= dry test run).
- `npm run plg_system_hyphenateghsvs` (= cleans code).
- `cd /mnt/z/git-kram/plg_system_hyphenateghsvs` (back to this repo).

## Build installable ZIP package
- `node build.js`
- New, installable ZIP is in `./dist` afterwards.
- All packed files for this ZIP can be seen in `./package`. **But only if you disable deletion of this folder at the end of `build.js`**.

### For Joomla update and changelog server
- Create new release with new tag.
- - See and copy and complete release description in `dist/release.txt`.
- Extracts(!) of the update and changelog XML for update and changelog servers are in `./dist` as well. Copy/paste and make necessary additions.
