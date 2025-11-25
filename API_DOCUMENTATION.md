# ISP Management System - Complete API Documentation

## Base URL
```
http://localhost:8000/api
```

## Authentication
All protected endpoints require Bearer token authentication.

```http
Authorization: Bearer {token}
```

---

## 📌 Authentication Endpoints

### 1. Login
```http
POST /auth/login
```

**Request Body:**
```json
{
  "email": "admin@isp.local",
  "password": "admin123"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Super Admin",
      "email": "admin@isp.local",
      "role": "superadmin",
      "role_display": "Super Administrator"
    },
    "token": "1|abc123..."
  }
}
```

### 2. Logout
```http
POST /auth/logout
```

### 3. Get Current User
```http
GET /auth/me
```

### 4. Update Profile
```http
PUT /auth/profile
```

**Request Body:**
```json
{
  "name": "John Doe",
  "phone": "08123456789"
}
```

### 5. Change Password
```http
PUT /auth/password
```

**Request Body:**
```json
{
  "current_password": "old_password",
  "new_password": "new_password",
  "new_password_confirmation": "new_password"
}
```

---

## 👥 Customer Endpoints

### List Customers
```http
GET /customers?search=john&status=active&page=1&per_page=15
```

### Create Customer
```http
POST /customers
```

**Request Body:**
```json
{
  "name": "John Doe",
  "address": "Jl. Contoh No. 123, Jakarta",
  "phone": "08123456789",
  "email": "john@example.com",
  "id_number": "1234567890123456",
  "type": "residential",
  "notes": "New customer"
}
```

### Get Customer
```http
GET /customers/{id}
```

### Update Customer
```http
PUT /customers/{id}
```

### Delete Customer
```http
DELETE /customers/{id}
```

### Suspend Customer
```http
POST /customers/{id}/suspend
```

### Activate Customer
```http
POST /customers/{id}/activate
```

---

## 📦 Service (Package) Endpoints

### List Services
```http
GET /services
```

### Create Service
```http
POST /services
```

**Request Body:**
```json
{
  "name": "Home 20 Mbps",
  "code": "HOME-20",
  "speed_up": 20,
  "speed_down": 20,
  "price": 250000,
  "billing_cycle": "monthly",
  "description": "Paket internet rumah 20 Mbps"
}
```

### Get Service
```http
GET /services/{id}
```

### Update Service
```http
PUT /services/{id}
```

### Delete Service
```http
DELETE /services/{id}
```

---

## 🔌 Subscription Endpoints

### List Subscriptions
```http
GET /subscriptions?customer_id=1&status=active&search=username
```

### Create Subscription
```http
POST /subscriptions
```

**Request Body:**
```json
{
  "customer_id": 1,
  "service_id": 2,
  "router_id": 1,
  "olt_id": 1,
  "onu_id": 1,
  "vlan_id": 100,
  "ip_static": "192.168.1.100",
  "ip_type": "static",
  "start_date": "2025-11-25",
  "notes": "Installation completed"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Subscription created successfully",
  "data": {
    "id": 1,
    "customer_id": 1,
    "service_id": 2,
    "username_pppoe": "johndoe123",
    "password_pppoe": "aB3dE9fG2hJ5",
    "status": "pending",
    "radiusUser": {
      "username": "johndoe123",
      "status": "inactive"
    }
  }
}
```

### Get Subscription
```http
GET /subscriptions/{id}
```

### Update Subscription
```http
PUT /subscriptions/{id}
```

### Provision Subscription
```http
POST /subscriptions/{id}/provision
```
*Triggers network provisioning jobs*

### Suspend Subscription
```http
POST /subscriptions/{id}/suspend
```

### Unsuspend Subscription
```http
POST /subscriptions/{id}/unsuspend
```

### Reset PPPoE Session
```http
POST /subscriptions/{id}/reset-session
```

### Terminate Subscription
```http
POST /subscriptions/{id}/terminate
```

---

