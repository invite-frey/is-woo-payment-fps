# Woocommerce Payment Gateway for Hong Kong Faster Payment System (FPS)

Woocommerce Payment Gateway for [Hong Kong FPS](https://www.hkma.gov.hk/eng/key-functions/international-financial-centre/financial-market-infrastructure/faster-payment-system-fps/). 

Features:

* Generates FPS QR Code based on your Woocommerce payment gateway configuration.
* Automatically include payment reference in the QR Code if your FPS account has "Ask to Pay" feature enabled.

### Prerequisites

* [Wordpress](https://wordpress.org/download/)
* [Woocommerce](https://woocommerce.com)
* An FPS enabled Hong Kong bank account.

### Installation

* Get the files 

```
$ git clone https://github.com/invite-frey/is-woo-payment-fps.git
```

* Move the is-woo-payment-fps directory to your wordpress plugins directory.
* Activate the plugin from the Wordpress admin plugins section.
* Configure your FPS account details in Woocommerce -> Settings -> Payments -> Hong Kong Faster Payment System (FPS).
* Enable the Payment Gateway.

### Configuration

* Title, Description and Payment Reference Guide are the customer facing instructions you can set to anything yo want.
* Account Id Type, Account Id and Bank Code identify the Payee Account. The QR Code is generated based on this information.
* Ask to Pay Enabled: If this feature is enabled, a reference number will be automatically generated and included in the QR Code. You need to contact your bank to use this feature. If this feature is NOT enabled the customer is presented with an automatically generated reference code and asked to enter it when completing the payment using the bank's FPS app. 

## Acknowledgements

* Inspiration for the FPS EMV string generation from https://github.com/nessgor/fps-hk-qrcode
* The QR codes are generated by [PHP QR Code](https://sourceforge.net/projects/phpqrcode/), which is included under the terms of its [license](https://sourceforge.net/p/phpqrcode/git/ci/master/tree/LICENSE). 

## Versioning

* 1.0 - First Release

## Donations

Donations are much appreciated if you found this resource useful. 

* Bitcoin: 32AULufQ6AUzq9jKZdcLjSxfePZbsqQKEp
* BTC Lightning via tippin.me: https://tippin.me/@freyhk

## License

This project is licensed under the LGPL 3 License - see the [LICENSE.md](LICENSE.md) file for details