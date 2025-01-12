# Woo Product Tester

A **single-product WooCommerce tester** plugin for WordPress that logs and exports detailed product data (including variations) into a CSV. It provides a modern interface with Awesomplete-powered product search, displays product data in a card layout, and includes a downloadable log/CSV for troubleshooting or record-keeping.

> **Author:** [Kyle Altenderfer](https://altenderfer.io/)  

## Features

- **Single-Product Testing**  
  Search for (or enter the ID of) a product, then generate a detailed log of its attributes, including:
  - Price, Regular Price, Sale Price, etc.  
  - Stock Status, Tax Class, Shipping Class  
  - Attributes, Variation Details (if applicable)  
  - Full HTML Descriptions/Short Descriptions  
  - Cart price testing (subtotal/total extracted as numeric in CSV)

- **Log & CSV Export**  
  - On-screen log for quick review.  
  - Automatic CSV export with dynamic columns based on the tested product data.  

- **Modern UI**  
  - Uses [Awesomplete](https://github.com/LeaVerou/awesomplete) for product auto-suggestion.  
  - Material Icons from Google.  
  - Responsive “card-like” layout for product data.  

- **Seamless Integration**  
  - Designed as a standard WordPress plugin.  
  - Works with any modern WooCommerce versions (3.x+).  

## Requirements

- **WordPress** 5.0 or higher  
- **WooCommerce** 3.x or higher  
- **PHP** 7.2 or higher recommended  

## Installation

1. **Download** the plugin files (or clone this repository).  
2. **Upload** the folder `woo-product-tester` to the `wp-content/plugins/` directory on your WordPress site, or use the “Upload Plugin” feature in the WordPress Admin.  
3. **Activate** the plugin via `WordPress Admin > Plugins`.  
4. Ensure **WooCommerce** is active.  

## Usage

1. In your WordPress Admin, navigate to **WooCommerce > Product Tester**.  
2. Under “Single Product Tester,” start typing a product name in the “Product Name” field.  
   - If you type 2+ characters, Awesomplete will suggest matching products.  
   - Alternatively, enter a Product ID directly.  
3. Click **Start Test**. A detailed product log (including variations, if any) will appear on the screen.  
4. **Download** the log or CSV using the “Download Log” and “Download CSV” buttons.  

### What’s Logged

- **Product Details**: Title, SKU, GTIN, Price, Stock, Tax Class, Shipping Class, etc.  
- **Variations**: Variation ID, Price, Attributes, etc.  
- **Descriptions**: Full HTML is retained in CSV.  
- **Cart Tests**: Price, Subtotal, and Total (extracted as numeric in CSV).

## Contributing

Feel free to open an issue or submit a pull request if you encounter any bugs or have feature requests.  

## License

This plugin is open-sourced software licensed under the MIT License.  

---

### Author

**Kyle Altenderfer**  
- Website: [altenderfer.io](https://altenderfer.io/)  
- GitHub: [github.com/altenderfer](https://github.com/altenderfer)

Enjoy using **Woo Product Tester**!  

---

### Screenshot

![Woo Product Tester Screenshot](https://altenderfer.io/github/woo-product-tester-screenshot01_web.webp)
