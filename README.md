# 小型电商商品管理系统

## 🛠 技术栈
- Frontend: Vue 3 + Element Plus + Vite
- Backend: Laravel 10.x
- Database: MySQL 8.0
- Container: Docker + Docker Compose

## 🚀 启动指南 (How to Run)

### 前置要求
- Docker Desktop 已安装并启动
- Git 已安装

### 启动步骤

1. **克隆项目**（如果还没有）
   ```bash
   git clone <repository-url>
   cd ecommerce-product-management
   ```

2. **启动所有服务**
   ```bash
   docker compose up --build
   ```

3. **等待容器启动完成**
   - 数据库初始化：约 30 秒
   - 后端服务启动和初始化（迁移+种子数据）：约 1-2 分钟
   - 前端服务启动：后端容器启动后自动启动，约 1 分钟
   
   > 💡 **提示**：前端容器会在后端容器启动后自动启动。你可以通过 `docker compose logs -f` 查看启动进度。

4. **访问应用**
   - 前端：http://localhost:3000（等待初始化完成后才能访问）
   - 后端 API：http://localhost:8000/api
   - 数据库：localhost:3306

### 停止服务
```bash
docker compose down
```

### 查看日志
```bash
# 查看所有服务日志
docker compose logs -f

# 查看特定服务日志
docker compose logs -f backend
docker compose logs -f frontend
docker compose logs -f db
```

## 🔗 服务地址 (Services)
- **Frontend**: http://localhost:3000
- **Backend API**: http://localhost:8000/api
- **Database**: localhost:3306
  - Username: root
  - Password: root
  - Database: ecommerce_db

## 🧪 测试账号
- **管理员**: admin@example.com / 123456

> 注意：首次启动会自动创建测试账号

## 📋 项目说明

本项目是一个小型电商商品管理系统，核心功能包括：

### 核心功能模块
- **商品管理**：商品的增删改查、分类管理、状态管理
- **订单处理**：
  - 订单创建：支持多商品订单创建，自动计算金额，库存验证
  - 订单状态流转：待支付→已支付→已发货→已完成
  - 订单取消：支持取消待支付和已支付订单
  - 订单详情查看
- **库存管理**：库存查询、库存调整、库存状态筛选（缺货/低库存/充足）

### 项目结构
```
ecommerce-product-management/
├── backend/          # Laravel 后端项目
├── frontend/         # Vue 3 前端项目
├── docker-compose.yml # Docker 编排配置
└── README.md         # 项目说明文档
```

## 🏗 架构设计文档

### 一、系统架构概述

本项目采用前后端分离架构，遵循 Laravel MVC（Model-View-Controller）设计模式，结合 Repository 模式和 Service 层，实现业务逻辑与数据访问的分离。

**架构层次：**
```
前端层（Vue 3） → API 层（Laravel） → 服务层（Service） → 仓储层（Repository） → 模型层（Model） → 数据库层（MySQL）
```

### 二、系统模块划分（MVC 架构）

#### 2.1 后端架构（Laravel MVC）

**1. Model 层（模型层）**
- **位置**：`backend/app/Models/`
- **职责**：定义数据模型、关联关系、业务规则
- **模块划分**：
  - `User.php` - 用户模型（管理员）
  - `Category.php` - 商品分类模型
  - `Product.php` - 商品模型
  - `Order.php` - 订单模型
  - `OrderItem.php` - 订单项模型
  - `InventoryLog.php` - 库存变动记录模型

**2. Controller 层（控制器层）**
- **位置**：`backend/app/Http/Controllers/Api/`
- **职责**：处理 HTTP 请求、参数验证、调用服务层、返回响应
- **模块划分**：
  - `AuthController.php` - 认证控制器（登录、登出、用户信息）
  - `ProductApiController.php` - 商品管理控制器（CRUD 操作）
  - `OrderApiController.php` - 订单管理控制器（订单创建、状态更新）
  - `InventoryApiController.php` - 库存管理控制器（库存查询、调整）
  - `DashboardApiController.php` - 仪表盘控制器（统计数据）

**3. Service 层（服务层）**
- **位置**：`backend/app/Services/`
- **职责**：封装业务逻辑、事务管理、业务规则验证
- **模块划分**：
  - `ProductService.php` - 商品业务逻辑（SKU 验证、价格验证）
  - `OrderService.php` - 订单业务逻辑（订单创建、状态流转、库存扣减）
  - `InventoryService.php` - 库存业务逻辑（库存增减、调整、记录）
  - `StatisticsService.php` - 统计业务逻辑（数据汇总、计算）

**4. Repository 层（仓储层）**
- **位置**：`backend/app/Repositories/`
- **职责**：数据访问抽象、查询封装、数据持久化
- **模块划分**：
  - `ProductRepository.php` - 商品数据访问
  - `OrderRepository.php` - 订单数据访问
  - `InventoryRepository.php` - 库存数据访问

**5. Middleware 层（中间件层）**
- **位置**：`backend/app/Http/Middleware/`
- **职责**：请求拦截、认证验证、权限控制
- **关键中间件**：
  - `auth:sanctum` - API Token 认证
  - `RoleMiddleware.php` - 角色权限验证（已简化，仅保留 admin）

**6. Resource 层（资源层）**
- **位置**：`backend/app/Http/Resources/`
- **职责**：API 响应数据格式化、字段转换
- **模块划分**：
  - `ProductResource.php` - 商品资源格式化
  - `OrderResource.php` - 订单资源格式化
  - `OrderItemResource.php` - 订单项资源格式化

#### 2.2 前端架构（Vue 3）

**1. Views 层（视图层）**
- **位置**：`frontend/src/views/`
- **职责**：页面组件、用户交互
- **模块划分**：
  - `auth/Login.vue` - 登录页面
  - `dashboard/Dashboard.vue` - 仪表盘页面
  - `products/ProductList.vue` - 商品列表页面
  - `products/ProductForm.vue` - 商品表单页面
  - `orders/OrderList.vue` - 订单列表页面
  - `orders/OrderDetail.vue` - 订单详情页面
  - `orders/OrderForm.vue` - 订单创建页面
  - `inventory/InventoryList.vue` - 库存管理页面

**2. Components 层（组件层）**
- **位置**：`frontend/src/components/`
- **职责**：可复用组件、布局组件
- **模块划分**：
  - `layout/MainLayout.vue` - 主布局组件（导航菜单、侧边栏）

**3. API 层（接口层）**
- **位置**：`frontend/src/api/`
- **职责**：API 请求封装、统一错误处理
- **模块划分**：
  - `request.js` - Axios 实例配置、请求拦截器
  - `modules/auth.js` - 认证相关 API
  - `modules/product.js` - 商品相关 API
  - `modules/order.js` - 订单相关 API
  - `modules/inventory.js` - 库存相关 API
  - `modules/dashboard.js` - 仪表盘相关 API

**4. Router 层（路由层）**
- **位置**：`frontend/src/router/index.js`
- **职责**：路由配置、导航守卫、权限控制

**5. Store 层（状态管理层）**
- **位置**：`frontend/src/store/`
- **职责**：全局状态管理、用户信息存储

### 三、路由规划

#### 3.1 后端 API 路由规划

**路由文件**：`backend/routes/api.php`

**路由分组策略：**
1. **公开路由**（无需认证）
   - `POST /api/login` - 用户登录

2. **受保护路由**（需要 `auth:sanctum` 中间件）
   - 用户相关：`GET /api/me`、`POST /api/logout`
   - 商品管理：RESTful 资源路由
   - 订单管理：RESTful 资源路由 + 状态更新路由
   - 库存管理：自定义路由
   - 仪表盘：统计数据路由

**详细路由表：**

| 方法 | 路径 | 控制器方法 | 说明 |
|------|------|-----------|------|
| POST | `/api/login` | `AuthController@login` | 用户登录（公开） |
| GET | `/api/me` | `AuthController@me` | 获取当前用户信息 |
| POST | `/api/logout` | `AuthController@logout` | 用户登出 |
| GET | `/api/products` | `ProductApiController@index` | 获取商品列表 |
| POST | `/api/products` | `ProductApiController@store` | 创建商品 |
| GET | `/api/products/{id}` | `ProductApiController@show` | 获取商品详情 |
| PUT | `/api/products/{id}` | `ProductApiController@update` | 更新商品 |
| DELETE | `/api/products/{id}` | `ProductApiController@destroy` | 删除商品 |
| GET | `/api/orders` | `OrderApiController@index` | 获取订单列表 |
| POST | `/api/orders` | `OrderApiController@store` | 创建订单 |
| GET | `/api/orders/{id}` | `OrderApiController@show` | 获取订单详情 |
| PUT | `/api/orders/{id}/status` | `OrderApiController@updateStatus` | 更新订单状态 |
| GET | `/api/inventory` | `InventoryApiController@index` | 获取库存列表 |
| GET | `/api/inventory/{product}` | `InventoryApiController@show` | 获取商品库存详情 |
| PUT | `/api/inventory/{product}` | `InventoryApiController@update` | 更新库存 |
| GET | `/api/dashboard/summary` | `DashboardApiController@summary` | 获取仪表盘统计数据 |

