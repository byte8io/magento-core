# Byte8 Core

Core utility module for Magento 2 providing shared services and components used across Byte8 modules.

## Features

- **Message Collection** — batch message tracking with status aggregation and multiple output formats (console, HTML, JSON)
- **Data Storage** — generic key-value and entity-specific in-memory storage utilities
- **Catalog Services** — product image/media management, SKU storage, category and attribute data access, configurable product support
- **EAV Utilities** — attribute set/group management, option creation/mapping, backend table resolution
- **Email Notifications** — template-based email sending with configurable recipients and senders
- **Logging** — PSR-3 logger with daily log rotation, sensitive data masking, and email alerts for critical errors
- **Admin UI Components** — status renderers with Font Awesome icons, grid columns (JSON, modal, tooltip), action buttons, and control buttons
- **Array Utilities** — flatten, search multidimensional, associative check
- **Database Compatibility** — transparent support for Open Source (`entity_id`) and Adobe Commerce with staging (`row_id`)

## Console Commands

```
bin/magento pub:static:clean
```

Clean static view files from `pub/static`.

## Configuration

Stores > Configuration > Byte8 > Core Configuration

- Log rotation settings
- Email logging
- Font Awesome icon support
- Installed module list

## Requirements

- PHP 8.1+
- Magento 2.4.x

## License

[MIT](LICENSE.txt)

## Support

Byte8 Ltd — support@byte8.io
