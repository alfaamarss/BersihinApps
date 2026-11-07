# Panduan Integrasi Notifikasi Mobile

Dokumentasi ini menjelaskan cara mengintegrasikan sistem notifikasi dari Laravel backend ke aplikasi Flutter mobile.

## Daftar Isi

1. [Overview](#overview)
2. [API Endpoints](#api-endpoints)
3. [Response Format](#response-format)
4. [Implementasi di Flutter](#implementasi-di-flutter)
5. [Contoh Penggunaan](#contoh-penggunaan)
6. [Jenis-Jenis Notifikasi](#jenis-jenis-notifikasi)

---

## Overview

Sistem notifikasi ini berbasis database (bukan realtime push notification). Mobile app perlu melakukan polling atau fetch manual untuk mendapatkan notifikasi terbaru.

### Karakteristik:
- **Non-realtime**: Notifikasi disimpan di database, mobile fetch via API
- **Database-based**: Menggunakan Laravel's built-in notification system
- **Role-based**: Notifikasi berbeda untuk User (Warga), Admin, dan Collector

---

## API Endpoints

Base URL: `{API_URL}/api/mobile`

### 1. Get All Notifications

```
GET /notifications
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Query Parameters:**
| Parameter | Type | Required | Description |
|-----------|------|----------|-------------|
| `per_page` | integer | No | Jumlah per halaman (default: 20) |
| `page` | integer | No | Nomor halaman |

**Response Success (200):**
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "type": "App\\Notifications\\User\\BillReminderNotification",
        "data": {
          "title": "Pengingat Tagihan",
          "message": "Tagihan Anda sebesar Rp 50.000 akan jatuh tempo dalam 3 hari",
          "type": "billing_reminder",
          "icon": "receipt",
          "action_url": "/invoices/123",
          "invoice_id": 123,
          "amount": 50000,
          "due_date": "2025-12-11"
        },
        "read_at": null,
        "created_at": "2025-12-08T10:30:00.000000Z"
      }
    ],
    "first_page_url": "...",
    "last_page_url": "...",
    "next_page_url": "...",
    "prev_page_url": null,
    "per_page": 20,
    "total": 45
  },
  "unread_count": 12
}
```

### 2. Get Unread Notifications Only

```
GET /notifications/unread
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "type": "App\\Notifications\\User\\ComplaintStatusNotification",
      "data": {
        "title": "Status Pengaduan Diperbarui",
        "message": "Pengaduan Anda sedang diproses oleh collector",
        "type": "complaint_status",
        "icon": "assignment",
        "complaint_id": 5,
        "status": "in_progress"
      },
      "read_at": null,
      "created_at": "2025-12-08T09:15:00.000000Z"
    }
  ],
  "count": 12
}
```

### 3. Get Unread Count

```
GET /notifications/unread-count
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "count": 12
}
```

### 4. Mark Notification as Read

```
POST /notifications/{id}/read
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Notification marked as read"
}
```

### 5. Mark All Notifications as Read

```
POST /notifications/read-all
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "All notifications marked as read"
}
```

### 6. Delete a Notification

```
DELETE /notifications/{id}
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "Notification deleted"
}
```

### 7. Clear All Notifications

```
DELETE /notifications/clear-all
```

**Headers:**
```
Authorization: Bearer {token}
Accept: application/json
```

**Response Success (200):**
```json
{
  "success": true,
  "message": "All notifications cleared"
}
```

---

## Response Format

### Notification Object Structure

```json
{
  "id": "uuid-string",
  "type": "App\\Notifications\\User\\SomeNotification",
  "data": {
    "title": "Judul Notifikasi",
    "message": "Pesan detail notifikasi",
    "type": "notification_type_key",
    "icon": "icon_name",
    "action_url": "/optional/path",
    // ... additional data specific to notification type
  },
  "read_at": "2025-12-08T10:30:00.000000Z" | null,
  "created_at": "2025-12-08T10:30:00.000000Z"
}
```

### Notification Types (data.type)

| Type Key | Description | Additional Data |
|----------|-------------|-----------------|
| `service_account_created` | Akun layanan dibuat | `service_account_id` |
| `new_article` | Artikel baru dipublikasikan | `article_id`, `article_title` |
| `schedule_reminder` | Pengingat jadwal pengambilan | `schedule_id`, `pickup_date`, `pickup_time` |
| `billing_reminder` | Pengingat tagihan H-3 | `invoice_id`, `amount`, `due_date` |
| `billing_due_today` | Tagihan jatuh tempo hari ini | `invoice_id`, `amount` |
| `billing_overdue` | Tagihan terlambat | `invoice_id`, `amount`, `days_overdue` |
| `payment_confirmed` | Pembayaran dikonfirmasi | `invoice_id`, `amount` |
| `complaint_status` | Status pengaduan diperbarui | `complaint_id`, `status` |
| `pickup_request_status` | Status permintaan penjemputan | `pickup_request_id`, `status` |
| `new_pickup_task` | (Collector) Tugas penjemputan baru | `pickup_request_id`, `resident_name`, `address` |

---

## Implementasi di Flutter

### 1. Model Notification

Buat file `lib/models/app_notification.dart`:

```dart
class AppNotification {
  final String id;
  final String type;
  final NotificationData data;
  final DateTime? readAt;
  final DateTime createdAt;

  AppNotification({
    required this.id,
    required this.type,
    required this.data,
    this.readAt,
    required this.createdAt,
  });

  bool get isRead => readAt != null;

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'],
      type: json['type'],
      data: NotificationData.fromJson(json['data']),
      readAt: json['read_at'] != null ? DateTime.parse(json['read_at']) : null,
      createdAt: DateTime.parse(json['created_at']),
    );
  }
}

class NotificationData {
  final String title;
  final String message;
  final String type;
  final String? icon;
  final String? actionUrl;
  final Map<String, dynamic> extra;

  NotificationData({
    required this.title,
    required this.message,
    required this.type,
    this.icon,
    this.actionUrl,
    this.extra = const {},
  });

  factory NotificationData.fromJson(Map<String, dynamic> json) {
    return NotificationData(
      title: json['title'] ?? '',
      message: json['message'] ?? '',
      type: json['type'] ?? '',
      icon: json['icon'],
      actionUrl: json['action_url'],
      extra: Map<String, dynamic>.from(json)
        ..remove('title')
        ..remove('message')
        ..remove('type')
        ..remove('icon')
        ..remove('action_url'),
    );
  }
}
```

### 2. Notification Service

Buat file `lib/services/notification_service.dart`:

```dart
import 'package:http/http.dart' as http;
import 'dart:convert';
import '../models/app_notification.dart';
import '../config/api_config.dart';

class NotificationService {
  final String baseUrl;
  final String Function() getToken;

  NotificationService({
    required this.baseUrl,
    required this.getToken,
  });

  Map<String, String> get _headers => {
    'Authorization': 'Bearer ${getToken()}',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
  };

  /// Get all notifications with pagination
  Future<NotificationResponse> getNotifications({int page = 1, int perPage = 20}) async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/mobile/notifications?page=$page&per_page=$perPage'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return NotificationResponse.fromJson(json);
    } else {
      throw Exception('Failed to load notifications');
    }
  }

  /// Get unread notifications only
  Future<List<AppNotification>> getUnreadNotifications() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/mobile/notifications/unread'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      final List<dynamic> data = json['data'];
      return data.map((item) => AppNotification.fromJson(item)).toList();
    } else {
      throw Exception('Failed to load unread notifications');
    }
  }

  /// Get unread count
  Future<int> getUnreadCount() async {
    final response = await http.get(
      Uri.parse('$baseUrl/api/mobile/notifications/unread-count'),
      headers: _headers,
    );

    if (response.statusCode == 200) {
      final json = jsonDecode(response.body);
      return json['count'] ?? 0;
    } else {
      throw Exception('Failed to get unread count');
    }
  }

  /// Mark single notification as read
  Future<bool> markAsRead(String notificationId) async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/mobile/notifications/$notificationId/read'),
      headers: _headers,
    );

    return response.statusCode == 200;
  }

  /// Mark all notifications as read
  Future<bool> markAllAsRead() async {
    final response = await http.post(
      Uri.parse('$baseUrl/api/mobile/notifications/read-all'),
      headers: _headers,
    );

    return response.statusCode == 200;
  }

  /// Delete a notification
  Future<bool> deleteNotification(String notificationId) async {
    final response = await http.delete(
      Uri.parse('$baseUrl/api/mobile/notifications/$notificationId'),
      headers: _headers,
    );

    return response.statusCode == 200;
  }

  /// Clear all notifications
  Future<bool> clearAllNotifications() async {
    final response = await http.delete(
      Uri.parse('$baseUrl/api/mobile/notifications/clear-all'),
      headers: _headers,
    );

    return response.statusCode == 200;
  }
}

class NotificationResponse {
  final List<AppNotification> notifications;
  final int currentPage;
  final int lastPage;
  final int total;
  final int unreadCount;

  NotificationResponse({
    required this.notifications,
    required this.currentPage,
    required this.lastPage,
    required this.total,
    required this.unreadCount,
  });

  factory NotificationResponse.fromJson(Map<String, dynamic> json) {
    final data = json['data'];
    final List<dynamic> items = data['data'];
    
    return NotificationResponse(
      notifications: items.map((item) => AppNotification.fromJson(item)).toList(),
      currentPage: data['current_page'] ?? 1,
      lastPage: data['last_page'] ?? 1,
      total: data['total'] ?? 0,
      unreadCount: json['unread_count'] ?? 0,
    );
  }
}
```

### 3. Notification Provider (State Management)

Buat file `lib/providers/notification_provider.dart`:

```dart
import 'package:flutter/foundation.dart';
import '../models/app_notification.dart';
import '../services/notification_service.dart';

class NotificationProvider extends ChangeNotifier {
  final NotificationService _service;
  
  List<AppNotification> _notifications = [];
  int _unreadCount = 0;
  bool _isLoading = false;
  String? _error;
  int _currentPage = 1;
  int _lastPage = 1;

  NotificationProvider(this._service);

  List<AppNotification> get notifications => _notifications;
  int get unreadCount => _unreadCount;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasMore => _currentPage < _lastPage;

  /// Initial load of notifications
  Future<void> loadNotifications({bool refresh = false}) async {
    if (refresh) {
      _currentPage = 1;
      _notifications = [];
    }

    _isLoading = true;
    _error = null;
    notifyListeners();

    try {
      final response = await _service.getNotifications(page: _currentPage);
      
      if (refresh) {
        _notifications = response.notifications;
      } else {
        _notifications.addAll(response.notifications);
      }
      
      _unreadCount = response.unreadCount;
      _lastPage = response.lastPage;
      _error = null;
    } catch (e) {
      _error = e.toString();
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// Load more notifications (pagination)
  Future<void> loadMore() async {
    if (_isLoading || !hasMore) return;
    
    _currentPage++;
    await loadNotifications();
  }

  /// Refresh notifications
  Future<void> refresh() async {
    await loadNotifications(refresh: true);
  }

  /// Fetch unread count only (for badge)
  Future<void> fetchUnreadCount() async {
    try {
      _unreadCount = await _service.getUnreadCount();
      notifyListeners();
    } catch (e) {
      // Silent fail for badge count
    }
  }

  /// Mark notification as read
  Future<void> markAsRead(String notificationId) async {
    final success = await _service.markAsRead(notificationId);
    
    if (success) {
      final index = _notifications.indexWhere((n) => n.id == notificationId);
      if (index != -1 && _notifications[index].readAt == null) {
        _unreadCount = (_unreadCount - 1).clamp(0, _unreadCount);
        // Update local state
        _notifications[index] = AppNotification(
          id: _notifications[index].id,
          type: _notifications[index].type,
          data: _notifications[index].data,
          readAt: DateTime.now(),
          createdAt: _notifications[index].createdAt,
        );
        notifyListeners();
      }
    }
  }

  /// Mark all as read
  Future<void> markAllAsRead() async {
    final success = await _service.markAllAsRead();
    
    if (success) {
      _unreadCount = 0;
      _notifications = _notifications.map((n) => AppNotification(
        id: n.id,
        type: n.type,
        data: n.data,
        readAt: n.readAt ?? DateTime.now(),
        createdAt: n.createdAt,
      )).toList();
      notifyListeners();
    }
  }

  /// Delete notification
  Future<void> deleteNotification(String notificationId) async {
    final success = await _service.deleteNotification(notificationId);
    
    if (success) {
      final notification = _notifications.firstWhere((n) => n.id == notificationId);
      if (!notification.isRead) {
        _unreadCount = (_unreadCount - 1).clamp(0, _unreadCount);
      }
      _notifications.removeWhere((n) => n.id == notificationId);
      notifyListeners();
    }
  }

  /// Clear all notifications
  Future<void> clearAll() async {
    final success = await _service.clearAllNotifications();
    
    if (success) {
      _notifications = [];
      _unreadCount = 0;
      notifyListeners();
    }
  }
}
```

### 4. Notification List Screen

Buat file `lib/screens/notifications/notification_list_screen.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/notification_provider.dart';
import '../../models/app_notification.dart';
import 'package:timeago/timeago.dart' as timeago;

class NotificationListScreen extends StatefulWidget {
  const NotificationListScreen({super.key});

  @override
  State<NotificationListScreen> createState() => _NotificationListScreenState();
}

class _NotificationListScreenState extends State<NotificationListScreen> {
  final ScrollController _scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    _scrollController.addListener(_onScroll);
    
    // Load notifications on init
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().loadNotifications(refresh: true);
    });
  }

  void _onScroll() {
    if (_scrollController.position.pixels >= 
        _scrollController.position.maxScrollExtent - 200) {
      context.read<NotificationProvider>().loadMore();
    }
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifikasi'),
        actions: [
          PopupMenuButton<String>(
            onSelected: (value) {
              final provider = context.read<NotificationProvider>();
              if (value == 'read_all') {
                provider.markAllAsRead();
              } else if (value == 'clear_all') {
                _showClearAllDialog(context, provider);
              }
            },
            itemBuilder: (context) => [
              const PopupMenuItem(
                value: 'read_all',
                child: Text('Tandai semua dibaca'),
              ),
              const PopupMenuItem(
                value: 'clear_all',
                child: Text('Hapus semua'),
              ),
            ],
          ),
        ],
      ),
      body: Consumer<NotificationProvider>(
        builder: (context, provider, child) {
          if (provider.isLoading && provider.notifications.isEmpty) {
            return const Center(child: CircularProgressIndicator());
          }

          if (provider.error != null && provider.notifications.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('Error: ${provider.error}'),
                  ElevatedButton(
                    onPressed: () => provider.refresh(),
                    child: const Text('Coba Lagi'),
                  ),
                ],
              ),
            );
          }

          if (provider.notifications.isEmpty) {
            return const Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Icon(Icons.notifications_off, size: 64, color: Colors.grey),
                  SizedBox(height: 16),
                  Text('Tidak ada notifikasi'),
                ],
              ),
            );
          }

          return RefreshIndicator(
            onRefresh: () => provider.refresh(),
            child: ListView.builder(
              controller: _scrollController,
              itemCount: provider.notifications.length + (provider.hasMore ? 1 : 0),
              itemBuilder: (context, index) {
                if (index == provider.notifications.length) {
                  return const Center(
                    child: Padding(
                      padding: EdgeInsets.all(16),
                      child: CircularProgressIndicator(),
                    ),
                  );
                }

                final notification = provider.notifications[index];
                return NotificationTile(
                  notification: notification,
                  onTap: () => _handleNotificationTap(context, notification),
                  onDismiss: () => provider.deleteNotification(notification.id),
                );
              },
            ),
          );
        },
      ),
    );
  }

  void _handleNotificationTap(BuildContext context, AppNotification notification) {
    // Mark as read
    context.read<NotificationProvider>().markAsRead(notification.id);

    // Navigate based on notification type
    final type = notification.data.type;
    final extra = notification.data.extra;

    switch (type) {
      case 'complaint_status':
        final complaintId = extra['complaint_id'];
        if (complaintId != null) {
          Navigator.pushNamed(context, '/complaints/$complaintId');
        }
        break;
      case 'pickup_request_status':
        final pickupId = extra['pickup_request_id'];
        if (pickupId != null) {
          Navigator.pushNamed(context, '/pickup-requests/$pickupId');
        }
        break;
      case 'billing_reminder':
      case 'billing_due_today':
      case 'billing_overdue':
      case 'payment_confirmed':
        final invoiceId = extra['invoice_id'];
        if (invoiceId != null) {
          Navigator.pushNamed(context, '/invoices/$invoiceId');
        }
        break;
      case 'new_article':
        final articleId = extra['article_id'];
        if (articleId != null) {
          Navigator.pushNamed(context, '/articles/$articleId');
        }
        break;
      case 'schedule_reminder':
        Navigator.pushNamed(context, '/schedules');
        break;
      default:
        // Show detail in dialog for unknown types
        showDialog(
          context: context,
          builder: (context) => AlertDialog(
            title: Text(notification.data.title),
            content: Text(notification.data.message),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(context),
                child: const Text('OK'),
              ),
            ],
          ),
        );
    }
  }

  void _showClearAllDialog(BuildContext context, NotificationProvider provider) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Hapus Semua Notifikasi'),
        content: const Text('Apakah Anda yakin ingin menghapus semua notifikasi?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Batal'),
          ),
          TextButton(
            onPressed: () {
              provider.clearAll();
              Navigator.pop(context);
            },
            child: const Text('Hapus'),
          ),
        ],
      ),
    );
  }
}

class NotificationTile extends StatelessWidget {
  final AppNotification notification;
  final VoidCallback onTap;
  final VoidCallback onDismiss;

  const NotificationTile({
    super.key,
    required this.notification,
    required this.onTap,
    required this.onDismiss,
  });

  @override
  Widget build(BuildContext context) {
    return Dismissible(
      key: Key(notification.id),
      direction: DismissDirection.endToStart,
      onDismissed: (_) => onDismiss(),
      background: Container(
        color: Colors.red,
        alignment: Alignment.centerRight,
        padding: const EdgeInsets.only(right: 16),
        child: const Icon(Icons.delete, color: Colors.white),
      ),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: notification.isRead 
              ? Colors.grey.shade200 
              : Theme.of(context).primaryColor.withOpacity(0.1),
          child: Icon(
            _getIcon(notification.data.icon),
            color: notification.isRead 
                ? Colors.grey 
                : Theme.of(context).primaryColor,
          ),
        ),
        title: Text(
          notification.data.title,
          style: TextStyle(
            fontWeight: notification.isRead ? FontWeight.normal : FontWeight.bold,
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              notification.data.message,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
            const SizedBox(height: 4),
            Text(
              timeago.format(notification.createdAt, locale: 'id'),
              style: TextStyle(
                fontSize: 12,
                color: Colors.grey.shade600,
              ),
            ),
          ],
        ),
        isThreeLine: true,
        tileColor: notification.isRead ? null : Colors.blue.withOpacity(0.05),
        onTap: onTap,
      ),
    );
  }

  IconData _getIcon(String? iconName) {
    switch (iconName) {
      case 'receipt':
        return Icons.receipt;
      case 'assignment':
        return Icons.assignment;
      case 'local_shipping':
        return Icons.local_shipping;
      case 'article':
        return Icons.article;
      case 'schedule':
        return Icons.schedule;
      case 'payment':
        return Icons.payment;
      case 'check_circle':
        return Icons.check_circle;
      case 'warning':
        return Icons.warning;
      case 'error':
        return Icons.error;
      default:
        return Icons.notifications;
    }
  }
}
```

### 5. Notification Badge Widget

Buat file `lib/widgets/notification_badge.dart`:

```dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/notification_provider.dart';

class NotificationBadge extends StatelessWidget {
  final Widget child;
  
  const NotificationBadge({
    super.key,
    required this.child,
  });

  @override
  Widget build(BuildContext context) {
    return Consumer<NotificationProvider>(
      builder: (context, provider, _) {
        return Stack(
          clipBehavior: Clip.none,
          children: [
            child,
            if (provider.unreadCount > 0)
              Positioned(
                right: -4,
                top: -4,
                child: Container(
                  padding: const EdgeInsets.all(4),
                  decoration: const BoxDecoration(
                    color: Colors.red,
                    shape: BoxShape.circle,
                  ),
                  constraints: const BoxConstraints(
                    minWidth: 18,
                    minHeight: 18,
                  ),
                  child: Text(
                    provider.unreadCount > 99 ? '99+' : '${provider.unreadCount}',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                    textAlign: TextAlign.center,
                  ),
                ),
              ),
          ],
        );
      },
    );
  }
}
```

### 6. Setup di Main App

Di `lib/main.dart`:

```dart
import 'package:provider/provider.dart';
import 'services/notification_service.dart';
import 'providers/notification_provider.dart';
import 'services/auth_service.dart';

void main() {
  runApp(
    MultiProvider(
      providers: [
        // ... other providers
        
        Provider<NotificationService>(
          create: (context) => NotificationService(
            baseUrl: 'https://your-api-url.com',
            getToken: () => context.read<AuthService>().token ?? '',
          ),
        ),
        
        ChangeNotifierProxyProvider<NotificationService, NotificationProvider>(
          create: (context) => NotificationProvider(
            context.read<NotificationService>(),
          ),
          update: (context, service, previous) => 
            previous ?? NotificationProvider(service),
        ),
      ],
      child: const MyApp(),
    ),
  );
}
```

### 7. Penggunaan di AppBar

```dart
AppBar(
  title: const Text('Home'),
  actions: [
    NotificationBadge(
      child: IconButton(
        icon: const Icon(Icons.notifications),
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => const NotificationListScreen(),
            ),
          );
        },
      ),
    ),
  ],
)
```

### 8. Periodic Refresh (Optional)

Untuk auto-refresh badge count:

```dart
class _HomeScreenState extends State<HomeScreen> {
  Timer? _notificationTimer;

  @override
  void initState() {
    super.initState();
    
    // Fetch unread count initially
    context.read<NotificationProvider>().fetchUnreadCount();
    
    // Refresh every 30 seconds
    _notificationTimer = Timer.periodic(
      const Duration(seconds: 30),
      (_) => context.read<NotificationProvider>().fetchUnreadCount(),
    );
  }

  @override
  void dispose() {
    _notificationTimer?.cancel();
    super.dispose();
  }
  
  // ...
}
```

---

## Jenis-Jenis Notifikasi

### Untuk User (Warga)

| Notification | Trigger | Data Fields |
|--------------|---------|-------------|
| `ServiceAccountCreatedNotification` | Saat akun layanan dibuat | `service_account_id` |
| `NewArticleNotification` | Saat artikel baru dipublish | `article_id`, `article_title` |
| `ScheduleReminderNotification` | H-1 jadwal pengambilan (scheduler 06:00) | `schedule_id`, `pickup_date`, `pickup_time` |
| `BillReminderNotification` | H-3 jatuh tempo tagihan (scheduler 08:00) | `invoice_id`, `amount`, `due_date` |
| `BillDueTodayNotification` | H-0 jatuh tempo tagihan (scheduler 08:00) | `invoice_id`, `amount` |
| `BillOverdueNotification` | Setelah jatuh tempo (scheduler 08:00) | `invoice_id`, `amount`, `days_overdue` |
| `PaymentConfirmedNotification` | Pembayaran dikonfirmasi admin | `invoice_id`, `amount` |
| `ComplaintStatusNotification` | Status pengaduan berubah | `complaint_id`, `status` |
| `PickupRequestStatusNotification` | Status permintaan penjemputan berubah | `pickup_request_id`, `status` |

### Untuk Admin

| Notification | Trigger | Data Fields |
|--------------|---------|-------------|
| `NewComplaintNotification` | Warga membuat pengaduan baru | `complaint_id`, `resident_name`, `subject` |
| `NewPickupRequestNotification` | Warga request penjemputan | `pickup_request_id`, `resident_name`, `address` |
| `CollectorSubmittedProofNotification` | Collector upload bukti | `pickup_id`, `collector_name` |
| `NewServiceAccountCreatedNotification` | Warga buat akun layanan baru | `service_account_id`, `user_name`, `rw`, `rt` |
| `NewRwNeedsCollectorNotification` | RW baru tanpa collector | `rw_id`, `rw_name`, `user_name` |
| `PickupCompletedNotification` | Penjemputan selesai | `pickup_id`, `collector_name`, `resident_name` |

### Untuk Collector

| Notification | Trigger | Data Fields |
|--------------|---------|-------------|
| `NewPickupTaskNotification` | Ditugaskan untuk penjemputan | `pickup_request_id`, `resident_name`, `address`, `requested_time` |
| `ComplaintAssignedNotification` | Ditugaskan untuk pengaduan | `complaint_id`, `resident_name`, `subject` |

---

## Testing

### Test via Terminal (Backend)

```bash
# Test create notification
php artisan tinker --execute='
$user = \App\Models\User::find(3);
$user->notify(new \App\Notifications\User\BillReminderNotification(
  (object)["id" => 1, "total_amount" => 50000, "due_date" => "2025-12-11"]
));
echo "Notification sent!";
'

# Check notifications
php artisan tinker --execute='
$user = \App\Models\User::find(3);
foreach($user->notifications as $n) {
  echo $n->data["title"] . "\n";
}
'
```

### Test via API (Postman/curl)

```bash
# Get notifications
curl -X GET "https://your-api.com/api/mobile/notifications" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Mark as read
curl -X POST "https://your-api.com/api/mobile/notifications/{id}/read" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"
```

---

## Troubleshooting

### 1. Notifications not appearing
- Pastikan user sudah login dan token valid
- Cek apakah notification sudah tersimpan di database (`notifications` table)
- Pastikan `notifiable_id` sesuai dengan user yang login

### 2. Unread count not updating
- Panggil `fetchUnreadCount()` setelah marking as read
- Pastikan `markAsRead()` mengembalikan success

### 3. Pagination not working
- Cek apakah `hasMore` condition benar
- Pastikan `_currentPage` di-increment setelah load

---

## Dependencies

Tambahkan di `pubspec.yaml`:

```yaml
dependencies:
  http: ^1.1.0
  provider: ^6.1.1
  timeago: ^3.6.1
```

Untuk timeago locale Indonesia:

```dart
import 'package:timeago/timeago.dart' as timeago;

void main() {
  timeago.setLocaleMessages('id', timeago.IdMessages());
  runApp(MyApp());
}
```