**路由设计原则：**
- 使用 RESTful 风格设计资源路由
- 使用 `apiResource` 简化 CRUD 路由定义
- 特殊操作使用自定义路由（如订单状态更新）
- 统一使用 `/api` 前缀
- 通过中间件实现认证和权限控制

#### 3.2 前端路由规划

**路由文件**：`frontend/src/router/index.js`

**路由结构：**
- `/login` - 登录页面（公开）
- `/dashboard` - 仪表盘（需认证）
- `/products` - 商品列表（需认证）
- `/products/create` - 创建商品（需认证）
- `/products/:id/edit` - 编辑商品（需认证）
- `/orders` - 订单列表（需认证）
- `/orders/create` - 创建订单（需认证）
- `/orders/:id` - 订单详情（需认证）
- `/inventory` - 库存管理（需认证）

**路由守卫：**
- 使用 `beforeEach` 导航守卫检查认证状态
- 未认证用户自动跳转到登录页
- Token 存储在 localStorage，刷新页面保持登录状态

### 四、目录结构规范

#### 4.1 后端目录结构（Laravel）

```
backend/
├── app/
│   ├── Console/              # 命令行命令
│   ├── Exceptions/           # 异常处理
│   │   └── Handler.php       # 全局异常处理器
│   ├── Http/
│   │   ├── Controllers/      # 控制器
│   │   │   ├── Api/          # API 控制器
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── ProductApiController.php
│   │   │   │   ├── OrderApiController.php
│   │   │   │   ├── InventoryApiController.php
│   │   │   │   └── DashboardApiController.php
│   │   │   └── Controller.php  # 基础控制器
│   │   ├── Middleware/       # 中间件
│   │   │   ├── Authenticate.php
│   │   │   └── RoleMiddleware.php
│   │   ├── Resources/        # API 资源
│   │   │   ├── ProductResource.php
│   │   │   ├── OrderResource.php
│   │   │   └── OrderItemResource.php
│   │   └── Requests/         # 表单请求验证
│   ├── Models/               # 数据模型
│   │   ├── User.php
│   │   ├── Category.php
│   │   ├── Product.php
│   │   ├── Order.php
│   │   ├── OrderItem.php
│   │   └── InventoryLog.php
│   ├── Providers/            # 服务提供者
│   │   ├── AppServiceProvider.php
│   │   └── RouteServiceProvider.php
│   ├── Repositories/         # 仓储层
│   │   ├── ProductRepository.php
│   │   ├── OrderRepository.php
│   │   └── InventoryRepository.php
│   └── Services/             # 服务层
│       ├── ProductService.php
│       ├── OrderService.php
│       ├── InventoryService.php
│       └── StatisticsService.php
├── bootstrap/                # 启动文件
│   └── app.php              # 应用启动配置
├── config/                  # 配置文件
│   ├── app.php
│   ├── database.php
│   ├── auth.php
│   └── sanctum.php
├── database/
│   ├── migrations/          # 数据库迁移
│   ├── seeders/             # 数据填充
│   └── factories/           # 模型工厂
├── routes/
│   ├── api.php              # API 路由
│   ├── web.php              # Web 路由
│   └── console.php          # 控制台路由
├── storage/                  # 存储目录
├── tests/                    # 测试文件
└── public/                   # 公共入口
    └── index.php
```

#### 4.2 前端目录结构（Vue 3）

```
frontend/
├── src/
│   ├── api/                 # API 接口层
│   │   ├── request.js       # Axios 配置
│   │   └── modules/         # 模块化 API
│   │       ├── auth.js
│   │       ├── product.js
│   │       ├── order.js
│   │       ├── inventory.js
│   │       └── dashboard.js
│   ├── assets/              # 静态资源
│   ├── components/          # 组件
│   │   ├── layout/          # 布局组件
│   │   └── common/          # 通用组件
│   ├── router/              # 路由配置
│   │   └── index.js
│   ├── store/               # 状态管理
│   │   └── modules/
│   ├── views/               # 页面视图
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── products/
│   │   ├── orders/
│   │   └── inventory/
│   ├── composables/         # 组合式函数
│   ├── utils/               # 工具函数
│   ├── App.vue              # 根组件
│   └── main.js              # 入口文件
├── public/                  # 公共文件
├── index.html               # HTML 模板
├── vite.config.js           # Vite 配置
└── package.json             # 依赖配置
```

## 🗄 数据库设计文档

### 一、数据库概述

本系统使用 MySQL 8.0 数据库，共设计 **7 张数据表**，涵盖用户管理、商品管理、订单管理、库存管理等核心业务模块。

**数据库名称**：`ecommerce_db`

**字符集**：`utf8mb4`

**排序规则**：`utf8mb4_unicode_ci`

### 二、数据表结构设计

#### 2.1 users（用户表）

**表说明**：存储系统管理员用户信息

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 用户ID |
| name | VARCHAR | 255 | NOT NULL | - | 用户姓名 |
| email | VARCHAR | 255 | UNIQUE, NOT NULL | - | 邮箱地址（登录账号） |
| email_verified_at | TIMESTAMP | - | NULL | NULL | 邮箱验证时间 |
| password | VARCHAR | 255 | NOT NULL | - | 密码（加密存储） |
| role | ENUM | - | NOT NULL | 'admin' | 用户角色（仅支持 admin） |
| is_active | BOOLEAN | - | NOT NULL | true | 是否激活 |
| remember_token | VARCHAR | 100 | NULL | NULL | 记住我令牌 |
| last_login_at | TIMESTAMP | - | NULL | NULL | 最后登录时间 |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- UNIQUE: `email`
- INDEX: `role`
- INDEX: `is_active`

#### 2.2 categories（商品分类表）

**表说明**：存储商品分类信息，支持多级分类（自关联）

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 分类ID |
| name | VARCHAR | 100 | NOT NULL | - | 分类名称 |
| parent_id | BIGINT UNSIGNED | - | FOREIGN KEY, NULL | NULL | 父分类ID（自关联） |
| description | TEXT | - | NULL | NULL | 分类描述 |
| sort_order | INTEGER | - | NOT NULL | 0 | 排序序号 |
| is_active | BOOLEAN | - | NOT NULL | true | 是否启用 |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- INDEX: `parent_id`
- INDEX: `is_active`
- FOREIGN KEY: `parent_id` REFERENCES `categories(id)` ON DELETE SET NULL

**说明**：支持多级分类，通过 `parent_id` 实现自关联，删除父分类时子分类的 `parent_id` 设置为 NULL。

#### 2.3 products（商品表）

**表说明**：存储商品基本信息、价格、库存等核心数据

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 商品ID |
| name | VARCHAR | 200 | NOT NULL | - | 商品名称 |
| sku | VARCHAR | 100 | UNIQUE, NOT NULL | - | 商品SKU（唯一标识） |
| category_id | BIGINT UNSIGNED | - | FOREIGN KEY, NULL | NULL | 分类ID |
| description | TEXT | - | NULL | NULL | 商品描述 |
| price | DECIMAL | 10,2 | NOT NULL | - | 售价 |
| cost_price | DECIMAL | 10,2 | NULL | NULL | 成本价 |
| image | VARCHAR | 255 | NULL | NULL | 主图URL |
| images | JSON | - | NULL | NULL | 商品图片列表（JSON数组） |
| status | ENUM | - | NOT NULL | 'active' | 商品状态（active/inactive/sold_out） |
| stock_quantity | INTEGER UNSIGNED | - | NOT NULL | 0 | 库存数量 |
| low_stock_threshold | INTEGER UNSIGNED | - | NOT NULL | 10 | 低库存阈值 |
| weight | DECIMAL | 8,2 | NULL | NULL | 商品重量（kg） |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |
| deleted_at | TIMESTAMP | - | NULL | NULL | 软删除时间 |

**索引：**
- PRIMARY KEY: `id`
- UNIQUE: `sku`
- INDEX: `category_id`
- INDEX: `status`
- INDEX: `stock_quantity`
- FOREIGN KEY: `category_id` REFERENCES `categories(id)` ON DELETE SET NULL

**说明**：
- SKU 必须唯一，用于商品唯一标识
- 支持软删除（`deleted_at`）
- 商品状态：`active`（上架）、`inactive`（下架）、`sold_out`（售罄）

#### 2.4 orders（订单表）

**表说明**：存储订单主信息，包括订单号、金额、状态、收货信息等

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 订单ID |
| order_no | VARCHAR | 50 | UNIQUE, NOT NULL | - | 订单号（唯一） |
| user_id | BIGINT UNSIGNED | - | NULL | NULL | 用户ID（可为空，支持游客订单） |
| total_amount | DECIMAL | 10,2 | NOT NULL | - | 订单总金额 |
| discount_amount | DECIMAL | 10,2 | NOT NULL | 0.00 | 折扣金额 |
| final_amount | DECIMAL | 10,2 | NOT NULL | - | 最终支付金额 |
| status | ENUM | - | NOT NULL | 'pending' | 订单状态（pending/paid/shipped/completed/cancelled） |
| shipping_address | TEXT | - | NULL | NULL | 收货地址 |
| shipping_name | VARCHAR | 100 | NULL | NULL | 收货人姓名 |
| shipping_phone | VARCHAR | 20 | NULL | NULL | 收货人电话 |
| remark | TEXT | - | NULL | NULL | 订单备注 |
| paid_at | TIMESTAMP | - | NULL | NULL | 支付时间 |
| shipped_at | TIMESTAMP | - | NULL | NULL | 发货时间 |
| completed_at | TIMESTAMP | - | NULL | NULL | 完成时间 |
| cancelled_at | TIMESTAMP | - | NULL | NULL | 取消时间 |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- UNIQUE: `order_no`
- INDEX: `user_id`
- INDEX: `status`
- INDEX: `created_at`

