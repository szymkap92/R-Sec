# R-SEC - Computer Networking & Security

## Project Structure

```
RSec/
├── public/                     # Public web files
│   ├── index.html             # Main homepage
│   ├── assets/                # Static assets
│   │   ├── css/              # Stylesheets
│   │   ├── js/               # JavaScript files
│   │   └── images/           # Images
│   │       ├── logos/        # Company logos
│   │       ├── team/         # Team member photos
│   │       └── products/     # Product images
│   └── pages/                # HTML pages
│       ├── pl/               # Polish language pages
│       └── en/               # English language pages
├── backend/                   # Backend API and logic
│   ├── api/                  # API endpoints
│   ├── config/               # Configuration files
│   ├── controllers/          # Controllers
│   ├── middleware/           # Middleware
│   ├── models/               # Data models
│   └── setup/                # Setup scripts
├── database/                 # Database schemas
├── docs/                     # Documentation
│   ├── technical/            # Technical documentation
│   └── user/                 # User documentation
└── config/                   # Environment configurations
```

## Getting Started

1. Configure your web server to point to the `public/` directory
2. Update database configuration in `backend/config/config.php`
3. Run database setup from `backend/setup/init_database.php`

## Development

- Frontend files are in `public/`
- Backend API is in `backend/`
- Assets are organized by type in `public/assets/`