## 💰 Invoice Endpoints

### List Invoices
```http
GET /invoices?customer_id=1&status=unpaid&start_date=2025-11-01&end_date=2025-11-30
```

### Create Invoice (Manual)
```http
POST /invoices
```

**Request Body:**
```json
{
  "subscription_id": 1,
  "period_start": "2025-11-01",
  "period_end": "2025-11-30",
  "due_date": "2025-12-07",
  "notes": "November 2025"
}
```

### Generate Invoices (Bulk)
```http
POST /invoices/generate
```

**Request Body:**
```json
{
  "subscription_ids": [1, 2, 3],
  "period_start": "2025-11-01",
  "period_end": "2025-11-30",
  "due_days": 7
}
```

### Get Invoice
```http
GET /invoices/{id}
```

### Update Invoice
```http
PUT /invoices/{id}
```

### Cancel Invoice
```http
POST /invoices/{id}/cancel
```

### Download Invoice PDF
```http
GET /invoices/{id}/pdf
```

---

## 💳 Payment Endpoints

### List Payments
```http
GET /payments?customer_id=1&invoice_id=1&method=cash&start_date=2025-11-01
```

### Record Payment
```http
POST /payments
```

**Request Body:**
```json
{
  "invoice_id": 1,
  "amount": 250000,
  "method": "bank_transfer",
  "reference": "TRX123456",
  "paid_at": "2025-11-25 14:30:00",
  "notes": "Payment via BCA"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment recorded successfully",
  "data": {
    "id": 1,
    "invoice_id": 1,
    "customer_id": 1,
    "amount": 250000,
    "method": "bank_transfer",
    "reference": "TRX123456",
    "paid_at": "2025-11-25 14:30:00"
  }
}
```

### Get Payment
```http
GET /payments/{id}
```

### Delete Payment
```http
DELETE /payments/{id}
```

---

## 🖥️ Device Endpoints

### Routers

#### List Routers
```http
GET /devices/routers?status=active
```

#### Create Router
```http
POST /devices/routers
```

**Request Body:**
```json
{
  "name": "MikroTik CCR1009",
  "ip_address": "192.168.1.1",
  "type": "mikrotik",
  "api_port": 8728,
  "api_user": "admin",
  "api_password": "secure_password",
  "radius_secret": "radius_secret",
  "location": "Data Center 1",
  "notes": "Primary router"
}
```

#### Get Router
```http
GET /devices/routers/{id}
```

#### Update Router
```http
PUT /devices/routers/{id}
```

#### Delete Router
```http
DELETE /devices/routers/{id}
```

### OLT

#### List OLTs
```http
GET /devices/olt?status=active
```

#### Create OLT
```http
POST /devices/olt
```

**Request Body:**
```json
{
  "name": "Huawei MA5800-X7",
  "ip_address": "192.168.2.1",
  "vendor": "huawei",
  "mgmt_type": "ssh",
  "mgmt_port": 22,
  "mgmt_user": "admin",
  "mgmt_password": "secure_password",
  "snmp_community": "public",
  "location": "POP Site A"
}
```

#### Get OLT
```http
GET /devices/olt/{id}
```

#### Update OLT
```http
PUT /devices/olt/{id}
```

#### Delete OLT
```http
DELETE /devices/olt/{id}
```

### ONU

#### List ONUs
```http
GET /devices/onu?olt_id=1&status=online&customer_id=1
```

#### Get ONU
```http
GET /devices/onu/{id}
```

---

## 📊 Response Formats

### Success Response
```json
{
  "success": true,
  "message": "Operation successful",
  "data": { ... }
}
```

### Error Response
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

### Paginated Response
```json
{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [ ... ],
    "first_page_url": "...",
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 75
  }
}
```

---

## 🔄 Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 401 | Unauthorized - Invalid token |
| 403 | Forbidden - No permission |
| 404 | Not Found - Resource not found |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Internal Server Error |

---