**说明**：
- 订单号唯一，系统自动生成
- 订单状态流转：`pending`（待支付）→ `paid`（已支付）→ `shipped`（已发货）→ `completed`（已完成）
- 支持取消订单：`pending`/`paid` → `cancelled`
- 各状态变更时间单独记录

#### 2.5 order_items（订单项表）

**表说明**：存储订单中的商品明细信息，一个订单包含多个订单项

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 订单项ID |
| order_id | BIGINT UNSIGNED | - | FOREIGN KEY, NOT NULL | - | 订单ID |
| product_id | BIGINT UNSIGNED | - | FOREIGN KEY, NOT NULL | - | 商品ID |
| product_name | VARCHAR | 200 | NOT NULL | - | 商品名称（快照） |
| product_sku | VARCHAR | 100 | NOT NULL | - | 商品SKU（快照） |
| product_price | DECIMAL | 10,2 | NOT NULL | - | 商品单价（快照） |
| quantity | INTEGER UNSIGNED | - | NOT NULL | - | 购买数量 |
| subtotal | DECIMAL | 10,2 | NOT NULL | - | 小计金额 |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- INDEX: `order_id`
- INDEX: `product_id`
- FOREIGN KEY: `order_id` REFERENCES `orders(id)` ON DELETE CASCADE
- FOREIGN KEY: `product_id` REFERENCES `products(id)` ON DELETE RESTRICT

**说明**：
- 订单项存储商品快照信息（名称、SKU、价格），防止商品信息变更影响历史订单
- 删除订单时，订单项级联删除（CASCADE）
- 删除商品时，如果存在订单项则禁止删除（RESTRICT）

#### 2.6 inventory_logs（库存变动记录表）

**表说明**：记录所有库存变动操作，用于库存审计和追溯

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 记录ID |
| product_id | BIGINT UNSIGNED | - | FOREIGN KEY, NOT NULL | - | 商品ID |
| type | ENUM | - | NOT NULL | - | 变动类型（in/out/adjust/sale/return） |
| quantity | INTEGER | - | NOT NULL | - | 变动数量（正数表示增加，负数表示减少） |
| before_quantity | INTEGER UNSIGNED | - | NOT NULL | - | 变动前库存 |
| after_quantity | INTEGER UNSIGNED | - | NOT NULL | - | 变动后库存 |
| related_order_id | BIGINT UNSIGNED | - | FOREIGN KEY, NULL | NULL | 关联订单ID |
| remark | VARCHAR | 500 | NULL | NULL | 备注说明 |
| operator_id | BIGINT UNSIGNED | - | NULL | NULL | 操作员ID |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- INDEX: `product_id`
- INDEX: `type`
- INDEX: `created_at`
- INDEX: `related_order_id`
- FOREIGN KEY: `product_id` REFERENCES `products(id)` ON DELETE CASCADE
- FOREIGN KEY: `related_order_id` REFERENCES `orders(id)` ON DELETE SET NULL

**说明**：
- 变动类型：
  - `in`：入库
  - `out`：出库
  - `adjust`：调整
  - `sale`：销售（关联订单）
  - `return`：退货（关联订单）
- 记录变动前后的库存数量，便于追溯
- 删除商品时，库存记录级联删除
- 删除订单时，关联的库存记录保留，但 `related_order_id` 设置为 NULL

#### 2.7 personal_access_tokens（个人访问令牌表）

**表说明**：Laravel Sanctum 认证系统使用的令牌表，存储 API 访问令牌

| 字段名 | 数据类型 | 长度 | 约束 | 默认值 | 说明 |
|--------|---------|------|------|--------|------|
| id | BIGINT UNSIGNED | - | PRIMARY KEY, AUTO_INCREMENT | - | 令牌ID |
| tokenable_type | VARCHAR | 255 | NOT NULL | - | 关联模型类型（如 User） |
| tokenable_id | BIGINT UNSIGNED | - | NOT NULL | - | 关联模型ID |
| name | VARCHAR | 255 | NOT NULL | - | 令牌名称 |
| token | VARCHAR | 64 | UNIQUE, NOT NULL | - | 令牌值（哈希） |
| abilities | TEXT | - | NULL | NULL | 权限列表（JSON） |
| last_used_at | TIMESTAMP | - | NULL | NULL | 最后使用时间 |
| expires_at | TIMESTAMP | - | NULL | NULL | 过期时间 |
| created_at | TIMESTAMP | - | NULL | NULL | 创建时间 |
| updated_at | TIMESTAMP | - | NULL | NULL | 更新时间 |

**索引：**
- PRIMARY KEY: `id`
- UNIQUE: `token`
- INDEX: `tokenable_type`, `tokenable_id`（复合索引，通过 morphs 创建）

**说明**：
- 使用多态关联（morphs），支持多种模型类型
- 令牌值存储为哈希，提高安全性
- 支持令牌过期时间设置

### 三、E-R 图（实体关系图）

#### 3.1 实体关系说明

**核心实体：**
1. **User（用户）** - 系统管理员
2. **Category（分类）** - 商品分类
3. **Product（商品）** - 商品信息
4. **Order（订单）** - 订单主表
5. **OrderItem（订单项）** - 订单明细
6. **InventoryLog（库存记录）** - 库存变动记录

#### 3.2 实体关系描述

```
┌─────────────┐
│    User     │
│  (用户表)    │
└──────┬──────┘
       │
       │ 1:N (一个用户可以有多个订单)
       │
       ▼
┌─────────────┐
│    Order    │
│  (订单表)    │◄──────┐
└──────┬──────┘       │
       │              │
       │ 1:N          │ N:1
       │              │
       ▼              │
┌─────────────┐      │
│  OrderItem  │      │
│  (订单项表)  │      │
└──────┬──────┘      │
       │             │
       │ N:1         │
       │             │
       ▼             │
┌─────────────┐      │
│   Product   │      │
│  (商品表)    │      │
└──────┬──────┘      │
       │             │
       │ N:1         │
       │             │
       ▼             │
┌─────────────┐      │
│  Category   │      │
│  (分类表)    │      │
└─────────────┘      │
                      │
                      │
       ┌──────────────┘
       │
       │ N:1 (一个订单可以有多条库存记录)
       │
       ▼
┌─────────────┐
│InventoryLog │
│(库存记录表)  │
└─────────────┘
```

#### 3.3 关系详细说明

**1. User ↔ Order（一对多）**
- 关系：一个用户可以有多个订单
- 外键：`orders.user_id` → `users.id`
- 删除策略：用户删除时，订单的 `user_id` 设置为 NULL（允许游客订单）

**2. Category ↔ Product（一对多）**
- 关系：一个分类可以包含多个商品
- 外键：`products.category_id` → `categories.id`
- 删除策略：删除分类时，商品的 `category_id` 设置为 NULL

**3. Category ↔ Category（自关联，一对多）**
- 关系：支持多级分类，一个分类可以有多个子分类
- 外键：`categories.parent_id` → `categories.id`
- 删除策略：删除父分类时，子分类的 `parent_id` 设置为 NULL

**4. Order ↔ OrderItem（一对多）**
- 关系：一个订单包含多个订单项
- 外键：`order_items.order_id` → `orders.id`
- 删除策略：级联删除（CASCADE），删除订单时同时删除所有订单项

**5. Product ↔ OrderItem（一对多）**
- 关系：一个商品可以出现在多个订单项中
- 外键：`order_items.product_id` → `products.id`
- 删除策略：限制删除（RESTRICT），存在订单项时禁止删除商品

**6. Product ↔ InventoryLog（一对多）**
- 关系：一个商品可以有多条库存变动记录
- 外键：`inventory_logs.product_id` → `products.id`
- 删除策略：级联删除（CASCADE），删除商品时同时删除所有库存记录

**7. Order ↔ InventoryLog（一对多）**
- 关系：一个订单可以产生多条库存变动记录（销售、退货等）
- 外键：`inventory_logs.related_order_id` → `orders.id`
- 删除策略：删除订单时，库存记录的 `related_order_id` 设置为 NULL

**8. User ↔ PersonalAccessToken（一对多）**
- 关系：一个用户可以有多个访问令牌
- 外键：通过多态关联 `tokenable_type` 和 `tokenable_id`
- 删除策略：级联删除，删除用户时同时删除所有令牌

### 四、数据库设计原则

1. **规范化设计**：遵循第三范式（3NF），减少数据冗余
2. **外键约束**：使用外键保证数据完整性
3. **索引优化**：为常用查询字段创建索引，提高查询性能
4. **软删除**：商品表支持软删除，保留历史数据
5. **快照机制**：订单项存储商品快照，防止历史订单数据被修改
6. **审计追踪**：库存变动记录表完整记录所有库存操作
7. **时间戳**：所有表包含 `created_at` 和 `updated_at` 字段
8. **字符集**：使用 `utf8mb4` 支持完整的 Unicode 字符

