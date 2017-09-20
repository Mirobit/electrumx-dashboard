# ElextrumX Dashboard Beta

ElextrumX Dashboard (EXD) is a lightweight dashboard for ElectrumX servers.

## Features

* Dashboard with general information about the server, establisted sessions and known peers
* List of all establisted sessions (including used traffic, version country, isp, ...)
* List of all known peers (including version, country, isp, ...)
* Create rules to manage sessions
	* Disconnect or log sessions that violate rules
	* Set gobal events that trigger the execution of rules
	* Run rules manually or set up a cron job

## Requirements

* ElectrumX 1.1+
* Web server
* PHP 7.0.0+
    * cURL

## Installation

1. Download EXD either from [here](https://github.com/mirobit/electrumx-dashboard/releases) or by cloning this the repository.
2. Edit `src/Config.php` to set up a password.
3. Upload the folder to the public directory of your web server. If the folder is accesible via the internet, I recommed renaming the folder to something unique. Although EXD is password protected and access can be limited to a specific IP, there can be security flaws and bugs.
4. Open the URL to the folder in your browser and login with the password choosen in `src/Config.php`.
5. Optional: Run `chmod -R 770 /path-to-folder/{data, src, views}`. Only necessary for non Apache (`AllowOverride All` necessary) and publicly accessible web server. For more information, read next section.

## Security

* All pages and control functionality are only accessible for logged in users. The only exception is if you use the `Run Rules' cron job functionality. But a password based token is requiered
and the functionality is only able to apply rules. 
* Access to EXD is by default limited to localhost. This can be expanded to a specific IP or disabled. If disabled, make sure to protect the EXD folder (.htaccess, rename it to something unique 
that an attacker will not guess). An attacker could "guess" your password, since there is no build-in brute force protection (if IP protection is disabled).
* The `data` folder contains your rules, logs and geo information about sessions and peers. Make sure to protect (e.g. `chmod -R 770 data`) this sensitive information if your web server is publicly accessible. The previously mentioned
IP protection doesn't work here. If you use `Apache` you are fine, since the folder is protected with `.htaccess` (make sure `AllowOverride All` is set in your `apache2.conf` file).

## Roadmap

- [ ] Improve project structure
- [ ] Improve OOP
- [ ] Improve error handling
- [ ] Import rules functionality
- [ ] Display expanded peer/sessions info (popup)
- [ ] Highlight suspicious sessions