## 🎯 Common Query Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `page` | integer | Page number (default: 1) |
| `per_page` | integer | Items per page (default: 15) |
| `search` | string | Search keyword |
| `status` | string | Filter by status |
| `customer_id` | integer | Filter by customer |
| `start_date` | date | Start date filter (Y-m-d) |
| `end_date` | date | End date filter (Y-m-d) |

---

## 🔐 Field Validation Rules

### Customer
- `name`: required, string, max:255
- `address`: required, string
- `phone`: required, string, max:20
- `email`: nullable, email
- `type`: required, in:residential,business

### Subscription
- `customer_id`: required, exists:customers
- `service_id`: required, exists:services
- `router_id`: nullable, exists:devices_routers
- `ip_type`: required, in:dhcp,static
- `start_date`: required, date

### Invoice
- `subscription_id`: required, exists:subscriptions
- `period_start`: required, date
- `period_end`: required, date, after:period_start
- `due_date`: required, date

### Payment
- `invoice_id`: required, exists:invoices
- `amount`: required, numeric, min:0
- `method`: required, in:cash,bank_transfer,credit_card,e_wallet,gateway
- `paid_at`: required, date

---

## 📱 Automated Features

### Observer Actions

**When Subscription is Created:**
- ✅ Automatically creates RADIUS user
- ✅ Sets initial status to 'pending'

**When Subscription Status Changes:**
- ✅ `pending` → `active`: Dispatches ProvisionSubscriptionJob
- ✅ `active` → `suspended`: Dispatches SuspendSubscriptionJob
- ✅ `suspended` → `active`: Dispatches UnsuspendSubscriptionJob

**When Invoice is Created:**
- ✅ Automatically creates accounting journal entry
- ✅ Debit: Accounts Receivable
- ✅ Credit: Service Revenue, Tax Payable

**When Payment is Recorded:**
- ✅ Updates invoice status if fully paid
- ✅ Creates accounting journal entry
- ✅ Auto-unsuspends subscription if applicable
- ✅ Dispatches UnsuspendSubscriptionJob

### Scheduled Jobs

**Daily at 01:00 AM:**
- Checks for overdue invoices
- Auto-suspends subscriptions with overdue invoices

**Monthly on 1st at 00:00 AM:**
- Generates invoices for all active subscriptions

---

## 🧪 Testing Examples

### Complete Workflow Test

#### 1. Login
```bash
TOKEN=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"admin@isp.local","password":"admin123"}' \
  | jq -r '.data.token')
```

#### 2. Create Customer
```bash
curl -X POST http://localhost:8000/api/customers \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Test Customer",
    "address": "Jl. Test 123",
    "phone": "08123456789",
    "type": "residential"
  }'
```

#### 3. Create Subscription
```bash
curl -X POST http://localhost:8000/api/subscriptions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "customer_id": 1,
    "service_id": 1,
    "router_id": 1,
    "ip_type": "dhcp",
    "start_date": "2025-11-25"
  }'
```

#### 4. Provision Subscription
```bash
curl -X POST http://localhost:8000/api/subscriptions/1/provision \
  -H "Authorization: Bearer $TOKEN"
```

#### 5. Generate Invoice
```bash
curl -X POST http://localhost:8000/api/invoices \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "subscription_id": 1,
    "period_start": "2025-11-01",
    "period_end": "2025-11-30",
    "due_date": "2025-12-07"
  }'
```

#### 6. Record Payment
```bash
curl -X POST http://localhost:8000/api/payments \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "invoice_id": 1,
    "amount": 150000,
    "method": "cash",
    "paid_at": "2025-11-25 10:00:00"
  }'
```

---

## 📞 Support & Contact

For API questions or issues:
- Check logs: `storage/logs/laravel.log`
- Queue logs: Monitor queue worker output
- Network worker logs: `network-worker/logs/`

---

**Last Updated:** 2025-11-25
**Version:** 1.0.0
**Status:** Phase 1 Complete ✅