## 💡 核心功能实现思路

### 一、商品管理功能实现思路

#### 1.1 功能概述

商品管理是系统的核心模块之一，负责商品的增删改查、状态管理、SKU 唯一性验证等操作。

#### 1.2 架构设计

采用 **Controller → Service → Repository → Model** 的分层架构：

```
ProductApiController (控制器层)
    ↓
ProductService (服务层 - 业务逻辑)
    ↓
ProductRepository (仓储层 - 数据访问)
    ↓
Product Model (模型层 - 数据模型)
```

#### 1.3 核心实现流程

**1. 商品创建流程**

```
用户请求 → Controller 参数验证 → Service 业务验证 → Repository 数据持久化 → 返回结果
```

**详细步骤：**
1. **Controller 层**（`ProductApiController@store`）：
   - 接收前端请求参数
   - 使用 Laravel 表单验证规则验证参数（名称、SKU、价格等）
   - 调用 Service 层创建商品

2. **Service 层**（`ProductService@create`）：
   - **SKU 唯一性验证**：通过 Repository 检查 SKU 是否已存在
   - **价格合理性验证**：确保成本价不大于售价
   - 调用 Repository 创建商品记录

3. **Repository 层**（`ProductRepository@create`）：
   - 执行数据库插入操作
   - 返回创建的 Product 模型实例

4. **Model 层**（`Product`）：
   - 定义字段填充规则（`$fillable`）
   - 定义数据类型转换（`$casts`）
   - 定义关联关系（分类、订单项、库存记录）

**关键代码逻辑：**
```php
// Service 层验证逻辑
if ($this->repository->existsBySku($data['sku'])) {
    throw new \Exception('SKU 已存在，请使用其他 SKU');
}

if (isset($data['cost_price']) && $data['cost_price'] > $data['price']) {
    throw new \Exception('成本价不能大于售价');
}
```

**2. 商品更新流程**

与创建流程类似，但增加了以下验证：
- **SKU 唯一性验证（排除自身）**：更新时允许保持原 SKU，但新 SKU 不能与其他商品重复
- **部分更新支持**：使用 `sometimes` 验证规则，允许只更新部分字段

**关键代码逻辑：**
```php
// 排除当前商品检查 SKU 唯一性
if (isset($data['sku']) && $this->repository->existsBySku($data['sku'], $product->id)) {
    throw new \Exception('SKU 已存在，请使用其他 SKU');
}
```

**3. 商品删除流程**

采用**智能删除策略**：
- **检查订单关联**：如果商品已存在于订单项中，使用软删除（保留历史数据）
- **无关联商品**：使用物理删除（彻底删除）

**关键代码逻辑：**
```php
if ($product->orderItems()->exists()) {
    // 有订单关联，使用软删除
    return $product->delete();
}
// 无关联，物理删除
return $product->forceDelete();
```

**4. 商品状态管理**

- **状态枚举**：`active`（上架）、`inactive`（下架）、`sold_out`（售罄）
- **自动状态更新**：库存为 0 时自动设置为 `sold_out`
- **状态切换**：提供 `toggleStatus` 方法快速切换上架/下架状态

**5. 商品查询功能**

- **分页查询**：使用 Laravel 分页器，支持自定义每页数量
- **多条件筛选**：支持按分类、状态、关键词搜索
- **关联数据加载**：使用 Eloquent 的 `load()` 方法预加载关联数据（分类、库存记录）

**6. 数据验证规则**

**创建商品验证规则：**
- `name`: 必填，字符串，最大 200 字符
- `sku`: 必填，字符串，最大 100 字符，唯一性
- `price`: 必填，数值，最小值 0
- `cost_price`: 可选，数值，最小值 0
- `category_id`: 可选，必须存在于 categories 表

**更新商品验证规则：**
- 使用 `sometimes` 规则，允许部分更新
- SKU 唯一性验证排除当前商品

#### 1.4 技术要点

1. **Repository 模式**：封装数据访问逻辑，便于测试和维护
2. **Service 层业务验证**：在 Service 层集中处理业务规则，Controller 只负责请求响应
3. **软删除机制**：使用 Laravel SoftDeletes，保留历史数据
4. **异常处理**：使用 try-catch 捕获业务异常，返回友好的错误信息
5. **资源转换**：使用 `ProductResource` 统一 API 响应格式

#### 1.5 数据流转示例

**创建商品请求流程：**
```
前端表单提交
  ↓
ProductApiController@store (参数验证)
  ↓
ProductService@create (SKU 验证、价格验证)
  ↓
ProductRepository@create (数据库插入)
  ↓
Product Model (数据模型)
  ↓
ProductResource (响应格式化)
  ↓
返回 JSON 响应给前端
```

### 二、订单处理功能实现思路

#### 2.1 功能概述

订单处理是系统的核心业务模块，负责订单的创建、状态流转、库存扣减与恢复、订单查询等操作。订单处理涉及多个业务实体（订单、订单项、商品、库存）的协调，需要保证数据一致性和业务逻辑的正确性。

#### 2.2 架构设计

采用 **Controller → Service → Repository → Model** 的分层架构，结合**数据库事务**确保数据一致性：

```
OrderApiController (控制器层)
    ↓
OrderService (服务层 - 业务逻辑、事务管理)
    ↓
OrderRepository (仓储层 - 数据访问)
    ↓
Order Model (模型层 - 数据模型)
    ↓
InventoryService (库存服务 - 库存扣减/恢复)
```

#### 2.3 核心实现流程

**1. 订单创建流程**

订单创建是系统最复杂的业务流程之一，涉及库存验证、金额计算、订单项创建、库存扣减等多个步骤，必须使用**数据库事务**确保原子性。

```
用户请求 → Controller 参数验证 → Service 业务验证 → 数据库事务开始
    ↓
库存验证（逐个检查商品库存和状态）
    ↓
生成订单号（唯一标识）
    ↓
计算订单金额（商品总额、优惠金额、最终金额）
    ↓
创建订单主记录（orders 表）
    ↓
创建订单项记录（order_items 表，保存商品快照）
    ↓
扣减商品库存（更新 products.stock_quantity）
    ↓
创建库存变动记录（inventory_logs 表，type = 'sale'）
    ↓
事务提交 → 返回订单信息
```

**详细步骤：**

1. **Controller 层**（`OrderApiController@store`）：
   - 接收前端请求参数（订单项列表、收货信息、优惠金额等）
   - 使用 Laravel 表单验证规则验证参数：
     - `items`: 必填，数组，至少包含一个订单项
     - `items.*.product_id`: 必填，必须存在于 products 表
     - `items.*.quantity`: 必填，整数，最小值 1
     - `discount_amount`: 可选，数值，最小值 0
   - 调用 Service 层创建订单

2. **Service 层**（`OrderService@create`）：
   - **开启数据库事务**：使用 `DB::transaction()` 确保所有操作原子性
   - **库存验证**：
     - 遍历订单项，逐个检查商品库存是否充足
     - 检查商品状态是否为 `active`（已下架商品不能购买）
     - 库存不足或商品已下架时抛出异常，事务回滚
   - **生成订单号**：使用 `Order::generateOrderNo()` 生成唯一订单号
     - 格式：`ORDER20240101123456`（ORDER + 日期 + 6位随机数）
   - **计算订单金额**：
     - `total_amount` = 所有订单项的 `subtotal` 之和
     - `subtotal` = 商品单价 × 购买数量
     - `final_amount` = `total_amount` - `discount_amount`
   - **创建订单主记录**：调用 Repository 创建订单
   - **创建订单项并扣减库存**：
     - 遍历订单项，创建 `OrderItem` 记录（保存商品快照：名称、SKU、价格）
     - 调用 `InventoryService::decreaseStock()` 扣减库存
     - 自动创建库存变动记录（`inventory_logs`，type = 'sale'）

3. **Repository 层**（`OrderRepository@create`）：
   - 执行数据库插入操作
   - 返回创建的 Order 模型实例

4. **Model 层**（`Order`、`OrderItem`）：
   - `Order` 模型定义订单字段、关联关系（订单项、用户、库存记录）
   - `OrderItem` 模型存储商品快照，防止商品信息变更影响历史订单

**关键代码逻辑：**
```php
// Service 层订单创建（事务保护）
return DB::transaction(function () use ($data) {
    // 1. 库存验证
    foreach ($items as $item) {
        $product = Product::findOrFail($item['product_id']);
        if (!$product->hasEnoughStock($item['quantity'])) {
            throw new \Exception("商品 {$product->name} 库存不足");
        }
        if ($product->status !== 'active') {
            throw new \Exception("商品 {$product->name} 已下架");
        }
    }
    
    // 2. 生成订单号
    $orderNo = Order::generateOrderNo();
    
    // 3. 计算金额
    $totalAmount = 0;
    foreach ($items as $item) {
        $product = Product::findOrFail($item['product_id']);
        $totalAmount += $product->price * $item['quantity'];
    }
    $finalAmount = $totalAmount - ($data['discount_amount'] ?? 0);
    
    // 4. 创建订单
    $order = $this->repository->create([...]);
    
    // 5. 创建订单项并扣减库存
    foreach ($items as $item) {
        OrderItem::create([...]); // 保存商品快照
        $this->inventoryService->decreaseStock(...); // 扣减库存
    }
    
    return $order->load('orderItems');
});
```

