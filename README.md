# ISP Management System

Comprehensive ISP management solution integrating RADIUS AAA, network device management (MikroTik & OLT), billing, and accounting.

## 🚀 Tech Stack

- **Backend**: Laravel 12 (PHP 8.4)
- **Frontend**: Vue 3 + Vite + Tailwind CSS
- **Database**: PostgreSQL 15
- **Cache/Queue**: Redis 7
- **Network Worker**: Python 3.11+
- **RADIUS**: FreeRADIUS 3.x

## 📋 Features

### Phase 1 (MVP - Current)
- ✅ Authentication & RBAC
- ✅ Customer & Subscription Management
- ✅ RADIUS Integration (PPPoE Authentication)
- ✅ MikroTik Provisioning (PPPoE user, Queue)
- ✅ Billing & Invoice Generation
- ✅ Auto-suspend on Overdue

### Phase 2 (Planned)
- 🔄 OLT Integration (Huawei)
- 🔄 ONU Provisioning & Monitoring
- 🔄 Customer Portal
- 🔄 Network Monitoring Dashboard

### Phase 3 (Planned)
- 📊 Full Accounting Module (Double-entry)
- 📈 Financial Reports (Income Statement, Balance Sheet, Cash Flow)

### Phase 4 (Planned)
- ⚡ Performance Optimization
- 🔌 Payment Gateway Integration
- 📱 WhatsApp Notifications
- 🌐 Multi-POP Support

## 🛠️ Installation

### Prerequisites
- Docker & Docker Compose
- PHP 8.2+
- Composer
- Node.js 18+
- Python 3.11+

### Quick Start

1. **Clone and setup**
```bash
cd isp-management
```

2. **Start Docker services**
```bash
docker-compose up -d
```

3. **Backend Setup**
```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
php artisan migrate --seed
```

4. **Frontend Setup**
```bash
cd frontend
npm install
npm run dev
```

5. **Network Worker Setup**
```bash
cd network-worker
python -m venv venv
source venv/bin/activate  # Windows: venv\Scripts\activate
pip install -r requirements.txt
python worker.py
```

## 🌐 Access Points

- **Frontend**: http://localhost:5173
- **Backend API**: http://localhost:8000
- **PostgreSQL**: localhost:5432
- **Redis**: localhost:6379
- **RADIUS**: localhost:1812 (Auth), localhost:1813 (Accounting)

## 📁 Project Structure

```
isp-management/
├── backend/              # Laravel 12 API
│   ├── app/
│   │   ├── Models/
│   │   ├── Http/Controllers/
│   │   ├── Services/
│   │   └── Jobs/
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   └── routes/
├── frontend/             # Vue 3 + Vite
│   ├── src/
│   │   ├── components/
│   │   ├── views/
│   │   ├── router/
│   │   └── stores/
│   └── public/
├── network-worker/       # Python Network Automation
│   ├── workers/
│   │   ├── mikrotik.py
│   │   ├── olt.py
│   │   └── monitoring.py
│   └── requirements.txt
├── radius-config/        # FreeRADIUS Configuration
└── docker-compose.yml
```

## 🔑 Default Credentials

### Admin Dashboard
- Email: admin@isp.local
- Password: admin123

### Database
- User: isp_user
- Password: isp_password
- Database: isp_management

## 📖 API Documentation

API documentation available at: http://localhost:8000/api/documentation

### Key Endpoints

**Authentication**
- POST `/api/auth/login`
- POST `/api/auth/logout`
- GET `/api/auth/me`

**Customers**
- GET `/api/customers`
- POST `/api/customers`
- GET `/api/customers/{id}`
- PUT `/api/customers/{id}`

**Subscriptions**
- POST `/api/subscriptions`
- POST `/api/subscriptions/{id}/provision`
- POST `/api/subscriptions/{id}/suspend`
- POST `/api/subscriptions/{id}/unsuspend`

**Billing**
- GET `/api/invoices`
- POST `/api/invoices/generate`
- POST `/api/payments`

## 🧪 Testing

```bash
# Backend tests
cd backend
php artisan test

# Frontend tests
cd frontend
npm run test
```

## 🔒 Security

- All passwords encrypted (bcrypt)
- Device credentials encrypted (AES-256)
- JWT authentication for API
- HTTPS only in production
- Rate limiting enabled
- CORS configured

## 📝 Development Roadmap

See [SPEC.md](../.factory/specs/2025-11-25-isp-management-system-technical-specification.md) for detailed implementation plan.

## 🤝 Contributing

This is a private ISP management system. Contact the development team for contribution guidelines.

## 📄 License

Proprietary - All rights reserved

## 📞 Support

For support, contact: support@isp.local
# isp-mangement
