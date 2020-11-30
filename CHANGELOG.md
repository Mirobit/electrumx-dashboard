# ElectrumX Dashboard Changelog

## 1.0.0 - 2020-11-30

This is a major update and breaks compatibility. You have to delete `data/geodatasessions.inc`, `data/geodatapeers.inc` and use the new `src\Config.php.example` (copy and remove `.example` from the name).

- [**New**] Seperate setting for peers and sessions for geo data
- [**New**] On the main, sessions and peers page is on the upper right a small refresh icon with info if Geo API calls were made
- [**Improved**] Modern favicon
- [**Improved**] More useful top information cards
- [**Improved**] Refactored parts of the code base
- [**Improved**] Layout and styling improvments
- [**Improved**] Updated hoster detection list
- [**Fixed**] Compatibility with latest ElectrumX
- [**Fixed**] Compatibility with IP-API.com limits

## 0.1.0 Beta - 2017-09-20

First Release