**2. 订单状态流转流程**

订单状态流转遵循严格的状态机模式，确保状态变更的合法性和数据一致性。

**状态流转图：**
```
pending（待支付） → paid（已支付） → shipped（已发货） → completed（已完成）
     ↓                                    ↓
cancelled（已取消）                    cancelled（已取消）
```

**状态流转规则：**
- `pending` → `paid`：待支付 → 已支付
- `pending` → `cancelled`：待支付 → 已取消（恢复库存）
- `paid` → `shipped`：已支付 → 已发货
- `paid` → `cancelled`：已支付 → 已取消（恢复库存）
- `shipped` → `completed`：已发货 → 已完成
- `shipped` → `cancelled`：已发货 → 已取消（恢复库存）

**详细步骤：**

1. **Controller 层**（`OrderApiController@updateStatus`）：
   - 接收状态更新请求参数
   - 验证状态值是否合法（`pending`、`paid`、`shipped`、`completed`、`cancelled`）
   - 调用 Service 层更新订单状态

2. **Service 层**（`OrderService@updateStatus`）：
   - **状态流转验证**：检查当前状态是否允许变更为目标状态
     - 使用 `$allowedTransitions` 数组定义允许的状态流转
     - 非法状态流转时抛出异常
   - **记录状态变更时间**：
     - `paid` → 记录 `paid_at`
     - `shipped` → 记录 `shipped_at`
     - `completed` → 记录 `completed_at`
     - `cancelled` → 记录 `cancelled_at`，并恢复库存
   - **库存恢复**（订单取消时）：
     - 遍历订单项，调用 `InventoryService::increaseStock()` 恢复库存
     - 自动创建库存变动记录（`inventory_logs`，type = 'return'）

**关键代码逻辑：**
```php
// Service 层状态更新
public function updateStatus(Order $order, string $status): Order
{
    $oldStatus = $order->status;
    
    // 状态流转验证
    $allowedTransitions = [
        'pending' => ['paid', 'cancelled'],
        'paid' => ['shipped', 'cancelled'],
        'shipped' => ['completed', 'cancelled'],
    ];
    
    if (!in_array($status, $allowedTransitions[$oldStatus] ?? [])) {
        throw new \Exception("订单状态不能从 {$oldStatus} 直接变更为 {$status}");
    }
    
    $updateData = ['status' => $status];
    
    // 记录状态变更时间
    switch ($status) {
        case 'paid':
            $updateData['paid_at'] = now();
            break;
        case 'shipped':
            $updateData['shipped_at'] = now();
            break;
        case 'completed':
            $updateData['completed_at'] = now();
            break;
        case 'cancelled':
            $updateData['cancelled_at'] = now();
            $this->restoreInventory($order); // 恢复库存
            break;
    }
    
    return $this->repository->update($order, $updateData);
}
```

**3. 订单查询功能**

- **订单列表查询**：
  - 支持多条件筛选：订单状态、订单号、日期范围
  - 使用 Laravel 分页器，支持自定义每页数量
  - 关联加载订单项数据（`load('orderItems')`）

- **订单详情查询**：
  - 加载订单主信息和所有订单项
  - 关联加载商品信息（`load('orderItems.product')`）
  - 使用 `OrderResource` 统一格式化响应数据

**4. 前端订单创建表单**

前端订单创建表单（`OrderForm.vue`）实现以下功能：

- **动态商品选择**：
  - 使用 `el-select` 下拉选择商品
  - 显示商品名称、SKU、库存信息
  - 禁用库存为 0 或已下架的商品

- **动态订单项管理**：
  - 支持添加/删除订单项
  - 自动计算单价、小计、总金额
  - 数量输入框限制：最小值 1，最大值 = 商品库存

- **金额自动计算**：
  - 商品总额 = 所有订单项小计之和
  - 最终金额 = 商品总额 - 优惠金额
  - 实时更新显示

- **表单验证**：
  - 至少选择一个商品
  - 每个订单项必须选择商品和数量
  - 优惠金额不能大于商品总额

#### 2.4 技术要点

1. **数据库事务**：订单创建涉及多个表的操作，必须使用事务确保原子性
2. **库存快照机制**：订单项保存商品快照（名称、SKU、价格），防止历史订单数据被修改
3. **状态机模式**：使用状态机管理订单状态流转，确保状态变更的合法性
4. **库存自动管理**：订单创建时自动扣减库存，订单取消时自动恢复库存
5. **异常处理**：库存不足、商品已下架、非法状态流转等异常情况都有明确的错误提示
6. **审计追踪**：所有库存变动都记录在 `inventory_logs` 表中，关联订单ID，便于追溯

#### 2.5 数据流转示例

**创建订单请求流程：**
```
前端表单提交（订单项、收货信息、优惠金额）
  ↓
OrderApiController@store (参数验证)
  ↓
OrderService@create (开启事务)
  ↓
库存验证（逐个检查商品库存和状态）
  ↓
生成订单号（ORDER20240101123456）
  ↓
计算订单金额（商品总额、优惠金额、最终金额）
  ↓
OrderRepository@create (创建订单主记录)
  ↓
OrderItem::create (创建订单项，保存商品快照)
  ↓
InventoryService::decreaseStock (扣减库存，创建库存记录)
  ↓
事务提交
  ↓
OrderResource (响应格式化)
  ↓
返回 JSON 响应给前端
```

**更新订单状态流程：**
```
前端状态更新请求（订单ID、新状态）
  ↓
OrderApiController@updateStatus (参数验证)
  ↓
OrderService@updateStatus (状态流转验证)
  ↓
记录状态变更时间（paid_at/shipped_at/completed_at/cancelled_at）
  ↓
订单取消时：InventoryService::increaseStock (恢复库存)
  ↓
OrderRepository@update (更新订单状态)
  ↓
OrderResource (响应格式化)
  ↓
返回 JSON 响应给前端
```

### 三、库存管理功能实现思路

#### 3.1 功能概述

库存管理负责库存查询、库存调整、库存状态筛选，并通过 `inventory_logs` 记录每一次库存变动，用于审计追溯。库存管理与订单模块强相关：**订单创建扣减库存**、**订单取消恢复库存**，都统一走库存服务，保证口径一致。

#### 3.2 架构设计

采用 **Controller → Service → Repository → Model** 分层架构，并将“库存变动记录”作为一等公民：

```
InventoryApiController (控制器层)
    ↓
InventoryService (服务层 - 库存增减/调整、记录库存日志)
    ↓
InventoryRepository (仓储层 - 查询封装、列表筛选)
    ↓
Product / InventoryLog Model (模型层)
```

#### 3.3 核心实现流程

**1. 库存列表查询 + 状态筛选**

- **查询维度**：商品名称/SKU（如有）、分类（如有）、库存状态（缺货/低库存/充足）
- **库存状态标准（统一口径）**：
  - **缺货**：库存数量 \(= 0\)
  - **低库存**：\(0 <\) 库存数量 \(\le 10\)
  - **充足**：库存数量 \(> 10\)
- **实现方式**：
  - 前端 `InventoryList.vue` 下拉框选择库存状态后，携带明确的筛选参数（如 `out_of_stock` / `low_stock` / `sufficient`）
  - 后端 `InventoryRepository` 根据筛选参数拼接查询条件（避免使用“阈值字段”作为筛选输入）

**2. 库存调整（入库/出库/调整）**

库存调整需要同时满足两个目标：**更新 `products.stock_quantity`** + **写入 `inventory_logs`**。

```
管理员提交调整请求
  ↓
InventoryApiController@update (参数校验)
  ↓
InventoryService (写事务：更新库存 + 写库存日志)
  ↓
返回最新库存数据
```

关键点：
- **不允许库存变成负数**：出库/扣减时校验 `stock_quantity >= quantity`
- **记录变动前后库存**：`before_quantity` / `after_quantity`，便于追溯
- **记录变动原因**：`remark` 写明“手工调整/订单创建/订单取消”等

**3. 与订单模块的联动**

- **订单创建**：`InventoryService::decreaseStock($product, $qty, $orderId, '订单创建')`
  - 更新商品库存
  - 记录 `inventory_logs`（`type = sale`）
- **订单取消**：`InventoryService::increaseStock($product, $qty, $orderId, '订单取消')`
  - 恢复商品库存
  - 记录 `inventory_logs`（`type = return` 或使用统一的“恢复”类型）

#### 3.4 技术要点

1. **统一库存入口**：所有库存变动（订单/手工调整）都通过 `InventoryService`，避免口径分裂
2. **审计追踪**：`inventory_logs` 保存订单关联、变动前后值、备注，支持问题排查
3. **并发安全（扩展建议）**：高并发下可引入行锁（`select ... for update`）或乐观锁防止超卖
4. **数据一致性**：库存更新与日志写入建议在同一事务中完成

