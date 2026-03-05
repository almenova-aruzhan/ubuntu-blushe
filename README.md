# ubuntu-blushe

Blushe — Server Infrastructure Project

Overview

Blushe is a demonstration project that shows the deployment of a web application infrastructure using Linux server technologies and containerized services.
The goal of the project is to demonstrate the configuration of a web server, container orchestration, database connectivity, and API interaction.

The system is deployed on Ubuntu Linux and uses Docker Compose to manage multiple services.

⸻

Architecture

The application architecture consists of three main components:

Client (Browser)
↓
Nginx (Reverse Proxy)
↓
PHP API
↓
MariaDB Database

Nginx acts as a reverse proxy and web server that forwards requests to the backend API.
The backend API processes requests and interacts with the MariaDB database.

⸻

Technologies Used

The project uses the following technologies:
 • Ubuntu Linux
 • Docker
 • Docker Compose
 • Nginx
 • PHP
 • MariaDB
 • REST API

⸻

Project Structure

blushe/

api/ — backend API written in PHP
db_init/ — database initialization scripts
nginx/ — Nginx configuration files
site/ — frontend pages

docker-compose.yml — container configuration
run.sh — script for starting the project
backup.sh — script for database backup
dump.sql — database dump
.env — environment configuration

⸻

Features

The project demonstrates the following infrastructure functionality:
 • deployment of a Linux web server
 • configuration of Nginx
 • containerized services using Docker
 • database deployment using MariaDB
 • REST API implementation
 • reverse proxy configuration
 • database initialization scripts
 • database backup

⸻

Running the Project

Clone the repository
cd blushe

Start containers:

docker compose up -d

Check running containers:

docker ps

⸻

API Endpoints

Health check:

/api/health

Get all products:

/api/products

Search products:

/api/products?q=keyword

⸻

Database

The MariaDB database runs inside a Docker container.

Initial database tables are created automatically using the script located in:

db_init/init.sql

⸻

Backup

Database backups can be created using the backup script:

./backup.sh

Backup files are saved in the project directory as SQL files.

⸻

Purpose of the Project

The purpose of this project is to demonstrate practical skills in:
 • deployment of server infrastructure
 • containerization using Docker
 • configuration of web servers
 • interaction between applications and databases
