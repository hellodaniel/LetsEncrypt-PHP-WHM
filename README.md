


Installing software on shared servers isn't a possibility. This little script aims to provide a simple way to add Let's Encrypt SSL automation to shared servers (albeit with WHM at the moment). 


### Requirements

- PHP 5.5+ is required
- ```exec```, ```shell_exec``` and ```curl``` must be enabled on the host (this can often be found in the "PHP Version" section of cPanel
- Currently only works with WHM+cPanel


### Installation

- Drop the files onto a folder on your site (e.g.: /letsencrypt)
- Configure the letsencypt.ini
- Access via web browser


### Config file

	; The domain name(s)
	; Multiple domains can be colon separated, www. will automatically be pre-pended
	domain = 

	; Use Let's Encrypt staging to avoid quote limits
	staging = 0

	; The cPanel username
	; This is required to perform the SSL installation via WHM
	cpanel_user = 

	; This e-mail will receive expiration notices from Let's Encrypt
	email = email@domain.com

	; Where to output the challenge files 
	; (must be the root of the domain)
	public_html = "../"

	; Where to store the output files and certificates
	storage = "../../.LetsEncrypt"

	; WHM username (used to access the API)
	whm_user = ""

	; WHM key (get this from your WHM console)
	whm_key = ""



### Optional query string parameters

- ```force_install=1```: Make the WHM re-install, even if the certificate seems valid
- ```www=0``` (default: 1): Skip including www.domain.com


### Credits 

All the Let's Encypt magic is done by  [kelunik's PHP ACME client](https://github.com/kelunik/acme-client/blob/
master/doc/advanced-usage.md). All I've done is build a wrapper around it to install the certificates via WHM's API. 

### Links

- [The PHP ACME client by kelunik](https://github.com/kelunik/acme-client/blob/
master/doc/advanced-usage.md)
- [Digiz cPanel script](https://digitz.org/blog/lets-encrypt-cpanel-script/)
- [cPanel API](https://documentation.cpanel.net/display/SDK/Guide+to+WHM+API+1)


	