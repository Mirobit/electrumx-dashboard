# ElectrumX Dashboard

![](https://user-images.githubusercontent.com/13236924/100623563-9d8b7880-3322-11eb-9da2-5083d28cefbd.png)

ElectrumX Dashboard (EXD) is a lightweight dashboard for [ElectrumX](https://github.com/spesmilo/electrumx) servers.
Check out [Bitcoin Node Manager](https://github.com/Mirobit/bitcoin-node-manager) as a Bitcoin Core server dashboard.

## Features

- Dashboard with general information about the server, established sessions and known peers
- List of all established sessions (including traffic, client country, isp, ...)
- List of all known peers (including client, country, isp, ...)
- Create rules to manage sessions
  - Disconnect or log sessions that violate rules
  - Set global events that trigger the execution of rules, run rules manually or set up a cron job

## Requirements

- ElectrumX 1.11.0+
- Web Server (Apache, Nginx, PHP Server)
- PHP 7.0.0+
  - curl extension
- Docker (Alternative to Web Server and PHP)

## Installation

1. Clone this repository or [download](https://github.com/mirobit/electrumx-dashboard/release) it.
2. Copy `src/Config.sample.php` and remove `.sample`. Open `src/Config.php` and enter your ElectrumX RPC IP and set the EXD password.

### Manual setup

3. Make sure the EXD folder is in your web servers folder (e.g. `/var/www/html/`). If the server is publicly accessible, I recommend renaming the EXD folder to something unique. Although EXD is password protected and access can be limited to a specific IP, there can be security flaws and bugs.
4. Open the URL to the folder in your browser and login with the password chosen in `src/Config.php`.

### Docker

The EXD folder is mounted as volume in Docker. This way you can edit `src/Config.php` and update BNM (`git pull`) at any time without connecting to the container.

3. Change the RPC IP in `src/Config.php` to the docker network interface IP.
4. Run either `docker-compose up -d` or `docker run -d -p 8001:80 --name exd -v ${PWD}:/var/www/html php:7.4-apache` in the BNM folder.
5. Add the following to your `elextrumx.conf` under SERVICES:

```
rpc://172.17.0.1:ELECXTRUMX-RPC-PORT
```

6. EXD should now be accessible under http://server-ip:8001.

## Security

- All pages and control functionality are only accessible for logged in users. The only exception is if you use the `Rules` cron job functionality. But a password based token is required and the functionality is only able to apply rules.
- Access to EXD is by default limited to localhost. This can be expanded to a specific IP or disabled. If disabled, make sure to protect the EXD folder (.htaccess or rename it to something unique that an attacker will not guess). An attacker could "guess" your password, since there is no build-in brute force protection.
- The `data` folder contains your rules, logs and geo information about your peers. Make sure to protect (e.g. `chmod -R 770 data`) this sensitive information if your web server is publicly accessible. The previously mentioned IP protection doesn't work here. If you use `Apache` you are fine, since the folder is protected with `.htaccess` (make sure `AllowOverride All` is set in your Apache config file).

## Roadmap

- [ ] Improve project structure
- [ ] Improve error handling
- [ ] Display expanded peer/session info (popup)
