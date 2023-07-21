# PrestaShop Critical CSS

[![PHP tests](https://github.com/PickleBoxer/pscriticalcss/actions/workflows/php.yml/badge.svg?branch=main)](https://github.com/PickleBoxer/pscriticalcss/actions/workflows/php.yml)
[![Build & Release draft](https://github.com/PickleBoxer/pscriticalcss/actions/workflows/build-release.yml/badge.svg?branch=main)](https://github.com/PickleBoxer/pscriticalcss/actions/workflows/build-release.yml)

The PrestaShop Critical CSS module automatically generates optimized CSS files for your store's front controllers, without requiring Node.js. These files can help improve your website's performance and speed.

## Installation

To install the module, follow these steps:

1. Download the latest release of the module from the [releases page](https://github.com/your-username/pscriticalcss/releases).
2. Extract the contents of the archive to the `modules` directory of your PrestaShop installation.
3. In the PrestaShop backoffice, navigate to the "Modules" page and search for "Critical CSS".
4. Click the "Install" button next to the "Critical CSS" module.
5. Once the module is installed, click the "Configure" button to access the module's configuration page.

## Building the module

### Direct download

If you want to get a zip ready to install on your shop. You can directly download it by clicking [here](https://github.com/your-username/pscriticalcss/releases).

### Production

1. Clone this repo `git clone git@github.com:PickleBoxer/pscriticalcss.git`
2. `make build-prod-zip`

The zip will be generated in the root directory of the module.

### Development

1. Clone this repo
2. `make docker-build`

## Configuration

On the configuration page, you can enable or disable the functionality of the module to regenerate the CSS files for all front controllers.

Please note that this module has only been tested on the Classic theme from PrestaShop. It may cause issues with custom themes.

## License

This module is licensed under the Academic Free License version 3.0. See the [LICENSE](LICENSE) file for details.

## Contributing

Contributions are welcome! If you find a bug or have a feature request, please [open an issue](https://github.com/your-username/pscriticalcss/issues/new). If you would like to contribute code, please fork the repository and submit a pull request.

## Credits

This module was created by PickleBoxer.