### 四、数据统计（仪表盘）功能实现思路

#### 4.1 功能概述

仪表盘用于展示核心经营指标（示例：商品数量、订单数量、库存总价值等），为管理员提供快速概览。统计计算由后端统一提供，前端只负责展示。

#### 4.2 架构设计

```
DashboardApiController (控制器层)
    ↓
StatisticsService (服务层 - 聚合统计、口径统一)
    ↓
数据库聚合查询（SUM/COUNT/分组）
```

#### 4.3 核心实现流程

**1. 统计接口**

- 前端页面 `Dashboard.vue` 加载时调用 `GET /api/dashboard/summary`
- 后端 `StatisticsService::getDashboardSummary()` 执行聚合查询并返回结构化数据

**2. 库存总价值计算口径**

- 口径：\(\text{total\_value} = \sum(\text{price} \times \text{stock\_quantity})\)
- 关键处理：
  - 使用 `COALESCE(..., 0)` 防止空表返回 `NULL`
  - 返回值类型显式转为 `float`，避免前端拿到字符串导致展示/计算异常

**3. 前端展示要点**

- 将金额字段转为 `Number` 再格式化（如 `toFixed(2)`），确保展示稳定
- 仪表盘卡片布局保持简洁，避免展示未实现或不在需求范围内的模块

#### 4.4 技术要点（性能与可维护性）

1. **统计口径统一**：所有统计都集中在 `StatisticsService`，避免各处重复计算造成口径不一致
2. **索引与查询优化（建议）**：
   - `orders.status`、`orders.created_at` 建索引，支撑按状态/时间的统计扩展
   - `products.stock_quantity` 建索引，支撑库存分段/筛选统计扩展
3. **缓存策略（扩展建议）**：统计结果可按分钟级缓存（如 Redis），降低高频请求压力

### 五、用户认证/权限功能实现思路

#### 5.1 功能概述

用户认证/权限模块负责系统的安全访问控制，采用 **Token 认证机制**（Laravel Sanctum）实现前后端分离架构下的身份验证。系统当前仅支持**管理员角色**，所有业务功能都需要管理员权限才能访问。

#### 5.2 架构设计

采用 **前后端分离的 Token 认证架构**：

```
前端登录表单
    ↓
AuthController@login (后端验证)
    ↓
生成 Sanctum Token
    ↓
前端存储 Token (localStorage)
    ↓
后续请求携带 Token (Authorization Header)
    ↓
auth:sanctum 中间件验证
    ↓
允许访问受保护资源
```

**核心组件：**
- **后端**：Laravel Sanctum（Token 生成与验证）
- **前端**：Axios 请求拦截器（自动添加 Token）、路由守卫（检查登录状态）
- **权限控制**：基于角色的访问控制（RBAC），当前仅支持 `admin` 角色

#### 5.3 核心实现流程

**1. 用户登录流程**

```
用户输入邮箱和密码
    ↓
前端表单验证（邮箱格式、密码长度）
    ↓
POST /api/login (无需认证)
    ↓
AuthController@login
    ↓
验证邮箱和密码（Hash::check）
    ↓
检查用户状态（is_active）
    ↓
更新最后登录时间（last_login_at）
    ↓
生成 Sanctum Token（createToken）
    ↓
返回用户信息和 Token
    ↓
前端存储 Token 到 localStorage
    ↓
跳转到仪表盘页面
```

**详细步骤：**

1. **前端登录表单**（`Login.vue`）：
   - 表单验证规则：
     - `email`: 必填，邮箱格式
     - `password`: 必填，最少 6 位
   - 提交登录请求，成功后存储 Token 并跳转

2. **后端登录处理**（`AuthController@login`）：
   - **参数验证**：使用 Laravel 表单验证
   - **用户查找**：根据邮箱查找用户
   - **密码验证**：使用 `Hash::check()` 验证密码（密码存储使用 `bcrypt` 哈希）
   - **状态检查**：检查 `is_active` 字段，禁用账户返回 403
   - **更新登录时间**：记录 `last_login_at`
   - **生成 Token**：使用 `$user->createToken('api-token')->plainTextToken` 生成 Token
   - **返回响应**：返回用户信息（id、name、email、role）和 Token

**关键代码逻辑：**
```php
// 后端登录逻辑
public function login(Request $request): JsonResponse
{
    $request->validate([
        'email' => 'required|email',
        'password' => 'required|string',
    ]);

    $user = User::where('email', $request->email)->first();

    // 验证密码
    if (!$user || !Hash::check($request->password, $user->password)) {
        throw ValidationException::withMessages([
            'email' => ['邮箱或密码错误'],
        ]);
    }

    // 检查账户状态
    if (!$user->is_active) {
        return response()->json(['message' => '账户已被禁用'], 403);
    }

    // 更新登录时间
    $user->update(['last_login_at' => now()]);

    // 生成 Token
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'data' => [
            'user' => [...],
            'token' => $token,
        ],
    ]);
}
```

**2. Token 存储与使用**

- **前端存储**：
  - Token 存储在 `localStorage` 中，键名为 `token`
  - 刷新页面不会丢失，保持登录状态

- **请求拦截器**（`request.js`）：
  - 请求拦截器自动从 `localStorage` 读取 Token
  - 添加到请求头：`Authorization: Bearer {token}`
  - 响应拦截器处理 401 错误（Token 无效/过期），自动跳转到登录页

**关键代码逻辑：**
```javascript
// 前端请求拦截器
request.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

// 响应拦截器（处理 401）
request.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response?.status === 401) {
      ElMessage.error('未授权，请重新登录')
      localStorage.removeItem('token')
      router.push('/login')
    }
    return Promise.reject(error)
  }
)
```

**3. 路由保护（前端路由守卫）**

- **路由守卫**（`router/index.js`）：
  - 定义 `requireAuth` 守卫函数
  - 检查 `localStorage` 中是否存在 Token
  - 有 Token：允许访问
  - 无 Token：跳转到登录页

- **路由配置**：
  - `/login`：公开路由，无需认证
  - 其他所有路由：需要认证，使用 `beforeEnter: requireAuth`

**关键代码逻辑：**
```javascript
// 路由守卫
const requireAuth = (to, from, next) => {
  const token = localStorage.getItem('token')
  if (token) {
    next()
  } else {
    next('/login')
  }
}

// 路由配置
{
  path: '/',
  component: MainLayout,
  beforeEnter: requireAuth, // 需要认证
  children: [...]
}
```

**4. API 路由保护（后端中间件）**

- **路由分组**（`routes/api.php`）：
  - **公开路由**：`POST /api/login`（无需认证）
  - **受保护路由**：使用 `auth:sanctum` 中间件保护所有业务 API

- **中间件验证**：
  - `auth:sanctum` 中间件自动验证请求头中的 Token
  - Token 有效：将用户信息注入到 `$request->user()`
  - Token 无效/过期：返回 401 未授权错误

**路由配置示例：**
```php
Route::prefix('api')->group(function () {
    // 公开路由
    Route::post('login', [AuthController::class, 'login']);
    
    // 受保护路由
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::apiResource('products', ProductApiController::class);
        // ... 其他业务路由
    });
});
```

**5. 用户登出流程**

```
用户点击退出登录
    ↓
调用 POST /api/logout
    ↓
AuthController@logout
    ↓
删除当前 Token（currentAccessToken()->delete()）
    ↓
前端清除 localStorage 中的 Token
    ↓
跳转到登录页
```

**6. 获取当前用户信息**

- **接口**：`GET /api/me`
- **用途**：前端刷新页面后获取当前登录用户信息
- **实现**：`$request->user()` 返回当前认证用户

**7. 权限控制（基于角色）**

- **当前实现**：系统仅支持 `admin` 角色，所有用户都是管理员
- **角色检查**：`User` 模型提供 `isAdmin()` 方法
- **权限中间件**：`RoleMiddleware` 已实现，但当前未使用（所有路由统一使用 `auth:sanctum`）

**扩展建议**：
- 如需多角色支持，可在路由中使用 `middleware('role:admin,manager')` 指定允许的角色
- 权限中间件会检查用户角色是否在允许列表中

#### 5.4 技术要点

1. **Token 安全**：
   - Token 存储在 `personal_access_tokens` 表中，支持多设备登录
   - Token 值使用哈希存储，提高安全性
   - 支持 Token 过期时间设置（当前未设置，永久有效）

2. **密码安全**：
   - 使用 Laravel 的 `Hash` 门面进行密码哈希（`bcrypt`）
   - 密码字段使用 `$casts` 自动哈希：`'password' => 'hashed'`

3. **账户状态管理**：
   - `is_active` 字段控制账户启用/禁用
   - 禁用账户无法登录，返回 403 错误

4. **登录记录**：
   - `last_login_at` 字段记录最后登录时间
   - 每次登录成功自动更新

5. **前后端分离认证**：
   - 使用 Token 而非 Session，适合前后端分离架构
   - 前端负责 Token 存储和携带，后端负责验证

6. **统一错误处理**：
   - 401 错误：Token 无效/过期，前端自动跳转登录
   - 403 错误：账户被禁用或权限不足
   - 统一使用响应拦截器处理错误

#### 5.5 数据流转示例

