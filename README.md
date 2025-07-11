# Lumen Project – Task & Notification Management API

This project is a **Lumen-based RESTful API** for managing users, tasks, notifications, and real-time messaging with broadcasting support. It includes user authentication, task assignment, notifications, and email reminders for pending tasks.

---

## Table of Contents

-   [Folder Structure](#folder-structure)
-   [Features](#features)
-   [Getting Started](#getting-started)
    -   [Prerequisites](#prerequisites)
    -   [Installation](#installation)
-   [Docker Setup](#docker-setup)
-   [Configuration](#configuration)
-   [Usage](#usage)
    -   [Running the API](#running-the-api)
    -   [Scheduled Tasks](#scheduled-tasks)
    -   [Broadcasting & Real-Time](#broadcasting--real-time)
-   [API Documentation](#api-documentation)
-   [Email Templates](#email-templates)
-   [Testing](#testing)

---

## Folder Structure

```
lumen-project/
├── app/
│   ├── Console/
│   │   ├── Commands/
│   │   │   └── SendPendingTasksMail.php
│   │   └── Kernel.php
│   ├── Events/
│   │   ├── Event.php
│   │   ├── ExampleEvent.php
│   │   ├── Message.php
│   │   ├── PrivateMessage.php
│   │   ├── TaskMessage.php
│   │   ├── TaskRegistered.php
│   │   ├── TaskRelatedMessages.php
│   │   └── UserRegistered.php
│   ├── Exceptions/
│   │   └── Handler.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AnalyticsController.php
│   │   │   ├── AuthController.php
│   │   │   ├── BroadcastAuthController.php
│   │   │   ├── Controller.php
│   │   │   ├── ExampleController.php
│   │   │   ├── NotificationsController.php
│   │   │   ├── RoleController.php
│   │   │   ├── TaskController.php
│   │   │   ├── UserController.php
│   │   │   └── UserRoleController.php
│   │   └── Middleware/
│   │       ├── Authenticate.php
│   │       ├── CorsMiddleware.php
│   │       └── ExampleMiddleware.php
│   ├── Jobs/
│   │   ├── ExampleJob.php
│   │   └── Job.php
│   ├── Listeners/
│   │   ├── ExampleListener.php
│   │   ├── SendRegisteredEmail.php
│   │   └── SendTaskAssignedEmail.php
│   ├── Mail/
│   │   └── GenericMail.php
│   ├── Models/
│   │   ├── ActivityLog.php
│   │   ├── Notification.php
│   │   ├── Role.php
│   │   ├── Task.php
│   │   └── User.php
│   ├── Providers/
│   │   ├── AppServiceProvider.php
│   │   ├── AuthServiceProvider.php
│   │   ├── BroadcastServiceProvider.php
│   │   └── EventServiceProvider.php
│   └── Services/
│       ├── AnalyticsService.php
│       ├── MailService.php
│       ├── NotificationService.php
│       ├── RoleService.php
│       ├── TaskService.php
│       ├── UserActivityService.php
│       └── UserService.php
├── bootstrap/
│   └── app.php
├── config/
│   ├── auth.php
│   ├── broadcasting.php
│   ├── database.php
│   ├── jwt.php
│   ├── mail.php
│   └── queue.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeds/
├── public/
│   └── index.php
├── resources/
│   └── views/
│       └── emails/
│           ├── confirmation.blade.php
│           ├── pendingTasks.blade.php
│           ├── ResetPassword.blade.php
│           └── taskAssigned.blade.php
├── routes/
│   ├── web.php
│   └── channels.php
├── storage/
│   ├── app/
│   ├── framework/
│   └── logs/
├── tests/
├── vendor/
├── .dockerignore
├── .editorconfig
├── .env
├── .env.example
├── .gitignore
├── .styleci.yml
├── artisan
├── composer.json
├── composer.lock
├── Dockerfile
├── phpunit.xml
├── README.md
└── supervisord.conf
```

---

## Features

-   **User Authentication** (JWT)
-   **Task Management**: Create, update, assign, soft-delete, and list tasks
-   **User Roles**: Role-based access (admin, user, etc.)
-   **Notifications**: Send and list notifications for users
-   **Email Reminders**: Scheduled emails for users with pending tasks
-   **Real-Time Messaging**: Private chat channels using broadcasting (Pusher/Laravel Echo)
-   **Analytics**: Task statistics and completion time reports

---

## Getting Started

### Prerequisites

-   PHP >= 8.0
-   Composer
-   MySQL or compatible database
-   Node.js & npm (for frontend or broadcasting)

### Installation

1. **Clone the repository:**

    ```sh
    git clone <repo-url>
    cd lumen-project
    ```

2. **Install dependencies:**

    ```sh
    composer install
    ```

3. **Copy and configure `.env`:**

    ```sh
    cp .env.example .env
    ```

    Update database, mail, and broadcasting settings in `.env`.

4. **Generate application key (if needed):**

    ```sh
    php artisan key:generate
    ```

5. **Run migrations:**
    ```sh
    php artisan migrate
    ```

---

## Docker Setup

This project includes a `docker-compose.yml` for easy local development with Docker.

### docker-compose.yml

```
version: "3.8"
services:
  backend:
    build: ./lumen-project
    container_name: lumen-backend
    restart: unless-stopped
    ports:
      - "8000:9000"
    environment:
      # Add your environment variables here or use .env
    depends_on:
      - db

  frontend:
    build: ./react-project/my-react16-app
    container_name: react-frontend
    restart: unless-stopped
    ports:
      - "3000:80"
    depends_on:
      - backend

  db:
    image: mysql:8
    container_name: mydb
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: lumen_db
      MYSQL_USER: root
      MYSQL_PASSWORD: pass
      MYSQL_ROOT_PASSWORD: rootpass
    ports:
      - "3306:3306"
    volumes:
      - db_data:/var/lib/mysql
volumes:
  db_data:
```

### How to Run with Docker

1. **Build and start all services:**

    ```sh
    docker-compose up --build
    ```

2. **Access the services:**

    - **Backend (Lumen API):** http://localhost:8000
    - **Frontend (React):** http://localhost:3000
    - **MySQL:** localhost:3306 (user: root, password: rootpass)

3. **Stop all services:**

    ```sh
    docker-compose down
    ```

---

## Configuration

-   Edit `.env` for database, mail, JWT, and broadcasting settings.
-   Set up mail credentials for Gmail, Mailgun, or your SMTP provider.
-   Configure broadcasting (Pusher, Redis, etc.) as needed.

---

## Usage

### Running the API

```sh
php -S localhost:8000 -t public
```

### Scheduled Tasks

To send daily pending task emails, run the scheduler:

```sh
php artisan schedule:work
```

Or add to your system's cron:

```
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

### Broadcasting & Real-Time

-   Configure Pusher or your preferred broadcaster in `.env`.
-   Use Laravel Echo or Pusher JS on the frontend to listen to private channels.

---

## API Documentation

-   **Analytics:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-e3fbce6b-94b8-4549-9ad1-971514632247?action=share&creator=45605049
-   **Auth:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-4befe122-2db9-41f7-8ccc-54f1a74d2cf2?action=share&creator=45605049
-   **User:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-98ed9e01-9736-4f3d-91fe-e48727ce643d?action=share&creator=45605049
-   **Task:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-782d44b0-70b7-4a1e-9659-1ac99cf97c78?action=share&creator=45605049
-   **Notification:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-1bb3ee23-bc74-4845-8795-206cd70cd64a?action=share&creator=45605049
-   **Message:** https://adityakhandelwal-6320578.postman.co/workspace/Aditya-Khandelwal's-Workspace~a1296a69-44f7-407f-83d9-4adcce341c03/collection/45605049-ef2020f7-204a-4e25-bed2-3ccc9c165dba?action=share&creator=45605049

All endpoints are POST unless otherwise specified.

---

## Email Templates

-   Located in `resources/views/emails/`
-   Example: `pendingTasks.blade.php` for pending task reminders

---

## Testing

You can use tools like Postman or curl to test API endpoints.
