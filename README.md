Gay City Health Project
Appointment Reservations
==================================================

Working with a local non-profit group to create a calendar appointment reservation system, this project accesses their existing Google Calendar appointments and displayed available times to end-users. It also assists administrators by automatically emailing reservation reminders or providing a list of upcoming phone reminder reservations.

The front-end display design was modified by the client to suit their site design.

Key files of interest, beyond a standard [Zend Framework v1](http://framework.zend.com/) + [Base Shell](https://bitbucket.org/zimzat/zend-framework-base-shell) setup:
```
┌── application
│   ├── configs
│   │   └── installation.example.ini
│   ├── models
│   │   └── Calendar.php
│   └── modules
│       └── default
│           └── controllers
│               └── ReservationController.php
└── scripts
    └── jobs
        └── EmailReservationReminders.php
```

There are a number of things that could, and should, be done differently if this project were to grow in size or scope. For example:
- The Calendar model could be split into two: an underlying API access object and a Public interface.
- Better encapsulation of configuration parameters from the global Config object.
