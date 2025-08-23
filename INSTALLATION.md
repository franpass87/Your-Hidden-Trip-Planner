# Your Hidden Trip Planner - Installation Guide

## For WordPress Users (Non-Developers)

⚠️ **IMPORTANT**: Do NOT download using GitHub's "Download ZIP" button!

### Correct Installation Method:
1. Go to the [Releases page](https://github.com/franpass87/Your-Hidden-Trip-Planner/releases)
2. Download `your-hidden-trip-planner-dist.zip` from the latest release
3. Extract the ZIP file
4. Upload the `your-hidden-trip-planner` folder to your `/wp-content/plugins/` directory
5. Activate the plugin in your WordPress admin panel

### Why This Method?
The GitHub "Download ZIP" button only downloads source code without the required vendor dependencies (like dompdf for PDF generation). This causes the plugin to fail with a "White Screen of Death" error.

The distribution package from Releases includes all necessary dependencies and is ready to install.

## For Developers

If you want to contribute or customize the plugin:

1. Clone this repository:
   ```bash
   git clone https://github.com/franpass87/Your-Hidden-Trip-Planner.git
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. The plugin will now work normally in your development environment.

## Build Distribution Package

To create a distribution package yourself:

```bash
composer install --no-dev --optimize-autoloader
```

Then copy the plugin files including the `vendor/` directory to your WordPress installation.

## Troubleshooting

### "Missing required dependencies" Error
If you see an admin notice about missing dependencies:
- Download the correct distribution package from the Releases page
- Or run `composer install` if you're working with source code

### PDF Generation Not Working
PDF generation requires the dompdf library. Make sure you:
- Used the distribution package from Releases, or
- Ran `composer install` in the plugin directory