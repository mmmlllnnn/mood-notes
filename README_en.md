<div align="center">
<h1> Mood Notes </h1>

<p align="center">
  <img src ="./icon.gif" style="width: 30%; max-width: 200px; border-radius: 20px;"/>
</p>

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?logo=php&logoColor=fff)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?logo=mysql&logoColor=fff)
![License](https://img.shields.io/badge/License-MIT-green)


<p align="center">
A lightweight, secure web app for capturing your thoughts. Clean design, fast, no dependencies.
</p>
</div>

[English] · [[简体中文]](README.md)

## Preview

[Click to Preview](https://handsome.eu.org/mood_notes/index.php)

<table>
  <tr>
    <td align="center">
      <img src="./Preview/1.png" width="260" style="border-radius: 12px; border: 2px solid #000;">
    </td>
    <td align="center">
      <img src="./Preview/2.png" width="260" style="border-radius: 12px; border: 2px solid #000;">
    </td>
    <td align="center">
      <img src="./Preview/3.png" width="260" style="border-radius: 12px; border: 2px solid #000;">
    </td>
  </tr>
</table>

## Features

- Create, edit, delete, and search notes
- Color-coded notes with 7 preset colors
- Random accent color on each page load
- Responsive — works on desktop, tablet, and phone
- Bottom-sheet editor on mobile
- No frameworks, no build tools, no npm

## Requirements

- PHP 7.4+
- MySQL 5.7+
- A web server (Nginx, Apache, Caddy, etc.)

## Quick Start

### 1. Clone

```bash
git clone https://github.com/mmmlllnnn/mood-notes.git
cd mood-notes
```

### 2. Import database

```bash
mysql -u root -p mood_notes < schema.sql
```

### 3. Configure

Edit `config.php`:

```php
return [
    'db_host'    => 'localhost',
    'db_name'    => 'mood_notes',
    'db_user'    => 'your_user',
    'db_pass'    => 'your_password',
    'db_charset' => 'utf8mb4',
];
```

### 4. Deploy

Copy all files to your web root. Make sure your web server routes requests to `index.php` and blocks direct access to `config.php` and `schema.sql`.

For Apache, the included `.htaccess` handles this automatically. For Nginx, add:

```nginx
location ~ (config\.php|schema\.sql|\.htaccess) {
    deny all;
}
```

### 5. Open

```
http://your-server/
```

## Project Structure

```
├── index.php        Frontend
├── api.php          REST API backend
├── config.php       Database configuration
├── schema.sql       Database schema
├── .htaccess        Apache security rules
└── README.md
```

## Configuration

Colors and accent can be changed in the config section at the top of `index.php`:

```php
// Note colors (keep api.php ALLOWED_COLORS in sync)
$noteColors = ['#e06850', '#e8963e', '#d4a84e', '#4a90d9', '#a86cc4', '#e05a8a', '#3ab0a0'];

// Accent candidates (random pick per page load)
$accents = $noteColors;
```

## API

All responses are JSON.

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api.php` | List all notes |
| POST | `/api.php` | Create a note |
| PUT | `/api.php?id=<uuid>` | Update a note |
| DELETE | `/api.php?id=<uuid>` | Delete a note |

POST/PUT body:

```json
{
  "content": "Your note here",
  "color": "#e06850"
}
```

## Security

| Threat | Protection |
|--------|-----------|
| SQL Injection | PDO prepared statements |
| XSS | All user input escaped via `textContent`/`innerHTML`, API returns pure JSON |
| CSRF | HMAC-signed token embedded in page, verified via `X-Api-Key` header |
| Direct file access | Server blocks `config.php`, `schema.sql`, and dotfiles |

## License

[MIT](LICENSE)