**登录请求流程：**
```
前端登录表单提交（email, password）
  ↓
POST /api/login (无需认证)
  ↓
AuthController@login (参数验证)
  ↓
查找用户（User::where('email', ...)）
  ↓
验证密码（Hash::check）
  ↓
检查账户状态（is_active）
  ↓
更新登录时间（last_login_at）
  ↓
生成 Token（createToken）
  ↓
返回用户信息和 Token
  ↓
前端存储 Token（localStorage.setItem('token', ...)）
  ↓
跳转到仪表盘（router.push('/dashboard')）
```

**受保护 API 请求流程：**
```
前端业务请求（如获取商品列表）
  ↓
Axios 请求拦截器（自动添加 Token）
  ↓
请求头：Authorization: Bearer {token}
  ↓
GET /api/products (需要认证)
  ↓
auth:sanctum 中间件验证 Token
  ↓
Token 有效：注入用户信息到 $request->user()
  ↓
ProductApiController@index (处理业务逻辑)
  ↓
返回业务数据
  ↓
前端展示数据
```

**Token 失效处理流程：**
```
前端业务请求
  ↓
后端验证 Token（无效/过期）
  ↓
返回 401 错误
  ↓
前端响应拦截器捕获 401
  ↓
清除 localStorage 中的 Token
  ↓
显示错误提示（"未授权，请重新登录"）
  ↓
自动跳转到登录页（router.push('/login')）
```

### 六、错误处理和异常处理机制

#### 6.1 功能概述

系统实现了统一的错误处理和异常处理机制，确保 API 返回格式一致、错误信息友好，同时在生产环境中保护敏感信息不被泄露。

#### 6.2 架构设计

采用 **全局异常处理器**（`Handler.php`）统一处理所有异常：

```
业务代码抛出异常
    ↓
Handler::render() (全局异常处理器)
    ↓
判断是否为 API 请求
    ↓
handleApiException() (API 异常处理)
    ↓
根据异常类型返回统一格式的错误响应
```

#### 6.3 核心实现流程

**1. 异常类型分类处理**

系统对不同类型的异常进行分类处理，返回相应的 HTTP 状态码和错误信息：

- **验证异常**（`ValidationException`）：
  - HTTP 状态码：422
  - 返回格式：包含验证错误详情
  - 用途：表单验证失败

- **模型未找到异常**（`ModelNotFoundException`）：
  - HTTP 状态码：404
  - 返回格式：统一错误消息
  - 用途：访问不存在的资源（如商品ID不存在）

- **路由未找到异常**（`NotFoundHttpException`）：
  - HTTP 状态码：404
  - 返回格式：统一错误消息
  - 用途：访问不存在的 API 接口

- **数据库查询异常**（`QueryException`）：
  - HTTP 状态码：500
  - 返回格式：统一错误消息（不暴露 SQL 详情）
  - 用途：数据库操作失败（记录日志）

- **通用异常**（`Exception`）：
  - HTTP 状态码：根据异常类型或默认 500
  - 返回格式：根据环境配置决定是否暴露详细信息
  - 用途：业务逻辑异常（如库存不足、SKU 重复）

**关键代码逻辑：**
```php
// Handler.php - API 异常处理
protected function handleApiException($request, Throwable $exception)
{
    // 验证异常
    if ($exception instanceof ValidationException) {
        return response()->json([
            'message' => '数据验证失败',
            'errors' => $exception->errors(),
        ], 422);
    }

    // 模型未找到异常
    if ($exception instanceof ModelNotFoundException) {
        return response()->json([
            'message' => '资源不存在',
        ], 404);
    }

    // 数据库查询异常
    if ($exception instanceof QueryException) {
        \Log::error('Database query error', [
            'message' => $exception->getMessage(),
            'sql' => $exception->getSql(),
        ]);

        return response()->json([
            'message' => '数据库操作失败',
        ], 500);
    }

    // 通用异常（根据环境决定是否暴露详细信息）
    $statusCode = method_exists($exception, 'getStatusCode')
        ? $exception->getStatusCode()
        : 500;

    $message = $exception->getMessage() ?: '服务器错误';

    if (config('app.debug')) {
        // 开发环境：返回详细错误信息
        return response()->json([
            'message' => $message,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ], $statusCode);
    }

    // 生产环境：只返回友好错误消息
    return response()->json([
        'message' => $statusCode === 500 ? '服务器错误' : $message,
    ], $statusCode);
}
```

**2. 业务异常处理**

业务逻辑层（Service 层）抛出异常，由全局异常处理器统一处理：

- **商品服务异常**：
  - SKU 重复：`throw new \Exception('SKU 已存在，请使用其他 SKU')`
  - 价格不合理：`throw new \Exception('成本价不能大于售价')`

- **订单服务异常**：
  - 库存不足：`throw new \Exception("商品 {$product->name} 库存不足")`
  - 商品已下架：`throw new \Exception("商品 {$product->name} 已下架，无法购买")`
  - 状态流转非法：`throw new \Exception("订单状态不能从 {$oldStatus} 直接变更为 {$status}")`

**3. 前端错误处理**

前端通过 Axios 响应拦截器统一处理错误：

- **401 错误**：Token 无效/过期，自动清除 Token 并跳转登录页
- **403 错误**：权限不足，显示错误提示
- **404 错误**：资源不存在，显示错误提示
- **422 错误**：验证失败，显示字段级错误信息
- **500 错误**：服务器错误，显示友好错误提示

**关键代码逻辑：**
```javascript
// 前端响应拦截器
request.interceptors.response.use(
  (response) => response.data,
  (error) => {
    if (error.response) {
      const { status, data } = error.response
      
      switch (status) {
        case 401:
          ElMessage.error('未授权，请重新登录')
          localStorage.removeItem('token')
          router.push('/login')
          break
        case 403:
          ElMessage.error('没有权限访问')
          break
        case 404:
          ElMessage.error('请求的资源不存在')
          break
        case 422:
          // 验证错误，显示字段级错误
          const errors = data.errors || {}
          Object.keys(errors).forEach(key => {
            ElMessage.error(errors[key][0])
          })
          break
        case 500:
          ElMessage.error('服务器错误')
          break
        default:
          ElMessage.error(data.message || '请求失败')
      }
    }
    return Promise.reject(error)
  }
)
```

#### 6.4 技术要点

1. **统一错误格式**：所有 API 错误返回统一的 JSON 格式
2. **环境区分**：开发环境暴露详细错误信息，生产环境只返回友好消息
3. **日志记录**：数据库异常等关键错误记录到日志文件
4. **前端友好提示**：根据错误类型显示相应的用户友好提示
5. **安全考虑**：生产环境不暴露文件路径、SQL 语句等敏感信息

### 七、初始化数据设计（Seeder 设计思路）

#### 7.1 功能概述

初始化数据（Seeder）用于在系统首次启动或重置数据库时，自动创建测试数据，包括用户、分类、商品、订单、库存记录等，确保系统可以立即投入使用。

#### 7.2 设计原则

1. **幂等性**：Seeder 可以多次执行而不产生重复数据或错误
2. **数据关联性**：确保数据之间的关联关系正确（如商品关联分类、订单关联商品）
3. **数据真实性**：使用真实场景的数据，便于测试和演示
4. **执行顺序**：按照依赖关系顺序执行（先创建分类，再创建商品）

#### 7.3 核心实现流程

**1. Seeder 执行顺序**

```
DatabaseSeeder::run()
    ↓
UserSeeder (创建管理员用户)
    ↓
CategorySeeder (创建商品分类)
    ↓
ProductSeeder (创建商品，依赖分类)
    ↓
OrderSeeder (创建订单，依赖商品和用户)
    ↓
InventoryLogSeeder (创建库存记录，依赖商品和订单)
```

**2. 幂等性实现**

所有 Seeder 使用 `updateOrCreate()` 方法确保幂等性：

- **UserSeeder**：基于 `email` 唯一字段判断是否存在
- **CategorySeeder**：基于 `name` 判断是否存在
- **ProductSeeder**：基于 `sku` 唯一字段判断是否存在
- **OrderSeeder**：基于 `order_no` 唯一字段判断是否存在

**关键代码逻辑：**
```php
// UserSeeder - 幂等性实现
User::updateOrCreate(
    ['email' => 'admin@example.com'], // 唯一标识
    [
        'name' => '管理员',
        'password' => Hash::make('123456'),
        'role' => 'admin',
        'is_active' => true,
    ]
);

// ProductSeeder - 幂等性实现
Product::updateOrCreate(
    ['sku' => 'IPHONE15PRO001'], // 唯一标识
    [
        'name' => 'iPhone 15 Pro',
        'category_id' => $categories['手机'],
        'price' => 8999.00,
        'stock_quantity' => 50,
        // ... 其他字段
    ]
);
```

**3. 数据关联处理**

- **分类关联**：ProductSeeder 通过分类名称查找分类ID
- **用户关联**：OrderSeeder 通过邮箱查找用户ID
- **商品关联**：OrderSeeder 和 InventoryLogSeeder 通过 SKU 查找商品ID

