# Nexora Monitoring
Nexora Monitoring is a web app for monitoring IT equipments. This is a supervised project I've made in my B1 inside the College de Paris (presently known as IUT & SN)

## Features
- [x] Authentification system
- [x] Users management
- [x] Devices management
- [x] Monitoring system
- [x] Smart Alerts system
- [x] Reporting system
- [x] Audit system
- [x] Incidents system
- [x] Role system (Super admin, admin, technician, standard user, auditor)
- [x] Real-time dashboard
- [x] Notification system
- [x] Automatic report generation

## Getting Started
### Requirements
* MySQL 8.x
* Php 8.x
* Apache

### Run locally
Nexora Monitoring is a php project. Which means that you have to launch a php server in order to make the project work. You can either use Apache or others like you usually do but, the simplest way to build it is to download xampp and clone the project in the ``htdocs/`` folder.

Once that's done, just copy and paste the content of the ``db.sql`` inside your MySQL console and modify the config files thanks to a .env file and that's it !

## License
Mabble is licensed under the [MIT License](LICENSE).
