# Backlog Management Vertical

This vertical handles project backlogs, task management, and issue tracking.

## Features

- Create and manage backlogs
- Assign tasks to users
- Track progress and milestones
- Integration with Git systems for automatic issue creation

## Setup

1. Run migrations: `php artisan migrate`
2. Configure in config/backlog.php

## API Endpoints

- GET /api/backlogs - List backlogs
- POST /api/backlogs - Create backlog
- PUT /api/backlogs/{id} - Update backlog

## Documentation

- [API Docs](./docs/api.md)
- [User Guide](./docs/user.md)
