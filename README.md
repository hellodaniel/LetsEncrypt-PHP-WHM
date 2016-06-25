


Installing software on shared servers isn't a possibility. This little script aims to provide a simple way to add Let's Encrypt SSL automation to shared servers (albeit with WHM at the moment). 


### Requirements

- PHP 5.5+ is required
- ```exec```, ```shell_exec``` and ```curl``` must be enabled on the host (this can often be found in the "PHP Version" section of cPanel
- Currently only works with WHM+cPanel


### Installation

- Drop the files onto a folder on your site (e.g.: /letsencrypt)
- Configure the letsencypt.ini
- Access via web browser


### Optional query string parameters

- ```force_install=1```: Make the WHM re-install, even if the certificate seems valid
- ```www=0``` (default: 1): Skip including www.domain.com


### Links
[The PHP ACME client by kelunik](https://github.com/kelunik/acme-client/blob/
master/doc/advanced-usage.md)

[Digiz cPanel script](https://digitz.org/blog/lets-encrypt-cpanel-script/)

[cPanel API](https://documentation.cpanel.net/display/SDK/Guide+to+WHM+API+1)


	