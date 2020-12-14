# ElectrumX Dashboard

![](https://user-images.githubusercontent.com/13236924/100623563-9d8b7880-3322-11eb-9da2-5083d28cefbd.png)

ElectrumX Dashboard (EXD) is a lightweight dashboard for [ElectrumX](https://github.com/spesmilo/electrumx) servers. 
Check out [Bitcoin Node Manager](https://github.com/Mirobit/electrumx-dashboard) as a Bitcoin Core server dashboard.

## Features

- Dashboard with general information about the server, establisted sessions and known peers
- List of all establisted sessions (including traffic, client country, isp, ...)
- List of all known peers (including client, country, isp, ...)
- Create rules to manage sessions
  - Disconnect or log sessions that violate rules
  - Set gobal events that trigger the execution of rules, run rules manually or set up a cron job

## Requirements

- ElectrumX 1.11.0+
- Web server
- PHP 7.0.0+
  - cURL

## Installation

1. Download EXD either from [here](https://github.com/mirobit/electrumx-dashboard/releases) or clone this repository.
2. Copy `src/Config.php.example` and remove `.example`. Open `src/Config.php` and set your EXD password.
3. Make sure the EXD folder is in your web servers folder (e.g. `/www/html/`). If the server is publicly accesible, I recommend renaming the EXD folder to something unique. Although EXD is password protected and access can be limited to a specific IP, there can be security flaws and bugs.
4. Open the URL to the folder in your browser and login with the password choosen in `src/Config.php`.
5. Optional: Run `chmod -R 770 /path-to-folder/{data, src, views}`. Only necessary for non Apache servers (`AllowOverride All` necessary), that are publicly accessible. For more information, read next section.

## Security

- All pages and control functionality are only accessible for logged in users. The only exception is if you use the `Rules` cron job functionality. But a password based token is requiered and the functionality is only able to apply rules.
- Access to EXD is by default limited to localhost. This can be expanded to a specific IP or disabled. If disabled, make sure to protect the EXD folder (.htaccess or rename it to something unique that an attacker will not guess). An attacker could "guess" your password, since there is no build-in brute force protection.
- The `data` folder contains your rules, logs and geo information about your peers. Make sure to protect (e.g. `chmod -R 770 data`) this sensitive information if your web server is publicly accessible. The previously mentioned IP protection doesn't work here. If you use `Apache` you are fine, since the folder is protected with `.htaccess` (make sure `AllowOverride All` is set in your Apache config file).

## Roadmap

- [ ] Improve project structure
- [ ] Improve error handling
- [ ] Display expanded peer/session info (popup)
- [ ] Highlight suspicious sessions
