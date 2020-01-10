# Skyline Notification Service
The notification service provides a notification center where you can post notifications from everywhere in your application.

The notifications are persistently stored and can be delivered on demand.

Common usage with ```skyline/launchd``` to deliver emails to clients if something did happen in the application that the client needs to know.

#### Installation
```bin
$composer require skyline/notification-service
```

This package does not provide mutability features, so its designed handle the notifications