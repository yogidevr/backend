# 🚀 Backend API Garuda Merah Putih

Powerful backend service built with **Laravel**.

---

# ✨ Features

- ⚡ REST API Ready
- 🔐 Authentication Support
- 🗄️ Database Migration
- 📁 Storage Linking
- 🔥 Clean Laravel Structure
- 🛠️ Easy Development Workflow

---

# 🛠️ Tech Stack

- Laravel
- PHP
- MySQL / Postgresql
- Composer

---

# 📁 Project Structure

```txt
backend/
├── app/
├── bootstrap/
├── config/
├── database/
├── public/
├── resources/
├── routes/
├── storage/
└── ...
```

---

# 🚀 Getting Started

## 1️⃣ Clone Repository

```bash
git clone github.com/yogidevr
cd backend
```

---

# ⚙️ Installation

## Install Dependencies

```bash
composer install
```

---

## Setup Environment

Copy file environment:

```bash
cp .env.example .env
```

---

## Generate App Key

```bash
php artisan key:generate
```

---

## Setup Storage Link

```bash
php artisan storage:link
```

---

## Run Database Migration

```bash
php artisan migrate
```

---

# ▶️ Run Development Server

```bash
php artisan serve
```

Server akan berjalan di:

```txt
http://127.0.0.1:8000
```

---

# 🧹 Useful Commands

## Clear All Cache

```bash
php artisan optimize:clear
```

---

## Fresh Migration + Seeder

```bash
php artisan migrate:fresh --seed
```

---

## Generate New App Key

```bash
php artisan key:generate
```

---

## Queue Worker

```bash
php artisan queue:work
```

---

## Run Scheduler

```bash
php artisan schedule:work
```

---

# 🔄 Git Workflow

## 📤 Push Backend Changes

```bash
git add .
git commit -m "update backend"
git push origin main
```

---

## 📥 Pull Latest Backend Updates

```bash
git fetch origin
git restore --source=origin/main backend
```

---

# 📋 Requirements

Pastikan sudah menginstall:

- PHP 8+
- Composer
- MySQL / Postgresql
- Git

---
