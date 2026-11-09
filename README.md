# CashIko

CashIko is a web application built with **Laravel** and **Vue 3**.

---

## What You Need

Before starting, make sure you have these installed on your computer:
* **PHP** (version 8.3 or newer)
* **Composer** (PHP package manager)
* **Node.js** & **npm** (version 18 or newer)
* **SQLite** (usually included with PHP)

---

## Quick Setup

Run these commands in your terminal inside the project folder:

### 1. Install Dependencies
```bash
composer install
npm install
```

### 2. Set Up Environment File
Create your local settings file:
```bash
cp .env.example .env
```
*(On Windows PowerShell, run: `copy .env.example .env`)*

### 3. Generate App Key
```bash
php artisan key:generate
```

### 4. Create Database & Tables
Create the database file (if it does not exist) and create the tables:
```bash
php artisan migrate
```

*(Optional)* Add sample test user:
```bash
php artisan db:seed
```

---

## Running the App

Start the backend and frontend servers:

**Terminal 1 (Backend):**
```bash
php artisan serve
```

**Terminal 2 (Frontend):**
```bash
npm run dev
```

Open your browser and visit:  
**http://localhost:8000**

---

## System Details

For an overview of features, database structure, technologies used, and architecture, see [SYSTEM_DETAILS.md](SYSTEM_DETAILS.md).

---

## Helpful Commands

| Action | Command |
| :--- | :--- |
| Run all checks / tests | `php artisan test` |
| Reset database | `php artisan migrate:fresh --seed` |
| Build for production | `npm run build` |