**关键代码逻辑：**
```php
// ProductSeeder - 分类关联
$categories = Category::query()->pluck('id', 'name');
// 使用 $categories['手机'] 获取分类ID

// OrderSeeder - 用户和商品关联
$user = User::where('email', 'admin@example.com')->first();
$product = Product::where('sku', 'IPHONE15PRO001')->first();
```

**4. 数据量控制**

- **用户**：1 个管理员账户
- **分类**：多个商品分类（手机、电脑、配件等）
- **商品**：至少 20+ 个商品，覆盖不同分类
- **订单**：至少 12 个订单，覆盖不同状态
- **库存记录**：至少 80 条记录，覆盖不同变动类型

**5. 订单数据设计**

订单数据需要确保：
- 订单状态分布：包含 `pending`、`paid`、`shipped`、`completed`、`cancelled` 等状态
- 订单金额合理性：商品价格 × 数量，包含折扣金额
- 订单项快照：保存商品名称、SKU、价格快照
- 库存一致性：订单创建时已扣减库存，库存记录已创建

#### 7.4 技术要点

1. **幂等性保证**：使用 `updateOrCreate()` 方法，避免重复执行报错
2. **事务安全**：Seeder 执行失败时，数据库会回滚（Laravel 自动处理）
3. **数据完整性**：确保外键关联正确，避免数据不一致
4. **执行效率**：批量插入数据时使用 `insert()` 而非逐条 `create()`
5. **环境区分**：生产环境可以跳过 Seeder 执行，仅开发/测试环境使用

### 八、API 响应格式规范

#### 8.1 统一响应格式

系统所有 API 接口遵循统一的响应格式，便于前端统一处理。

**成功响应格式：**
```json
{
  "data": {
    // 业务数据
  },
  "meta": {
    // 分页信息（列表接口）
    "current_page": 1,
    "per_page": 15,
    "total": 100,
    "last_page": 7
  }
}
```

**错误响应格式：**
```json
{
  "message": "错误消息",
  "errors": {
    // 验证错误详情（422 错误）
    "email": ["邮箱格式不正确"],
    "password": ["密码长度不能少于6位"]
  }
}
```

#### 8.2 HTTP 状态码规范

- **200 OK**：请求成功
- **201 Created**：资源创建成功
- **400 Bad Request**：请求参数错误
- **401 Unauthorized**：未授权（Token 无效/过期）
- **403 Forbidden**：权限不足
- **404 Not Found**：资源不存在
- **422 Unprocessable Entity**：数据验证失败
- **500 Internal Server Error**：服务器错误

#### 8.3 资源转换（Resource）

使用 Laravel Resource 统一格式化 API 响应数据：

- **ProductResource**：商品数据格式化
- **OrderResource**：订单数据格式化（包含订单项）
- **OrderItemResource**：订单项数据格式化

**优势**：
- 统一数据格式
- 隐藏敏感字段
- 灵活控制返回字段
- 支持数据关联格式化

### API 接口文档

#### 商品 API
- `GET /api/products` - 获取商品列表
- `GET /api/products/{id}` - 获取商品详情
- `POST /api/products` - 创建商品
- `PUT /api/products/{id}` - 更新商品
- `DELETE /api/products/{id}` - 删除商品

#### 订单 API
- `GET /api/orders` - 获取订单列表
- `GET /api/orders/{id}` - 获取订单详情
- `POST /api/orders` - 创建订单
- `PUT /api/orders/{id}/status` - 更新订单状态
  - 支持的状态流转：`pending` → `paid` → `shipped` → `completed`
  - 支持取消订单：`pending`/`paid` → `cancelled`

#### 库存 API
- `GET /api/inventory` - 获取库存列表
- `GET /api/inventory/{productId}` - 获取商品库存详情
- `PUT /api/inventory/{productId}` - 更新库存

#### 仪表盘 API
- `GET /api/dashboard/summary` - 获取汇总数据

## 🐳 Docker 配置说明

### 服务说明
- **db**: MySQL 8.0 数据库服务
- **backend**: Laravel 后端服务（PHP 8.2 + Nginx）
- **frontend**: Vue 3 前端服务（Node.js + Vite）

### 数据持久化
- 数据库数据存储在 Docker Volume `db_data` 中
- 后端存储文件存储在 Docker Volume `backend_storage` 中

### 环境变量
后端环境变量在 `docker-compose.yml` 中配置，包括数据库连接信息等。

## 📝 开发说明

### 后端开发
1. 进入后端目录：`cd backend`
2. 安装依赖：`composer install`
3. 复制环境文件：`cp .env.example .env`
4. 生成应用密钥：`php artisan key:generate`
5. 运行迁移：`php artisan migrate`
6. 填充数据：`php artisan db:seed`
7. 启动开发服务器：`php artisan serve`

### 前端开发
1. 进入前端目录：`cd frontend`
2. 安装依赖：`npm install`
3. 启动开发服务器：`npm run dev`

### 运行测试
```bash
# 后端测试
cd backend
php artisan test

# 或使用 PHPUnit
vendor/bin/phpunit
```

### 认证说明
- 系统使用 Laravel Sanctum 进行 API 认证
- 前端登录后会获取 token，存储在 localStorage
- 所有 API 请求（除登录外）都需要在请求头中携带 token：`Authorization: Bearer {token}`
- 路由守卫会自动检查 token 有效性，无效或过期会跳转到登录页

## ⚠️ 注意事项

1. **首次启动**：首次启动会自动运行数据库迁移和数据填充，前端容器会在后端容器启动后自动启动
2. **端口占用**：确保 3000、8000、3306 端口未被占用
4. **数据持久化**：使用 `docker compose down -v` 会删除所有数据卷
5. **开发模式**：当前配置为开发模式，生产环境需要调整配置
6. **认证 Token**：登录后 token 存储在 localStorage，刷新页面不会丢失
7. **错误处理**：系统已实现统一的错误处理机制，API 错误会自动显示提示信息

## ✨ 功能特性

### 已实现功能
- ✅ **商品管理**：商品的增删改查、状态管理、SKU 唯一性验证
- ✅ **订单管理**：订单创建、完整状态流转（待支付→已支付→已发货→已完成）、订单详情查看
- ✅ **库存管理**：库存查询、库存调整、库存状态筛选（缺货/低库存/充足）、库存变动记录
- ✅ **用户认证**：基于 Sanctum 的 API 认证、管理员权限控制
- ✅ **错误处理**：统一的异常处理和错误响应格式
- ✅ **单元测试**：关键业务逻辑的单元测试覆盖
- ✅ **Docker 优化**：多阶段构建、依赖缓存优化、镜像大小优化

### 技术亮点
- 🚀 **前后端分离**：Vue 3 + Laravel API，清晰的架构设计
- 🔒 **安全认证**：基于 Token 的认证机制，管理员权限控制
- 📊 **数据统计**：实时数据统计和图表展示
- 🐳 **容器化部署**：100% Docker 容器化，一键启动
- 🧪 **测试覆盖**：关键业务逻辑的单元测试
- ⚡ **性能优化**：Docker 多阶段构建、依赖缓存优化

### UI 设计与交互优化

为提升可用性和观感，本项目前端基于 Element Plus 做了整体 UI 提升，风格与现代 SaaS/CRM 后台一致：

- **整体布局**
  - 左侧固定导航栏 + 右侧内容区的经典管理后台布局。
  - 顶部为轻量级页头，仅保留「控制台」面包屑标签，减少视觉干扰。
  - 内容区域统一使用大圆角卡片与柔和阴影，背景采用淡蓝渐变，营造干净、轻量的工作面板体验。

- **侧边导航栏**
  - 左侧导航使用浅蓝渐变背景和白色卡片风格，选中菜单项采用蓝紫渐变高亮，带柔和投影，方便识别当前模块。
  - 菜单项图标与文案间距统一，悬浮状态使用白底高亮，保证 Hover 反馈清晰。

- **用户信息区域（左下角）**
  - 用户信息区域固定在左下角，以白色圆角卡片承载当前登录账号信息。
  - 头像圆点使用账号首字母大写作为展示，并采用蓝紫渐变背景，与主题色保持一致。
  - 默认仅展示「头像 + 账号 + 管理员角色」，鼠标移入整块区域时，自右下方淡入一个「退出登录」按钮，点击后完成登出逻辑，交互符合直觉又避免界面噪音。

- **业务页面风格统一**
  - 仪表盘、商品列表、订单列表、库存页、表单页和详情页均采用统一的 `page-shell` 卡片容器，保证边距、圆角、阴影和标题层级一致。
  - 每个页面卡片顶部均包含「主标题 + 副标题」说明，帮助快速理解当前页面的业务目的。
  - 列表页的筛选区域、分页区域与表格之间留白统一，视觉层级清晰。

### 库存状态说明
- **缺货**：库存数量 = 0
- **低库存**：0 < 库存数量 ≤ 10
- **充足**：库存数量 > 10

## 🐛 常见问题

### 问题1：容器启动失败
**解决方案**：检查端口是否被占用，确保 Docker Desktop 正常运行

### 问题2：数据库连接失败
**解决方案**：等待数据库容器完全启动（健康检查通过）后再启动后端

### 问题3：前端无法访问后端 API
**解决方案**：检查 `vite.config.js` 中的代理配置，确保后端服务正常运行

## 📄 许可证

MIT License
