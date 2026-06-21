#!/bin/sh
# 健康检查脚本：检查后端是否已初始化完成
# 检查 API 是否可以访问（需要数据库初始化完成）

response=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/dashboard/summary 2>/dev/null || echo "000")

# 如果返回 200 或 401（401 表示服务正常但需要认证），说明初始化完成
# 返回 500 可能表示数据库未初始化，需要继续等待
if [ "$response" = "200" ] || [ "$response" = "401" ]; then
    exit 0
fi

# 其他状态码表示服务未就绪
exit 1
