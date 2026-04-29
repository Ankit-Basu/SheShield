# Email Configuration Setup

## Overview
This document explains how to set up email functionality for the SheShield application after cloning the repository. The email credentials have been removed from the codebase for security reasons.

## Setup Instructions

1. Navigate to the `config` directory
2. Copy the `email_config.example.php` file to create a new file named `email_config.php`:
   ```
   cp config/email_config.example.php config/email_config.php
   ```
3. Open the newly created `email_config.php` file in your preferred editor
4. Fill in your SMTP credentials:
   - `SMTP_HOST`: Your SMTP server (e.g., smtp.gmail.com)
   - `SMTP_USERNAME`: Your email address
   - `SMTP_PASSWORD`: Your email password or app password
   - `SMTP_PORT`: SMTP port (typically 465 for SSL or 587 for TLS)
   - `SMTP_ENCRYPTION`: Use 'ssl' or 'tls' based on your SMTP server requirements
   - `DEFAULT_FROM_EMAIL`: The email address that will appear as the sender
   - `DEFAULT_FROM_NAME`: The name that will appear as the sender

## Using Gmail
If you're using Gmail, you'll need to:
1. Enable 2-Factor Authentication on your Google account
2. Generate an App Password specifically for this application
3. Use that App Password instead of your regular Gmail password

## Security Note
The `email_config.php` file is included in `.gitignore` to prevent accidentally committing your credentials to the repository. Never commit files containing sensitive information like passwords or API keys.